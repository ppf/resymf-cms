<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin User CRUD Controller.
 *
 * Handles user management in the admin area
 */
#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * List all users (Vue-powered grid).
     */
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(): Response
    {
        // Users are now loaded via Vue component from API
        return $this->render('admin/user/index.html.twig');
    }

    /**
     * Create a new user.
     */
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'include_password' => true,
            'require_password' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            if ($user->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword(),
                );
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" has been created successfully.', $user->getUsername()));

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Show user details.
     */
    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Edit existing user.
     */
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'include_password' => true,
            'require_password' => false, // Password is optional when editing
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password if it was changed
            if ($user->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword(),
                );
                $user->setPassword($hashedPassword);
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" has been updated successfully.', $user->getUsername()));

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Delete user.
     */
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            // Prevent users from deleting themselves
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'You cannot delete your own account.');

                return $this->redirectToRoute('admin_user_index');
            }

            $username = $user->getUsername();
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('User "%s" has been deleted successfully.', $username));
        } else {
            $this->addFlash('error', 'Invalid CSRF token. User was not deleted.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Toggle user active status.
     */
    #[Route('/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            // Prevent users from deactivating themselves
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'You cannot deactivate your own account.');

                return $this->redirectToRoute('admin_user_index');
            }

            $user->setIsActive(!$user->getIsActive());
            $this->entityManager->flush();

            $status = $user->getIsActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', sprintf('User "%s" has been %s.', $user->getUsername(), $status));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
