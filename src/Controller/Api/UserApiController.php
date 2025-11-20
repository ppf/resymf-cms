<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
#[IsGranted('ROLE_ADMIN')]
class UserApiController extends AbstractController
{
    #[Route('', name: 'api_users_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(50, $request->query->getInt('limit', 10)));
        $search = trim((string) $request->query->get('q', ''));
        $sortField = $request->query->get('sort', 'createdAt');
        $sortOrder = strtoupper($request->query->get('order', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        // Validate sort field to prevent SQL injection
        $allowedSortFields = ['id', 'username', 'email', 'isActive', 'createdAt', 'updatedAt'];
        if (!in_array($sortField, $allowedSortFields, true)) {
            $sortField = 'createdAt';
        }

        $qb = $users->createQueryBuilder('u')
            ->orderBy('u.'.$sortField, $sortOrder);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(u.username) LIKE :q OR LOWER(u.email) LIKE :q')
                ->setParameter('q', '%'.mb_strtolower($search).'%');
        }

        $qb
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        $paginator = new DoctrinePaginator($qb, true);
        $items = [];
        foreach ($paginator as $entity) {
            $items[] = [
                'id' => $entity->getId(),
                'username' => $entity->getUsername(),
                'email' => $entity->getEmail(),
                'roles' => $entity->getRoles(),
                'isActive' => $entity->getIsActive(),
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

    #[Route('/{id}', name: 'api_users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        // Prevent users from deleting themselves
        if ($user === $this->getUser()) {
            return $this->json([
                'error' => 'You cannot delete your own account.',
            ], 400);
        }

        $username = $user->getUsername();
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => sprintf('User "%s" has been deleted successfully.', $username),
        ]);
    }
}
