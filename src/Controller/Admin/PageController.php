<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Page;
use App\Form\PageType;
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
     * Create a new page.
     */
    #[Route('/new', name: 'admin_page_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $page = new Page();

        // Set current user as author
        $page->setAuthor($this->getUser());

        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($page);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Page "%s" has been created successfully.', $page->getTitle()));

            return $this->redirectToRoute('admin_page_index');
        }

        return $this->render('admin/page/new.html.twig', [
            'page' => $page,
            'form' => $form,
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
     * Edit existing page.
     */
    #[Route('/{id}/edit', name: 'admin_page_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Page $page): Response
    {
        $form = $this->createForm(PageType::class, $page);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Page "%s" has been updated successfully.', $page->getTitle()));

            return $this->redirectToRoute('admin_page_index');
        }

        return $this->render('admin/page/edit.html.twig', [
            'page' => $page,
            'form' => $form,
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
        if ($this->isCsrfTokenValid('toggle' . $page->getId(), $request->request->get('_token'))) {
            $page->setIsPublished(!$page->getIsPublished());
            $this->entityManager->flush();

            $status = $page->getIsPublished() ? 'published' : 'unpublished';
            $this->addFlash('success', sprintf('Page "%s" has been %s.', $page->getTitle(), $status));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_page_index');
    }
}
