<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard')]
#[IsGranted('ROLE_ADMIN')]
class DashboardApiController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PageRepository $pageRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    /**
     * Get dashboard statistics.
     *
     * Returns counts and metrics for the admin dashboard
     */
    #[Route('/stats', name: 'api_dashboard_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        // User statistics - use COUNT queries instead of fetching entities
        $totalUsers = $this->userRepository->countAll();
        $activeUsers = $this->userRepository->countActive();
        $newUsersThisMonth = $this->userRepository->countCreatedSince(new \DateTimeImmutable('-30 days'));

        // Page statistics
        $totalPages = $this->pageRepository->countAll();
        $publishedPages = $this->pageRepository->countPublished();
        $draftPages = $totalPages - $publishedPages;

        // Category statistics - use COUNT query instead of fetching entities
        $totalCategories = $this->categoryRepository->countAll();
        $activeCategories = $this->categoryRepository->countActive();

        // Recent activity (pages created in last 7 days) - use COUNT query
        $recentActivity = $this->pageRepository->countCreatedSince(new \DateTimeImmutable('-7 days'));

        return $this->json([
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'inactive' => $totalUsers - $activeUsers,
                'newThisMonth' => $newUsersThisMonth,
            ],
            'pages' => [
                'total' => $totalPages,
                'published' => $publishedPages,
                'drafts' => $draftPages,
            ],
            'categories' => [
                'total' => $totalCategories,
                'active' => $activeCategories,
                'inactive' => $totalCategories - $activeCategories,
            ],
            'activity' => [
                'recentPages' => $recentActivity,
                'period' => '7 days',
            ],
        ]);
    }
}
