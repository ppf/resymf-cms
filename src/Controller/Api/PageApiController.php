<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Page;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/api/pages')]
#[IsGranted('ROLE_ADMIN')]
class PageApiController extends AbstractController
{
    /**
     * List pages for grid (existing endpoint).
     */
    #[Route('/list', name: 'api_pages_list', methods: ['GET'])]
    public function list(Request $request, PageRepository $pages): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'createdAt');
        $order = $request->query->get('order', 'desc');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('perPage', 20)));
        $categoryId = $request->query->get('category', null);

        $validSorts = ['title', 'slug', 'isPublished', 'isHomepage', 'viewCount', 'createdAt', 'publishedAt'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'createdAt';
        }

        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $pages->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->leftJoin('p.author', 'a')
            ->addSelect('c', 'a')
            ->orderBy('p.' . $sort, $order);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(p.title) LIKE :search OR LOWER(p.slug) LIKE :search OR LOWER(p.content) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($categoryId) {
            $qb
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        // Count total efficiently using COUNT query instead of fetching all results
        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT p.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        // Paginate
        $results = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $data = array_map(static fn ($p) => [
            'id' => $p->getId(),
            'title' => $p->getTitle(),
            'slug' => $p->getSlug(),
            'isPublished' => $p->getIsPublished(),
            'isHomepage' => $p->getIsHomepage(),
            'viewCount' => $p->getViewCount(),
            'author' => $p->getAuthor() ? $p->getAuthor()->getEmail() : null,
            'categories' => array_map(static fn ($cat) => [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
            ], $p->getCategories()->toArray()),
            'createdAt' => $p->getCreatedAt()->format('c'),
            'publishedAt' => $p->getPublishedAt()?->format('c'),
        ], $results);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    /**
     * List pages with pagination (standard endpoint).
     */
    #[Route('', name: 'api_pages_index', methods: ['GET'])]
    public function index(Request $request, PageRepository $pages): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $search = trim((string) $request->query->get('q', ''));
        $sortField = $request->query->get('sort', 'createdAt');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['id', 'title', 'slug', 'isPublished', 'isHomepage', 'displayOrder', 'viewCount', 'publishedAt', 'createdAt', 'updatedAt'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'createdAt';
        }

        $qb = $pages->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->orderBy('p.' . $sortField, $sortOrder);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(p.title) LIKE :q OR LOWER(p.content) LIKE :q OR LOWER(p.slug) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        $qb
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $paginator = new DoctrinePaginator($qb, true);
        $items = [];
        foreach ($paginator as $entity) {
            $items[] = [
                'id' => $entity->getId(),
                'title' => $entity->getTitle(),
                'slug' => $entity->getSlug(),
                'excerpt' => $entity->getExcerpt(150),
                'isPublished' => $entity->getIsPublished(),
                'isHomepage' => $entity->getIsHomepage(),
                'displayOrder' => $entity->getDisplayOrder(),
                'viewCount' => $entity->getViewCount(),
                'authorName' => $entity->getAuthor()?->getUsername(),
                'publishedAt' => $entity->getPublishedAt()?->format(\DateTimeInterface::ATOM),
                'createdAt' => $entity->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $entity->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => \count($paginator),
                'pages' => (int) ceil(\count($paginator) / $limit),
                'has_next' => ($page * $limit) < \count($paginator),
                'has_prev' => $page > 1,
            ],
        ]);
    }

    /**
     * Get single page by ID.
     */
    #[Route('/{id}', name: 'api_pages_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id, PageRepository $pages): JsonResponse
    {
        $page = $pages->find($id);

        if (!$page) {
            return $this->json(['error' => 'Page not found'], 404);
        }

        $categoryIds = [];
        foreach ($page->getCategories() as $category) {
            $categoryIds[] = $category->getId();
        }

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'slug' => $page->getSlug(),
            'content' => $page->getContent(),
            'metaDescription' => $page->getMetaDescription(),
            'metaKeywords' => $page->getMetaKeywords(),
            'isPublished' => $page->getIsPublished(),
            'isHomepage' => $page->getIsHomepage(),
            'displayOrder' => $page->getDisplayOrder(),
            'viewCount' => $page->getViewCount(),
            'publishedAt' => $page->getPublishedAt()?->format('c'),
            'authorId' => $page->getAuthor()?->getId(),
            'authorName' => $page->getAuthor()?->getUsername(),
            'categoryIds' => $categoryIds,
            'createdAt' => $page->getCreatedAt()->format('c'),
            'updatedAt' => $page->getUpdatedAt()?->format('c'),
        ]);
    }

    /**
     * Create new page.
     */
    #[Route('', name: 'api_pages_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        PageRepository $pages,
        CategoryRepository $categories,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validatePageData($data, $pages);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Create page
        $page = new Page();
        $page->setTitle($data['title']);
        $page->setSlug($data['slug']);
        $page->setContent($data['content'] ?? '');
        $page->setMetaDescription($data['metaDescription'] ?? null);
        $page->setMetaKeywords($data['metaKeywords'] ?? null);
        $page->setIsPublished($data['isPublished'] ?? false);
        $page->setDisplayOrder($data['displayOrder'] ?? 0);

        // Set author to current user
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $page->setAuthor($user);

        // Handle publishedAt
        if (!empty($data['publishedAt'])) {
            try {
                $page->setPublishedAt(new \DateTimeImmutable($data['publishedAt']));
            } catch (\Exception $e) {
                return $this->json(['errors' => ['publishedAt' => 'Invalid date format']], 422);
            }
        }

        // Handle homepage - unset all other pages if this is set as homepage
        if (!empty($data['isHomepage'])) {
            $pages->unsetAllHomepages();
            $page->setIsHomepage(true);
        }

        // Handle categories
        if (!empty($data['categoryIds']) && is_array($data['categoryIds'])) {
            foreach ($data['categoryIds'] as $categoryId) {
                $category = $categories->find($categoryId);
                if ($category) {
                    $page->addCategory($category);
                }
            }
        }

        $em->persist($page);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Page created successfully',
            'id' => $page->getId(),
        ], 201);
    }

    /**
     * Update existing page.
     */
    #[Route('/{id}', name: 'api_pages_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        PageRepository $pages,
        CategoryRepository $categories,
    ): JsonResponse {
        $page = $pages->find($id);

        if (!$page) {
            return $this->json(['error' => 'Page not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validatePageData($data, $pages, $page);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Update page
        $page->setTitle($data['title']);
        $page->setSlug($data['slug']);
        $page->setContent($data['content'] ?? '');
        $page->setMetaDescription($data['metaDescription'] ?? null);
        $page->setMetaKeywords($data['metaKeywords'] ?? null);
        $page->setIsPublished($data['isPublished'] ?? false);
        $page->setDisplayOrder($data['displayOrder'] ?? 0);

        // Handle publishedAt
        if (isset($data['publishedAt'])) {
            if ($data['publishedAt']) {
                try {
                    $page->setPublishedAt(new \DateTimeImmutable($data['publishedAt']));
                } catch (\Exception $e) {
                    return $this->json(['errors' => ['publishedAt' => 'Invalid date format']], 422);
                }
            } else {
                $page->setPublishedAt(null);
            }
        }

        // Handle homepage - unset all other pages if this is set as homepage
        if (isset($data['isHomepage'])) {
            if ($data['isHomepage'] && !$page->getIsHomepage()) {
                $pages->unsetAllHomepages();
                $page->setIsHomepage(true);
            } elseif (!$data['isHomepage']) {
                $page->setIsHomepage(false);
            }
        }

        // Handle categories - clear and re-add
        if (isset($data['categoryIds'])) {
            // Clear existing categories
            foreach ($page->getCategories() as $category) {
                $page->removeCategory($category);
            }

            // Add new categories
            if (is_array($data['categoryIds'])) {
                foreach ($data['categoryIds'] as $categoryId) {
                    $category = $categories->find($categoryId);
                    if ($category) {
                        $page->addCategory($category);
                    }
                }
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Page updated successfully',
        ]);
    }

    #[Route('/{id}', name: 'api_pages_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Page $page, EntityManagerInterface $entityManager): JsonResponse
    {
        $title = $page->getTitle();
        $entityManager->remove($page);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => sprintf('Page "%s" has been deleted successfully.', $title),
        ]);
    }

    /**
     * Validate slug uniqueness.
     */
    #[Route('/validate-slug', name: 'api_pages_validate_slug', methods: ['POST'])]
    public function validateSlug(Request $request, PageRepository $pages): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $slug = $data['slug'] ?? '';
        $excludeId = $data['excludeId'] ?? null;

        // Basic format validation
        if (!preg_match('/^[a-z0-9-\/]+$/', $slug)) {
            return $this->json([
                'valid' => false,
                'message' => 'Slug can only contain lowercase letters, numbers, hyphens, and forward slashes',
            ]);
        }

        // Check uniqueness
        $qb = $pages->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId) {
            $qb->andWhere('p.id != :id')
               ->setParameter('id', $excludeId);
        }

        $exists = $qb->getQuery()->getOneOrNullResult() !== null;

        return $this->json([
            'valid' => !$exists,
            'message' => $exists ? 'This slug is already used by another page' : 'Slug is available',
        ]);
    }

    /**
     * Generate slug from title.
     */
    #[Route('/generate-slug', name: 'api_pages_generate_slug', methods: ['POST'])]
    public function generateSlug(Request $request, SluggerInterface $slugger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $title = $data['title'] ?? '';

        if (empty($title)) {
            return $this->json(['error' => 'Title is required'], 400);
        }

        $slug = $slugger->slug($title)->lower()->toString();

        return $this->json([
            'slug' => $slug,
        ]);
    }

    /**
     * Validate page data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string> Validation errors
     */
    private function validatePageData(array $data, PageRepository $pages, ?Page $existing = null): array
    {
        $errors = [];

        // Validate title
        if (empty($data['title'])) {
            $errors['title'] = 'Page title is required';
        } elseif (strlen($data['title']) < 3) {
            $errors['title'] = 'Title must be at least 3 characters';
        } elseif (strlen($data['title']) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters';
        }

        // Validate slug
        if (empty($data['slug'])) {
            $errors['slug'] = 'Slug is required';
        } elseif (strlen($data['slug']) > 255) {
            $errors['slug'] = 'Slug must not exceed 255 characters';
        } elseif (!preg_match('/^[a-z0-9-\/]+$/', $data['slug'])) {
            $errors['slug'] = 'Slug can only contain lowercase letters, numbers, hyphens, and forward slashes';
        } else {
            // Check slug uniqueness
            $qb = $pages->createQueryBuilder('p')
                ->where('p.slug = :slug')
                ->setParameter('slug', $data['slug']);

            if ($existing) {
                $qb->andWhere('p.id != :id')
                   ->setParameter('id', $existing->getId());
            }

            if ($qb->getQuery()->getOneOrNullResult() !== null) {
                $errors['slug'] = 'This slug is already used by another page';
            }
        }

        // Validate content
        if (empty($data['content'])) {
            $errors['content'] = 'Content is required';
        }

        // Validate metaDescription length
        if (isset($data['metaDescription']) && strlen($data['metaDescription']) > 255) {
            $errors['metaDescription'] = 'Meta description must not exceed 255 characters';
        }

        // Validate metaKeywords length
        if (isset($data['metaKeywords']) && strlen($data['metaKeywords']) > 255) {
            $errors['metaKeywords'] = 'Meta keywords must not exceed 255 characters';
        }

        // Validate displayOrder
        if (isset($data['displayOrder']) && (!is_numeric($data['displayOrder']) || $data['displayOrder'] < 0)) {
            $errors['displayOrder'] = 'Display order must be a positive number';
        }

        return $errors;
    }
}
