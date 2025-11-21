<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Page entity.
 *
 * Tests page visibility, publication workflow, and business logic.
 */
class PageTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $page = new Page();

        $this->assertNull($page->getId());
        $this->assertFalse($page->getIsPublished());
        $this->assertFalse($page->getIsHomepage());
        $this->assertEquals(0, $page->getDisplayOrder());
        $this->assertEquals(0, $page->getViewCount());
        $this->assertNull($page->getPublishedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $page->getCreatedAt());
        $this->assertNull($page->getUpdatedAt());
        $this->assertCount(0, $page->getCategories());
    }

    public function testTitleGetterAndSetter(): void
    {
        $page = new Page();
        $page->setTitle('Test Page');

        $this->assertEquals('Test Page', $page->getTitle());
    }

    public function testSlugGetterAndSetter(): void
    {
        $page = new Page();
        $page->setSlug('test-page');

        $this->assertEquals('test-page', $page->getSlug());
    }

    public function testContentGetterAndSetter(): void
    {
        $page = new Page();
        $page->setContent('<p>Test content</p>');

        $this->assertEquals('<p>Test content</p>', $page->getContent());
    }

    public function testMetaDescriptionGetterAndSetter(): void
    {
        $page = new Page();

        $this->assertNull($page->getMetaDescription());

        $page->setMetaDescription('Test description');

        $this->assertEquals('Test description', $page->getMetaDescription());
    }

    public function testMetaKeywordsGetterAndSetter(): void
    {
        $page = new Page();

        $this->assertNull($page->getMetaKeywords());

        $page->setMetaKeywords('test, keywords');

        $this->assertEquals('test, keywords', $page->getMetaKeywords());
    }

    public function testIsPublishedGetterAndSetter(): void
    {
        $page = new Page();

        $this->assertFalse($page->getIsPublished());

        $page->setIsPublished(true);

        $this->assertTrue($page->getIsPublished());
    }

    public function testIsHomepageGetterAndSetter(): void
    {
        $page = new Page();

        $this->assertFalse($page->getIsHomepage());

        $page->setIsHomepage(true);

        $this->assertTrue($page->getIsHomepage());
    }

    public function testDisplayOrderGetterAndSetter(): void
    {
        $page = new Page();
        $page->setDisplayOrder(5);

        $this->assertEquals(5, $page->getDisplayOrder());
    }

    public function testViewCountGetterAndSetter(): void
    {
        $page = new Page();
        $page->setViewCount(100);

        $this->assertEquals(100, $page->getViewCount());
    }

    public function testIncrementViewCount(): void
    {
        $page = new Page();

        $this->assertEquals(0, $page->getViewCount());

        $page->incrementViewCount();

        $this->assertEquals(1, $page->getViewCount());

        $page->incrementViewCount();

        $this->assertEquals(2, $page->getViewCount());
    }

    public function testPublishedAtGetterAndSetter(): void
    {
        $page = new Page();
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');

        $this->assertNull($page->getPublishedAt());

        $page->setPublishedAt($date);

        $this->assertEquals($date, $page->getPublishedAt());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $page = new Page();
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $page->setCreatedAt($date);

        $this->assertEquals($date, $page->getCreatedAt());
    }

    public function testUpdatedAtInitiallyNull(): void
    {
        $page = new Page();

        $this->assertNull($page->getUpdatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $page = new Page();
        $before = new \DateTimeImmutable();

        $page->setUpdatedAt();

        $after = new \DateTimeImmutable();

        $this->assertNotNull($page->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before, $page->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $page->getUpdatedAt());
    }

    public function testAuthorGetterAndSetter(): void
    {
        $page = new Page();
        $user = $this->createMock(User::class);

        $page->setAuthor($user);

        $this->assertSame($user, $page->getAuthor());
    }

    public function testCategoriesCollection(): void
    {
        $page = new Page();

        $this->assertCount(0, $page->getCategories());
    }

    public function testAddCategory(): void
    {
        $page = new Page();
        $category = $this->createMock(Category::class);

        $page->addCategory($category);

        $this->assertCount(1, $page->getCategories());
        $this->assertTrue($page->getCategories()->contains($category));
    }

    public function testAddCategoryDoesNotDuplicate(): void
    {
        $page = new Page();
        $category = $this->createMock(Category::class);

        $page->addCategory($category);
        $page->addCategory($category);

        $this->assertCount(1, $page->getCategories());
    }

    public function testRemoveCategory(): void
    {
        $page = new Page();
        $category = $this->createMock(Category::class);

        $page->addCategory($category);
        $page->removeCategory($category);

        $this->assertCount(0, $page->getCategories());
    }

    public function testIsVisibleWhenNotPublished(): void
    {
        $page = new Page();
        $page->setIsPublished(false);

        $this->assertFalse($page->isVisible());
    }

    public function testIsVisibleWhenPublishedNoDate(): void
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setPublishedAt(null);

        $this->assertTrue($page->isVisible());
    }

    public function testIsVisibleWhenPublishedInPast(): void
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable('-1 day'));

        $this->assertTrue($page->isVisible());
    }

    public function testIsVisibleWhenPublishedInFuture(): void
    {
        $page = new Page();
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable('+1 day'));

        $this->assertFalse($page->isVisible());
    }

    public function testGetExcerptShortContent(): void
    {
        $page = new Page();
        $page->setContent('Short content');

        $excerpt = $page->getExcerpt(100);

        $this->assertEquals('Short content', $excerpt);
    }

    public function testGetExcerptLongContent(): void
    {
        $page = new Page();
        $content = str_repeat('A', 300);
        $page->setContent($content);

        $excerpt = $page->getExcerpt(200);

        $this->assertEquals(203, mb_strlen($excerpt)); // 200 + '...'
        $this->assertStringEndsWith('...', $excerpt);
    }

    public function testGetExcerptStripsHtmlTags(): void
    {
        $page = new Page();
        $page->setContent('<p>Test <strong>content</strong> with <em>HTML</em></p>');

        $excerpt = $page->getExcerpt(100);

        $this->assertEquals('Test content with HTML', $excerpt);
        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertStringNotContainsString('<strong>', $excerpt);
    }

    public function testToString(): void
    {
        $page = new Page();
        $page->setTitle('My Page');

        $this->assertEquals('My Page', (string) $page);
    }

    public function testFluentInterface(): void
    {
        $page = new Page();
        $user = $this->createMock(User::class);

        $result = $page
            ->setTitle('Test')
            ->setSlug('test')
            ->setContent('Content')
            ->setIsPublished(true)
            ->setAuthor($user);

        $this->assertSame($page, $result);
    }
}
