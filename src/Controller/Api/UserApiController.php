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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
            ->orderBy('u.' . $sortField, $sortOrder);

        if ('' !== $search) {
            $qb
                ->andWhere('LOWER(u.username) LIKE :q OR LOWER(u.email) LIKE :q')
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

    /**
     * Get single user by ID.
     */
    #[Route('/{id}', name: 'api_users_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id, UserRepository $users): JsonResponse
    {
        $user = $users->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->getIsActive(),
            'themeId' => $user->getTheme()?->getId(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()?->format('c'),
        ]);
    }

    /**
     * Create new user.
     */
    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validateUserData($data, $users);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Password is required for new users
        if (empty($data['password'])) {
            return $this->json(['errors' => ['password' => 'Password is required']], 422);
        }

        // Create user
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setIsActive($data['isActive'] ?? true);

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Set theme if provided
        if (!empty($data['themeId'])) {
            $theme = $em->getReference('App\Entity\Theme', $data['themeId']);
            $user->setTheme($theme);
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User created successfully',
            'id' => $user->getId(),
        ], 201);
    }

    /**
     * Update existing user.
     */
    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $user = $users->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        // Prevent users from modifying themselves
        if ($user === $this->getUser()) {
            return $this->json(['error' => 'You cannot modify your own account via this endpoint'], 400);
        }

        $data = json_decode($request->getContent(), true);

        // Validate input
        $errors = $this->validateUserData($data, $users, $user);
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 422);
        }

        // Update user
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setIsActive($data['isActive'] ?? true);

        // Update password if provided
        if (!empty($data['password'])) {
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        // Update theme
        if (isset($data['themeId'])) {
            if ($data['themeId']) {
                $theme = $em->getReference('App\Entity\Theme', $data['themeId']);
                $user->setTheme($theme);
            } else {
                $user->setTheme(null);
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User updated successfully',
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

    /**
     * Validate username uniqueness.
     */
    #[Route('/validate-username', name: 'api_users_validate_username', methods: ['POST'])]
    public function validateUsername(Request $request, UserRepository $users): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? '';
        $excludeId = $data['excludeId'] ?? null;

        // Basic format validation
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            return $this->json([
                'valid' => false,
                'message' => 'Username can only contain letters, numbers, underscores, and hyphens',
            ]);
        }

        // Check uniqueness
        $qb = $users->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username);

        if ($excludeId) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $excludeId);
        }

        $exists = $qb->getQuery()->getOneOrNullResult() !== null;

        return $this->json([
            'valid' => !$exists,
            'message' => $exists ? 'Username already exists' : 'Username is available',
        ]);
    }

    /**
     * Validate email uniqueness.
     */
    #[Route('/validate-email', name: 'api_users_validate_email', methods: ['POST'])]
    public function validateEmail(Request $request, UserRepository $users): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $excludeId = $data['excludeId'] ?? null;

        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'valid' => false,
                'message' => 'Please enter a valid email address',
            ]);
        }

        // Check uniqueness
        $qb = $users->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        if ($excludeId) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $excludeId);
        }

        $exists = $qb->getQuery()->getOneOrNullResult() !== null;

        return $this->json([
            'valid' => !$exists,
            'message' => $exists ? 'Email already exists' : 'Email is available',
        ]);
    }

    /**
     * Validate user data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, string> Validation errors
     */
    private function validateUserData(array $data, UserRepository $users, ?User $existing = null): array
    {
        $errors = [];

        // Validate username
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 25) {
            $errors['username'] = 'Username must not exceed 25 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, underscores, and hyphens';
        } else {
            // Check username uniqueness
            $qb = $users->createQueryBuilder('u')
                ->where('u.username = :username')
                ->setParameter('username', $data['username']);

            if ($existing) {
                $qb->andWhere('u.id != :id')
                   ->setParameter('id', $existing->getId());
            }

            if ($qb->getQuery()->getOneOrNullResult() !== null) {
                $errors['username'] = 'This username is already taken';
            }
        }

        // Validate email
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif (strlen($data['email']) > 180) {
            $errors['email'] = 'Email must not exceed 180 characters';
        } else {
            // Check email uniqueness
            $qb = $users->createQueryBuilder('u')
                ->where('u.email = :email')
                ->setParameter('email', $data['email']);

            if ($existing) {
                $qb->andWhere('u.id != :id')
                   ->setParameter('id', $existing->getId());
            }

            if ($qb->getQuery()->getOneOrNullResult() !== null) {
                $errors['email'] = 'This email address is already registered';
            }
        }

        // Validate password (if provided)
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }

            // Validate password confirmation
            if (isset($data['passwordConfirm']) && $data['password'] !== $data['passwordConfirm']) {
                $errors['passwordConfirm'] = 'Password confirmation does not match';
            }
        }

        // Validate roles
        if (isset($data['roles']) && !is_array($data['roles'])) {
            $errors['roles'] = 'Roles must be an array';
        }

        return $errors;
    }
}
