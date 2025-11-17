<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin Category CRUD Controller.
 *
 * Handles category management in the admin area
 */
#[Route('/admin/categories')]
#[IsGranted('ROLE_USER')]
class CategoryController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * List all categories.
     */
    #[Route('', name: 'admin_category_index', methods: ['GET'])]
    public function index(): Response
    {
        $categories = $this->categoryRepository->findAllOrderedByOrder();

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    /**
     * Create a new category.
     */
    #[Route('/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Category "%s" has been created successfully.', $category->getName()));

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * Show category details.
     */
    #[Route('/{id}', name: 'admin_category_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Category $category): Response
    {
        return $this->render('admin/category/show.html.twig', [
            'category' => $category,
        ]);
    }

    /**
     * Edit existing category.
     */
    #[Route('/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Category $category): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Category "%s" has been updated successfully.', $category->getName()));

            return $this->redirectToRoute('admin_category_index');
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    /**
     * Delete category.
     */
    #[Route('/{id}/delete', name: 'admin_category_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            $name = $category->getName();
            $this->entityManager->remove($category);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Category "%s" has been deleted successfully.', $name));
        } else {
            $this->addFlash('error', 'Invalid CSRF token. Category was not deleted.');
        }

        return $this->redirectToRoute('admin_category_index');
    }

    /**
     * Toggle active status.
     */
    #[Route('/{id}/toggle-active', name: 'admin_category_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(Request $request, Category $category): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $category->getId(), $request->request->get('_token'))) {
            $category->setIsActive(!$category->getIsActive());
            $this->entityManager->flush();

            $status = $category->getIsActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', sprintf('Category "%s" has been %s.', $category->getName(), $status));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_category_index');
    }
}
