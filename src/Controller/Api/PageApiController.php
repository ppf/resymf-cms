<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\PageRepository;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/pages')]
#[IsGranted('ROLE_USER')]
class PageApiController extends AbstractController
{
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

        $paginator = new DoctrinePaginator($qb, true);
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
