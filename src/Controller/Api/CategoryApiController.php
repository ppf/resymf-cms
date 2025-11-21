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
}
