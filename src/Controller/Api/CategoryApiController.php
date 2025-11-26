<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/categories')]
#[IsGranted('ROLE_USER')]
class CategoryApiController extends AbstractController
{
    #[Route('', name: 'api_categories_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categories): JsonResponse
    {
        $search = trim((string) $request->query->get('q', ''));

        $qb = $categories->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(c.name) LIKE :q OR LOWER(c.slug) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        $results = $qb
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        $items = array_map(static fn ($category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
        ], $results);

        return $this->json(['items' => $items]);
    }

    #[Route('/list', name: 'api_categories_list', methods: ['GET'])]
    public function list(Request $request, CategoryRepository $categories): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $sort = $request->query->get('sort', 'displayOrder');
        $order = $request->query->get('order', 'asc');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('perPage', 20)));

        $validSorts = ['name', 'slug', 'isActive', 'displayOrder', 'createdAt'];
        if (!in_array($sort, $validSorts, true)) {
            $sort = 'displayOrder';
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $categories->createQueryBuilder('c')
            ->select('c', 'COUNT(p.id) as HIDDEN pageCount')
            ->leftJoin('c.pages', 'p')
            ->groupBy('c.id')
            ->orderBy('c.' . $sort, $order);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(c.name) LIKE :search OR LOWER(c.slug) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        // Count total
        $countQb = clone $qb;
        $total = count($countQb->getQuery()->getResult());

        // Paginate
        $results = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        $data = array_map(static fn ($category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'isActive' => $category->getIsActive(),
            'displayOrder' => $category->getDisplayOrder(),
            'pageCount' => $category->getPageCount(),
            'createdAt' => $category->getCreatedAt()->format('c'),
        ], $results);

        return $this->json([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/reorder', name: 'api_categories_reorder', methods: ['POST'])]
    public function reorder(Request $request, EntityManagerInterface $em, CategoryRepository $categories): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['items']) || !is_array($data['items'])) {
            return $this->json(['error' => 'Invalid request format'], 400);
        }

        foreach ($data['items'] as $item) {
            if (!isset($item['id'], $item['displayOrder'])) {
                continue;
            }

            $category = $categories->find($item['id']);
            if ($category instanceof Category) {
                $category->setDisplayOrder((int) $item['displayOrder']);
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Display order updated',
        ]);
    }

    /**
     * Get single category by ID.
     */
    #[Route('/{id}', name: 'api_categories_get', methods: ['GET'])]
    public function get(int $id, CategoryRepository $categories): JsonResponse
    {
        $category = $categories->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], 404);
        }

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'slug' => $category->getSlug(),
            'description' => $category->getDescription(),
            'displayOrder' => $category->getDisplayOrder(),
            'isActive' => $category->getIsActive(),
            'pageCount' => $category->getPageCount(),
            'createdAt' => $category->getCreatedAt()->format('c'),
        ]);
    }

    /**
     * Create new category.
     */
    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $categories): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validateCategoryData($data, $categories);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Create category
        $category = new Category();
        $category->setName($data['name']);
        $category->setSlug($data['slug']);
        $category->setDescription($data['description'] ?? '');
        $category->setDisplayOrder($data['displayOrder'] ?? 0);
        $category->setIsActive($data['isActive'] ?? true);

        $em->persist($category);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Category created successfully',
            'id' => $category->getId(),
        ], 201);
    }

    /**
     * Update existing category.
     */
    #[Route('/{id}', name: 'api_categories_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(int $id, Request $request, EntityManagerInterface $em, CategoryRepository $categories): JsonResponse
    {
        $category = $categories->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validateCategoryData($data, $categories, $category);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Update category
        $category->setName($data['name']);
        $category->setSlug($data['slug']);
        $category->setDescription($data['description'] ?? '');
        $category->setDisplayOrder($data['displayOrder'] ?? 0);
        $category->setIsActive($data['isActive'] ?? true);

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Category updated successfully',
        ]);
    }

    /**
     * Delete category.
     */
    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $em, CategoryRepository $categories): JsonResponse
    {
        $category = $categories->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], 404);
        }

        // Check if category has pages
        if ($category->getPageCount() > 0) {
            return $this->json([
                'error' => 'Cannot delete category with associated pages',
            ], 400);
        }

        $em->remove($category);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    /**
     * Validate slug uniqueness.
     */
    #[Route('/validate-slug', name: 'api_categories_validate_slug', methods: ['POST'])]
    public function validateSlug(Request $request, CategoryRepository $categories): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $slug = $data['slug'] ?? '';
        $excludeId = $data['excludeId'] ?? null;

        // Basic slug format validation
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return $this->json([
                'valid' => false,
                'message' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            ]);
        }

        // Check uniqueness
        $qb = $categories->createQueryBuilder('c')
            ->where('c.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId) {
            $qb->andWhere('c.id != :id')
               ->setParameter('id', $excludeId);
        }

        $exists = $qb->getQuery()->getOneOrNullResult() !== null;

        return $this->json([
            'valid' => !$exists,
            'message' => $exists ? 'Slug already exists' : 'Slug is available',
        ]);
    }

    /**
     * Validate category data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string> Validation errors
     */
    private function validateCategoryData(array $data, CategoryRepository $categories, ?Category $existing = null): array
    {
        $errors = [];

        // Validate name
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        } elseif (strlen($data['name']) > 100) {
            $errors['name'] = 'Name must not exceed 100 characters';
        } else {
            // Check name uniqueness
            $qb = $categories->createQueryBuilder('c')
                ->where('c.name = :name')
                ->setParameter('name', $data['name']);

            if ($existing) {
                $qb->andWhere('c.id != :id')
                   ->setParameter('id', $existing->getId());
            }

            if ($qb->getQuery()->getOneOrNullResult() !== null) {
                $errors['name'] = 'This category name is already taken';
            }
        }

        // Validate slug
        if (empty($data['slug'])) {
            $errors['slug'] = 'Slug is required';
        } elseif (strlen($data['slug']) > 128) {
            $errors['slug'] = 'Slug must not exceed 128 characters';
        } elseif (!preg_match('/^[a-z0-9-]+$/', $data['slug'])) {
            $errors['slug'] = 'Slug can only contain lowercase letters, numbers, and hyphens';
        } else {
            // Check slug uniqueness
            $qb = $categories->createQueryBuilder('c')
                ->where('c.slug = :slug')
                ->setParameter('slug', $data['slug']);

            if ($existing) {
                $qb->andWhere('c.id != :id')
                   ->setParameter('id', $existing->getId());
            }

            if ($qb->getQuery()->getOneOrNullResult() !== null) {
                $errors['slug'] = 'This slug is already taken';
            }
        }

        // Validate display order
        if (isset($data['displayOrder']) && (!is_numeric($data['displayOrder']) || $data['displayOrder'] < 0)) {
            $errors['displayOrder'] = 'Display order must be a positive number';
        }

        return $errors;
    }
}
