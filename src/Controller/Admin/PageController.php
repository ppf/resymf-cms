<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin Page CRUD Controller.
 *
 * Handles CMS page management in the admin area
 * Modern Symfony 7 implementation with autowiring and PHP 8 attributes
 */
#[Route('/admin/pages')]
#[IsGranted('ROLE_USER')]
class PageController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageRepository $pageRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * List all pages with pagination.
     */
    #[Route('', name: 'admin_page_index', methods: ['GET'])]
    public function index(): Response
    {
        $pages = $this->pageRepository->findAllOrderedByCreated();

        return $this->render('admin/page/index.html.twig', [
            'pages' => $pages,
        ]);
    }

    /**
     * Create a new page (Vue form).
     */
    #[Route('/new', name: 'admin_page_new', methods: ['GET'])]
    public function new(): Response
    {
        // Fetch all categories for the form
        $categories = $this->categoryRepository->findAll();
        $categoriesData = array_map(fn ($category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ], $categories);

        return $this->render('admin/page/new.html.twig', [
            'categoriesData' => json_encode($categoriesData),
        ]);
    }

    /**
     * Show page details.
     */
    #[Route('/{id}', name: 'admin_page_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Page $page): Response
    {
        return $this->render('admin/page/show.html.twig', [
            'page' => $page,
        ]);
    }

    /**
     * Edit existing page (Vue form).
     */
    #[Route('/{id}/edit', name: 'admin_page_edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function edit(Page $page): Response
    {
        // Fetch all categories for the form
        $categories = $this->categoryRepository->findAll();
        $categoriesData = array_map(fn ($category) => [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'description' => $category->getDescription(),
        ], $categories);

        return $this->render('admin/page/edit.html.twig', [
            'page' => $page,
            'categoriesData' => json_encode($categoriesData),
        ]);
    }

    /**
     * Delete page.
     */
    #[Route('/{id}/delete', name: 'admin_page_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Page $page): Response
    {
        // CSRF protection
        if ($this->isCsrfTokenValid('delete' . $page->getId(), $request->request->get('_token'))) {
            $title = $page->getTitle();
            $this->entityManager->remove($page);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Page "%s" has been deleted successfully.', $title));
        } else {
            $this->addFlash('error', 'Invalid CSRF token. Page was not deleted.');
        }

        return $this->redirectToRoute('admin_page_index');
    }

    /**
     * Toggle published status.
     */
    #[Route('/{id}/toggle-published', name: 'admin_page_toggle_published', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function togglePublished(Request $request, Page $page): Response
    {
        if ($this->isCsrfTokenValid('toggle', $request->request->get('_token'))) {
            $page->setIsPublished(!$page->getIsPublished());
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'isPublished' => $page->getIsPublished(),
                ]);
            }

            $status = $page->getIsPublished() ? 'published' : 'unpublished';
            $this->addFlash('success', sprintf('Page "%s" has been %s.', $page->getTitle(), $status));
        } else {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 400);
            }
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_page_index');
    }

    /**
     * Toggle homepage status.
     */
    #[Route('/{id}/toggle-homepage', name: 'admin_page_toggle_homepage', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleHomepage(Request $request, Page $page): Response
    {
        if ($this->isCsrfTokenValid('toggle', $request->request->get('_token'))) {
            if (!$page->getIsHomepage()) {
                // Unset all other homepage flags
                $this->pageRepository->unsetAllHomepages();
                $page->setIsHomepage(true);
                $this->entityManager->flush();

                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'isHomepage' => true,
                    ]);
                }

                $this->addFlash('success', sprintf('Page "%s" set as homepage.', $page->getTitle()));
            } else {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'error' => 'Cannot unset homepage'], 400);
                }
                $this->addFlash('error', 'Cannot unset homepage. Set another page as homepage first.');
            }
        } else {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 400);
            }
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_page_index');
    }
}
