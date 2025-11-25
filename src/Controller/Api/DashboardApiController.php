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
#[IsGranted('ROLE_USER')]
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
        // User statistics
        $totalUsers = $this->userRepository->countAll();
        $activeUsers = $this->userRepository->countActive();
        $recentUsers = $this->userRepository->findRecent(30);

        // Calculate users created in the last 30 days
        $thirtyDaysAgo = new \DateTime('-30 days');
        $newUsersThisMonth = count(array_filter($recentUsers, function ($user) use ($thirtyDaysAgo) {
            return $user->getCreatedAt() >= $thirtyDaysAgo;
        }));

        // Page statistics
        $totalPages = $this->pageRepository->countAll();
        $publishedPages = $this->pageRepository->countPublished();
        $draftPages = $totalPages - $publishedPages;

        // Category statistics
        $totalCategories = $this->categoryRepository->countAll();
        $activeCategories = count($this->categoryRepository->findActive());

        // Recent activity (pages created in last 7 days)
        $recentPages = $this->pageRepository->findRecent(100);
        $sevenDaysAgo = new \DateTime('-7 days');
        $recentActivity = count(array_filter($recentPages, function ($page) use ($sevenDaysAgo) {
            return $page->getCreatedAt() >= $sevenDaysAgo;
        }));

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
