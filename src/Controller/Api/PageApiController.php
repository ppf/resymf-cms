<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/pages')]
#[IsGranted('ROLE_USER')]
class PageApiController extends AbstractController
{
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
            ->groupBy('p.id')
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

        // Count total
        $countQb = clone $qb;
        $total = count($countQb->getQuery()->getResult());

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

    #[Route('', name: 'api_pages_index', methods: ['GET'])]
    public function index(Request $request, PageRepository $pages): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $search = trim((string) $request->query->get('q', ''));

        $qb = $pages->createQueryBuilder('p')
            ->leftJoin('p.categories', 'c')
            ->addSelect('c')
            ->orderBy('p.publishedAt', 'DESC');

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(p.title) LIKE :q OR LOWER(p.slug) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($search) . '%');
        }

        $qb
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb, true);
        $items = [];
        foreach ($paginator as $entity) {
            $items[] = [
                'id' => $entity->getId(),
                'title' => $entity->getTitle(),
                'slug' => $entity->getSlug(),
                'publishedAt' => $entity->getPublishedAt()?->format(\DateTimeInterface::ATOM),
                'viewCount' => $entity->getViewCount(),
                'categories' => array_map(static fn ($category) => [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                ], $entity->getCategories()->toArray()),
            ];
        }

        return $this->json([
            'items' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => \count($paginator),
                'has_next' => ($page * $limit) < \count($paginator),
            ],
        ]);
    }
}
