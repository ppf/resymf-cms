<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Page;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Page Fixtures
 *
 * Creates sample CMS pages for testing
 * Load order: 3 (depends on UserFixtures and CategoryFixtures)
 */
class PageFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get admin user for authoring pages
        $admin = $this->getReference(UserFixtures::USER_ADMIN);

        // Get categories
        $newsCategory = $this->getReference(CategoryFixtures::CATEGORY_NEWS);
        $blogCategory = $this->getReference(CategoryFixtures::CATEGORY_BLOG);
        $docsCategory = $this->getReference(CategoryFixtures::CATEGORY_DOCS);

        // Homepage
        $homepage = new Page();
        $homepage->setTitle('Welcome to ReSymf CMS');
        $homepage->setSlug('home');
        $homepage->setContent(<<<'HTML'
<h1>Welcome to ReSymf CMS</h1>
<p>This is a modern Symfony 7 CMS built with PHP 8.3. It features:</p>
<ul>
    <li>Content management with categories</li>
    <li>User authentication and authorization</li>
    <li>Theme customization</li>
    <li>SEO-friendly URLs</li>
    <li>Responsive design</li>
</ul>
<p>Get started by exploring the documentation or checking out our latest blog posts.</p>
HTML
        );
        $homepage->setMetaDescription('Modern Symfony 7 CMS - Content management made easy');
        $homepage->setMetaKeywords('symfony, cms, php, content management');
        $homepage->setIsPublished(true);
        $homepage->setIsHomepage(true);
        $homepage->setDisplayOrder(1);
        $homepage->setAuthor($admin);
        $homepage->setPublishedAt(new \DateTimeImmutable('-7 days'));
        $manager->persist($homepage);

        // About page
        $about = new Page();
        $about->setTitle('About Us');
        $about->setSlug('about');
        $about->setContent(<<<'HTML'
<h1>About ReSymf CMS</h1>
<p>ReSymf CMS is a complete migration from a legacy Symfony 2 application to modern Symfony 7.</p>
<h2>Our Mission</h2>
<p>To provide a clean, maintainable, and scalable content management system built on modern standards.</p>
<h2>Technology Stack</h2>
<ul>
    <li>Symfony 7.1.11</li>
    <li>PHP 8.3</li>
    <li>Doctrine ORM 3.5</li>
    <li>MySQL 8.0</li>
    <li>PHPUnit 12</li>
</ul>
HTML
        );
        $about->setMetaDescription('Learn about ReSymf CMS and our technology stack');
        $about->setIsPublished(true);
        $about->setIsHomepage(false);
        $about->setDisplayOrder(2);
        $about->setAuthor($admin);
        $about->setPublishedAt(new \DateTimeImmutable('-6 days'));
        $manager->persist($about);

        // News article 1
        $news1 = new Page();
        $news1->setTitle('Phase 3 Migration Complete!');
        $news1->setSlug('news/phase-3-complete');
        $news1->setContent(<<<'HTML'
<h1>Phase 3 Migration Complete!</h1>
<p><strong>Published on November 16, 2025</strong></p>
<p>We're excited to announce that Phase 3 of our Symfony migration is now complete!</p>
<h2>What's New</h2>
<ul>
    <li>✅ Theme entity with UI customization</li>
    <li>✅ Category entity for content organization</li>
    <li>✅ Page entity with full CMS capabilities</li>
    <li>✅ Comprehensive test coverage</li>
</ul>
<p>This brings us to 30% overall completion of the migration project.</p>
HTML
        );
        $news1->setMetaDescription('Phase 3 migration complete - Theme, Category, and Page entities implemented');
        $news1->setIsPublished(true);
        $news1->setDisplayOrder(10);
        $news1->setAuthor($admin);
        $news1->setPublishedAt(new \DateTimeImmutable());
        $news1->addCategory($newsCategory);
        $news1->addCategory($blogCategory);
        $manager->persist($news1);

        // Documentation page
        $gettingStarted = new Page();
        $gettingStarted->setTitle('Getting Started Guide');
        $gettingStarted->setSlug('docs/getting-started');
        $gettingStarted->setContent(<<<'HTML'
<h1>Getting Started with ReSymf CMS</h1>
<h2>Installation</h2>
<pre><code>cd symfony7-skeleton
composer install
bin/console doctrine:database:create
bin/console doctrine:migrations:migrate
bin/console doctrine:fixtures:load
</code></pre>
<h2>Starting the Server</h2>
<pre><code>symfony server:start
# OR
php -S localhost:8000 -t public/
</code></pre>
<h2>Default Credentials</h2>
<ul>
    <li>Username: <code>admin</code></li>
    <li>Password: <code>admin123</code></li>
</ul>
<h2>Next Steps</h2>
<p>Check out the admin dashboard at <a href="/admin">/admin</a></p>
HTML
        );
        $gettingStarted->setMetaDescription('Quick start guide for ReSymf CMS installation and setup');
        $gettingStarted->setIsPublished(true);
        $gettingStarted->setDisplayOrder(20);
        $gettingStarted->setAuthor($admin);
        $gettingStarted->setPublishedAt(new \DateTimeImmutable('-5 days'));
        $gettingStarted->addCategory($docsCategory);
        $manager->persist($gettingStarted);

        // Unpublished draft page
        $draft = new Page();
        $draft->setTitle('Future Feature Announcement');
        $draft->setSlug('news/future-feature');
        $draft->setContent('<h1>Coming Soon</h1><p>This feature is under development...</p>');
        $draft->setIsPublished(false);
        $draft->setDisplayOrder(50);
        $draft->setAuthor($admin);
        $draft->addCategory($newsCategory);
        $manager->persist($draft);

        // Scheduled future page
        $scheduled = new Page();
        $scheduled->setTitle('Scheduled Post for Next Week');
        $scheduled->setSlug('blog/scheduled-post');
        $scheduled->setContent('<h1>Scheduled Content</h1><p>This will be published next week.</p>');
        $scheduled->setIsPublished(true);
        $scheduled->setPublishedAt(new \DateTimeImmutable('+7 days'));
        $scheduled->setDisplayOrder(60);
        $scheduled->setAuthor($admin);
        $scheduled->addCategory($blogCategory);
        $manager->persist($scheduled);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }
}
