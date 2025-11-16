<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Page;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Functional tests for CMS Frontend (public pages)
 */
class CmsFrontendTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get('doctrine.orm.entity_manager');

        // Create database schema
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createSchema(): void
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createAuthor(): User
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setUsername('author');
        $user->setEmail('author@example.com');
        $user->setFirstName('John');
        $user->setLastName('Author');
        $user->setPassword($hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createSettings(): Settings
    {
        $settings = new Settings();
        $settings->setSiteName('Test CMS Site');
        $settings->setSiteDescription('A test CMS website');
        $settings->setSiteKeywords('test, cms, symfony');
        $settings->setAdminEmail('admin@test.com');
        $settings->setContactEmail('contact@test.com');
        $settings->setTimezone('UTC');
        $settings->setLocale('en');
        $settings->setMaintenanceMode(false);

        $this->entityManager->persist($settings);
        $this->entityManager->flush();

        return $settings;
    }

    private function createPublishedPage(string $title, string $slug, bool $isHomepage = false): Page
    {
        $author = $this->createAuthor();
        $this->createSettings();

        $page = new Page();
        $page->setTitle($title);
        $page->setSlug($slug);
        $page->setContent('<p>This is the content of ' . $title . '</p>');
        $page->setMetaDescription('Meta description for ' . $title);
        $page->setMetaKeywords('test, page');
        $page->setAuthor($author);
        $page->setIsPublished(true);
        $page->setIsHomepage($isHomepage);
        $page->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return $page;
    }

    public function testHomepageRendersCorrectly(): void
    {
        $client = static::createClient();
        $page = $this->createPublishedPage('Home Page', 'home', true);

        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Home Page');
        $this->assertSelectorTextContains('body', 'This is the content of Home Page');

        // Check SEO meta tags
        $this->assertSelectorExists('meta[name="description"]');
        $this->assertSelectorExists('meta[name="keywords"]');
    }

    public function testHomepageWithoutSetHomepage(): void
    {
        $client = static::createClient();

        // Create published pages but none marked as homepage
        $this->createPublishedPage('First Page', 'first-page', false);
        $this->createPublishedPage('Second Page', 'second-page', false);

        $crawler = $client->request('GET', '/');

        // Should show first published page
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'First Page');
    }

    public function testHomepageWithNoPagesReturns404(): void
    {
        $client = static::createClient();
        $this->createSettings(); // Create settings but no pages

        $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCustomPageRendersCorrectly(): void
    {
        $client = static::createClient();
        $page = $this->createPublishedPage('About Us', 'about-us');

        $crawler = $client->request('GET', '/about-us');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'About Us');
        $this->assertSelectorTextContains('body', 'This is the content of About Us');
    }

    public function testNonPublishedPageReturns404(): void
    {
        $client = static::createClient();

        $author = $this->createAuthor();
        $this->createSettings();

        // Create unpublished page
        $page = new Page();
        $page->setTitle('Draft Page');
        $page->setSlug('draft-page');
        $page->setContent('<p>Draft content</p>');
        $page->setAuthor($author);
        $page->setIsPublished(false); // Not published

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client->request('GET', '/draft-page');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testFuturePublishedPageReturns404(): void
    {
        $client = static::createClient();

        $author = $this->createAuthor();
        $this->createSettings();

        // Create page scheduled for future publication
        $page = new Page();
        $page->setTitle('Future Page');
        $page->setSlug('future-page');
        $page->setContent('<p>Future content</p>');
        $page->setAuthor($author);
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable('+1 week')); // Future date

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $client->request('GET', '/future-page');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNonExistentPageReturns404(): void
    {
        $client = static::createClient();
        $this->createSettings();

        $client->request('GET', '/non-existent-page');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPageViewCountIncreases(): void
    {
        $client = static::createClient();
        $page = $this->createPublishedPage('Popular Page', 'popular-page');

        $initialViewCount = $page->getViewCount();

        // Visit the page
        $client->request('GET', '/popular-page');
        $this->assertResponseIsSuccessful();

        // Refresh page entity from database
        $this->entityManager->refresh($page);

        $this->assertEquals($initialViewCount + 1, $page->getViewCount());

        // Visit again
        $client->request('GET', '/popular-page');
        $this->entityManager->refresh($page);

        $this->assertEquals($initialViewCount + 2, $page->getViewCount());
    }

    public function testPageMetaTagsAreRendered(): void
    {
        $client = static::createClient();
        $page = $this->createPublishedPage('SEO Page', 'seo-page');

        $crawler = $client->request('GET', '/seo-page');

        $this->assertResponseIsSuccessful();

        // Check meta description
        $metaDescription = $crawler->filter('meta[name="description"]')->attr('content');
        $this->assertEquals('Meta description for SEO Page', $metaDescription);

        // Check meta keywords
        $metaKeywords = $crawler->filter('meta[name="keywords"]')->attr('content');
        $this->assertEquals('test, page', $metaKeywords);
    }

    public function testPageWithCategoriesRendersCorrectly(): void
    {
        $client = static::createClient();

        $author = $this->createAuthor();
        $this->createSettings();

        // Create categories
        $category1 = new Category();
        $category1->setName('Technology');
        $category1->setSlug('technology');
        $this->entityManager->persist($category1);

        $category2 = new Category();
        $category2->setName('Programming');
        $category2->setSlug('programming');
        $this->entityManager->persist($category2);

        // Create page with categories
        $page = new Page();
        $page->setTitle('Tech Article');
        $page->setSlug('tech-article');
        $page->setContent('<p>Technology content</p>');
        $page->setAuthor($author);
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable());
        $page->addCategory($category1);
        $page->addCategory($category2);

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $crawler = $client->request('GET', '/tech-article');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tech Article');

        // Check that categories are displayed (if template shows them)
        // This depends on template implementation
        $html = $crawler->html();
        $this->assertStringContainsString('Technology', $html);
        $this->assertStringContainsString('Programming', $html);
    }

    public function testPageContentIsRendered(): void
    {
        $client = static::createClient();

        $author = $this->createAuthor();
        $this->createSettings();

        // Create page with rich HTML content
        $page = new Page();
        $page->setTitle('Rich Content Page');
        $page->setSlug('rich-content');
        $page->setContent('<h2>Section Title</h2><p>Paragraph with <strong>bold</strong> and <em>italic</em> text.</p><ul><li>List item 1</li><li>List item 2</li></ul>');
        $page->setAuthor($author);
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $crawler = $client->request('GET', '/rich-content');

        $this->assertResponseIsSuccessful();

        // Check that HTML content is rendered
        $this->assertSelectorExists('h2:contains("Section Title")');
        $this->assertSelectorExists('strong:contains("bold")');
        $this->assertSelectorExists('em:contains("italic")');
        $this->assertSelectorExists('ul li:contains("List item 1")');
    }

    public function testMultiplePagesCanBeAccessed(): void
    {
        $client = static::createClient();

        $this->createPublishedPage('Home', 'home', true);
        $this->createPublishedPage('About', 'about');
        $this->createPublishedPage('Contact', 'contact');
        $this->createPublishedPage('Services', 'services');

        // Test accessing all pages
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/about');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/contact');
        $this->assertResponseIsSuccessful();

        $client->request('GET', '/services');
        $this->assertResponseIsSuccessful();
    }

    public function testNestedSlugRouting(): void
    {
        $client = static::createClient();

        $author = $this->createAuthor();
        $this->createSettings();

        // Create page with nested slug
        $page = new Page();
        $page->setTitle('Nested Page');
        $page->setSlug('blog/2024/my-post');
        $page->setContent('<p>Nested content</p>');
        $page->setAuthor($author);
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        $crawler = $client->request('GET', '/blog/2024/my-post');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Nested Page');
    }

    public function testSiteSettingsAreAvailableInTemplate(): void
    {
        $client = static::createClient();

        $settings = $this->createSettings();
        $settings->setSiteName('My Custom Site Name');
        $this->entityManager->flush();

        $page = $this->createPublishedPage('Test Page', 'test-page');

        $crawler = $client->request('GET', '/test-page');

        $this->assertResponseIsSuccessful();

        // Check that site name is in the page (usually in title or header)
        $html = $crawler->html();
        $this->assertStringContainsString('My Custom Site Name', $html);
    }
}
