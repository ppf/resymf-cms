<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Admin Theme CRUD Controller
 *
 * Handles theme management in the admin area
 */
#[Route('/admin/themes')]
#[IsGranted('ROLE_ADMIN')]
class ThemeController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ThemeRepository $themeRepository
    ) {
    }

    /**
     * List all themes
     */
    #[Route('', name: 'admin_theme_index', methods: ['GET'])]
    public function index(): Response
    {
        $themes = $this->themeRepository->findAll();

        return $this->render('admin/theme/index.html.twig', [
            'themes' => $themes,
        ]);
    }

    /**
     * Create a new theme
     */
    #[Route('/new', name: 'admin_theme_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $theme = new Theme();
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If this theme is set as default, unset all other defaults
            if ($theme->getIsDefault()) {
                $this->themeRepository->unsetAllDefaults();
            }

            $this->entityManager->persist($theme);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Theme "%s" has been created successfully.', $theme->getName()));

            return $this->redirectToRoute('admin_theme_index');
        }

        return $this->render('admin/theme/new.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    /**
     * Show theme details
     */
    #[Route('/{id}', name: 'admin_theme_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Theme $theme): Response
    {
        return $this->render('admin/theme/show.html.twig', [
            'theme' => $theme,
        ]);
    }

    /**
     * Edit existing theme
     */
    #[Route('/{id}/edit', name: 'admin_theme_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Theme $theme): Response
    {
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If this theme is set as default, unset all other defaults
            if ($theme->getIsDefault()) {
                $this->themeRepository->unsetAllDefaults();
            }

            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Theme "%s" has been updated successfully.', $theme->getName()));

            return $this->redirectToRoute('admin_theme_index');
        }

        return $this->render('admin/theme/edit.html.twig', [
            'theme' => $theme,
            'form' => $form,
        ]);
    }

    /**
     * Delete theme
     */
    #[Route('/{id}/delete', name: 'admin_theme_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Theme $theme): Response
    {
        if ($this->isCsrfTokenValid('delete' . $theme->getId(), $request->request->get('_token'))) {
            // Prevent deletion of default theme
            if ($theme->getIsDefault()) {
                $this->addFlash('error', 'Cannot delete the default theme.');

                return $this->redirectToRoute('admin_theme_index');
            }

            $name = $theme->getName();
            $this->entityManager->remove($theme);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Theme "%s" has been deleted successfully.', $name));
        } else {
            $this->addFlash('error', 'Invalid CSRF token. Theme was not deleted.');
        }

        return $this->redirectToRoute('admin_theme_index');
    }

    /**
     * Set theme as default
     */
    #[Route('/{id}/set-default', name: 'admin_theme_set_default', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function setDefault(Request $request, Theme $theme): Response
    {
        if ($this->isCsrfTokenValid('set-default' . $theme->getId(), $request->request->get('_token'))) {
            $this->themeRepository->unsetAllDefaults();
            $theme->setIsDefault(true);
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('Theme "%s" has been set as default.', $theme->getName()));
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('admin_theme_index');
    }
}
