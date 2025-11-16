<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Theme;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Repository\ThemeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for Content Management System
 *
 * Tests Theme, Category, and Page entities with their relationships
 */
class ContentManagementTest extends WebTestCase
{
    private $client;
    private UserRepository $userRepository;
    private ThemeRepository $themeRepository;
    private CategoryRepository $categoryRepository;
    private PageRepository $pageRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->userRepository = $container->get(UserRepository::class);
        $this->themeRepository = $container->get(ThemeRepository::class);
        $this->categoryRepository = $container->get(CategoryRepository::class);
        $this->pageRepository = $container->get(PageRepository::class);
    }

    public function testThemeRepository(): void
    {
        // Test finding default theme
        $defaultTheme = $this->themeRepository->findDefault();
        $this->assertInstanceOf(Theme::class, $defaultTheme);
        $this->assertTrue($defaultTheme->getIsDefault());
        $this->assertTrue($defaultTheme->getIsActive());

        // Test finding all active themes
        $activeThemes = $this->themeRepository->findActive();
        $this->assertGreaterThanOrEqual(3, count($activeThemes));
        foreach ($activeThemes as $theme) {
            $this->assertTrue($theme->getIsActive());
        }

        // Test finding theme by name
        $darkTheme = $this->themeRepository->findByName('Dark Mode');
        $this->assertInstanceOf(Theme::class, $darkTheme);
        $this->assertEquals('Dark Mode', $darkTheme->getName());

        // Test counting
        $totalCount = $this->themeRepository->countAll();
        $activeCount = $this->themeRepository->countActive();
        $this->assertGreaterThanOrEqual($activeCount, $totalCount);
    }

    public function testCategoryRepository(): void
    {
        // Test finding category by slug
        $newsCategory = $this->categoryRepository->findBySlug('news');
        $this->assertInstanceOf(Category::class, $newsCategory);
        $this->assertEquals('News', $newsCategory->getName());

        // Test finding all active categories
        $activeCategories = $this->categoryRepository->findActive();
        $this->assertGreaterThanOrEqual(4, count($activeCategories));

        // Test display order
        $orderedCategories = $this->categoryRepository->findOrdered();
        $this->assertGreaterThanOrEqual(4, count($orderedCategories));
        // First category should have lower display order
        $this->assertLessThanOrEqual(
            $orderedCategories[1]->getDisplayOrder(),
            $orderedCategories[0]->getDisplayOrder()
        );

        // Test search
        $searchResults = $this->categoryRepository->search('blog');
        $this->assertGreaterThanOrEqual(1, count($searchResults));
    }

    public function testPageRepository(): void
    {
        // Test finding homepage
        $homepage = $this->pageRepository->findHomepage();
        $this->assertInstanceOf(Page::class, $homepage);
        $this->assertTrue($homepage->getIsHomepage());
        $this->assertTrue($homepage->getIsPublished());

        // Test finding page by slug
        $aboutPage = $this->pageRepository->findBySlug('about');
        $this->assertInstanceOf(Page::class, $aboutPage);
        $this->assertEquals('About Us', $aboutPage->getTitle());

        // Test finding published pages
        $publishedPages = $this->pageRepository->findPublished();
        $this->assertGreaterThanOrEqual(3, count($publishedPages));
        foreach ($publishedPages as $page) {
            $this->assertTrue($page->getIsPublished());
        }

        // Test finding recent pages
        $recentPages = $this->pageRepository->findRecent(5);
        $this->assertLessThanOrEqual(5, count($recentPages));

        // Test counting
        $totalCount = $this->pageRepository->countAll();
        $publishedCount = $this->pageRepository->countPublished();
        $this->assertGreaterThanOrEqual($publishedCount, $totalCount);
    }

    public function testPageCategoryRelationship(): void
    {
        // Get a page with categories
        $newsPage = $this->pageRepository->findBySlug('news/phase-3-complete');
        $this->assertInstanceOf(Page::class, $newsPage);

        // Check it has categories
        $categories = $newsPage->getCategories();
        $this->assertGreaterThanOrEqual(1, $categories->count());

        // Get a category
        $newsCategory = $this->categoryRepository->findBySlug('news');
        $this->assertInstanceOf(Category::class, $newsCategory);

        // Check category has pages
        $pages = $newsCategory->getPages();
        $this->assertGreaterThanOrEqual(1, $pages->count());

        // Find pages by category
        $categoryPages = $this->pageRepository->findByCategory($newsCategory);
        $this->assertGreaterThanOrEqual(1, count($categoryPages));
    }

    public function testPageAuthorRelationship(): void
    {
        // Get admin user
        $admin = $this->userRepository->findByUsername('admin');
        $this->assertInstanceOf(User::class, $admin);

        // Check user has authored pages
        $authoredPages = $admin->getAuthoredPages();
        $this->assertGreaterThanOrEqual(3, $authoredPages->count());

        // Find pages by author
        $userPages = $this->pageRepository->findByAuthor($admin);
        $this->assertGreaterThanOrEqual(3, count($userPages));

        // Check each page has correct author
        foreach ($userPages as $page) {
            $this->assertEquals($admin->getId(), $page->getAuthor()->getId());
        }
    }

    public function testUserThemeRelationship(): void
    {
        // Get default theme
        $defaultTheme = $this->themeRepository->findDefault();
        $this->assertInstanceOf(Theme::class, $defaultTheme);

        // Create a test user with theme
        $testUser = $this->userRepository->findByUsername('testuser');
        $this->assertInstanceOf(User::class, $testUser);

        // Assign theme to user
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $testUser->setTheme($defaultTheme);
        $entityManager->flush();

        // Reload user and verify theme
        $entityManager->clear();
        $reloadedUser = $this->userRepository->findByUsername('testuser');
        $this->assertInstanceOf(Theme::class, $reloadedUser->getTheme());
        $this->assertEquals($defaultTheme->getId(), $reloadedUser->getTheme()->getId());

        // Check theme has users
        $reloadedTheme = $this->themeRepository->find($defaultTheme->getId());
        $this->assertGreaterThanOrEqual(1, $reloadedTheme->getUsers()->count());
    }

    public function testPageVisibility(): void
    {
        // Published page should be visible
        $publishedPage = $this->pageRepository->findBySlug('about');
        $this->assertTrue($publishedPage->isVisible());

        // Unpublished page should not be visible
        $draftPage = $this->pageRepository->findBySlug('news/future-feature');
        $this->assertFalse($draftPage->isVisible());

        // Future scheduled page should not be visible yet
        $scheduledPage = $this->pageRepository->findBySlug('blog/scheduled-post');
        $this->assertFalse($scheduledPage->isVisible());
    }

    public function testPageExcerpt(): void
    {
        $page = $this->pageRepository->findHomepage();
        $excerpt = $page->getExcerpt(50);

        $this->assertLessThanOrEqual(53, strlen($excerpt)); // 50 + '...'
        $this->assertStringNotContainsString('<', $excerpt); // No HTML tags
    }

    public function testPageSearch(): void
    {
        // Search for pages
        $results = $this->pageRepository->search('Symfony', true);
        $this->assertGreaterThanOrEqual(1, count($results));

        // All results should be published
        foreach ($results as $page) {
            $this->assertTrue($page->getIsPublished());
        }
    }

    public function testCategoryPageCount(): void
    {
        $newsCategory = $this->categoryRepository->findBySlug('news');

        // Get page count
        $pageCount = $newsCategory->getPageCount();
        $this->assertGreaterThanOrEqual(1, $pageCount);

        // Verify with repository method
        $categoryPages = $this->pageRepository->findByCategory($newsCategory, false); // Include unpublished
        $this->assertEquals(count($categoryPages), $pageCount);
    }

    public function testPageViewCount(): void
    {
        $page = $this->pageRepository->findHomepage();
        $initialCount = $page->getViewCount();

        // Increment view count
        $page->incrementViewCount();
        $this->assertEquals($initialCount + 1, $page->getViewCount());

        // Persist and verify
        $entityManager = static::getContainer()->get('doctrine')->getManager();
        $entityManager->flush();

        $entityManager->clear();
        $reloadedPage = $this->pageRepository->findHomepage();
        $this->assertEquals($initialCount + 1, $reloadedPage->getViewCount());
    }

    public function testThemeColorValidation(): void
    {
        $theme = $this->themeRepository->findDefault();

        // Valid hex colors should be accepted
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $theme->getPrimaryColor());
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $theme->getSecondaryColor());
    }

    public function testCategorySlugUniqueness(): void
    {
        $categories = $this->categoryRepository->findAll();
        $slugs = [];

        foreach ($categories as $category) {
            $this->assertNotContains($category->getSlug(), $slugs, 'Duplicate slug found: ' . $category->getSlug());
            $slugs[] = $category->getSlug();
        }
    }

    public function testPageSlugUniqueness(): void
    {
        $pages = $this->pageRepository->findAll();
        $slugs = [];

        foreach ($pages as $page) {
            $this->assertNotContains($page->getSlug(), $slugs, 'Duplicate slug found: ' . $page->getSlug());
            $slugs[] = $page->getSlug();
        }
    }
}
