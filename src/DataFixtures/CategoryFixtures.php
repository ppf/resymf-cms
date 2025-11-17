<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Category Fixtures.
 *
 * Creates sample content categories for testing
 * Load order: 2 (before PageFixtures)
 */
class CategoryFixtures extends Fixture
{
    public const CATEGORY_NEWS = 'category-news';
    public const CATEGORY_BLOG = 'category-blog';
    public const CATEGORY_DOCS = 'category-docs';
    public const CATEGORY_PROJECTS = 'category-projects';

    public function load(ObjectManager $manager): void
    {
        // News category
        $news = new Category();
        $news->setName('News');
        $news->setDescription('Latest news and announcements');
        $news->setSlug('news');
        $news->setDisplayOrder(1);
        $news->setIsActive(true);
        $manager->persist($news);
        $this->addReference(self::CATEGORY_NEWS, $news);

        // Blog category
        $blog = new Category();
        $blog->setName('Blog');
        $blog->setDescription('Blog posts and articles');
        $blog->setSlug('blog');
        $blog->setDisplayOrder(2);
        $blog->setIsActive(true);
        $manager->persist($blog);
        $this->addReference(self::CATEGORY_BLOG, $blog);

        // Documentation category
        $docs = new Category();
        $docs->setName('Documentation');
        $docs->setDescription('Technical documentation and guides');
        $docs->setSlug('documentation');
        $docs->setDisplayOrder(3);
        $docs->setIsActive(true);
        $manager->persist($docs);
        $this->addReference(self::CATEGORY_DOCS, $docs);

        // Projects category
        $projects = new Category();
        $projects->setName('Projects');
        $projects->setDescription('Featured projects and case studies');
        $projects->setSlug('projects');
        $projects->setDisplayOrder(4);
        $projects->setIsActive(true);
        $manager->persist($projects);
        $this->addReference(self::CATEGORY_PROJECTS, $projects);

        // Inactive category (for testing)
        $inactive = new Category();
        $inactive->setName('Archived');
        $inactive->setDescription('Archived content - no longer visible');
        $inactive->setSlug('archived');
        $inactive->setDisplayOrder(99);
        $inactive->setIsActive(false);
        $manager->persist($inactive);

        $manager->flush();
    }
}
