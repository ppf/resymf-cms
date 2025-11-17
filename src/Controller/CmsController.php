<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PageRepository;
use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CMS Frontend Controller
 *
 * Handles public page rendering by slug
 * Implements dynamic routing for CMS pages
 */
class CmsController extends AbstractController
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly SettingsRepository $settingsRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Homepage
     * Shows the page marked as homepage or first published page
     */
    #[Route('/', name: 'cms_homepage', methods: ['GET'])]
    public function homepage(): Response
    {
        // Find the page marked as homepage
        $page = $this->pageRepository->findHomepage();

        if (!$page) {
            // Fallback to first published page if no homepage is set
            $page = $this->pageRepository->findFirstPublished();
        }

        if (!$page) {
            throw $this->createNotFoundException('No homepage found. Please create a page and mark it as homepage.');
        }

        // Increment view count
        $page->incrementViewCount();
        $this->entityManager->flush();

        $settings = $this->settingsRepository->getOrCreate();

        return $this->render('cms/page.html.twig', [
            'page' => $page,
            'settings' => $settings,
            'is_homepage' => true,
        ]);
    }

    /**
     * Dynamic page rendering by slug
     * Catches all remaining routes and tries to find a matching page
     *
     * This route has the lowest priority to avoid conflicting with other routes
     */
    #[Route('/{slug}', name: 'cms_page', methods: ['GET'], priority: -100, requirements: ['slug' => '.+'])]
    public function page(string $slug): Response
    {
        // Find published page by slug
        $page = $this->pageRepository->findPublishedBySlug($slug);

        if (!$page) {
            throw $this->createNotFoundException(sprintf('Page with slug "%s" not found or not published.', $slug));
        }

        // Increment view count
        $page->incrementViewCount();
        $this->entityManager->flush();

        $settings = $this->settingsRepository->getOrCreate();

        return $this->render('cms/page.html.twig', [
            'page' => $page,
            'settings' => $settings,
            'is_homepage' => false,
        ]);
    }
}
