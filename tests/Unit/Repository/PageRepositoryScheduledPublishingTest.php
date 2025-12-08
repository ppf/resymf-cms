<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Page;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PageRepository scheduled publishing logic.
 *
 * Tests that isVisible() method is properly used by entity for scheduled publishing.
 * The repository query tests require integration tests with a real database.
 */
class PageRepositoryScheduledPublishingTest extends TestCase
{
    /**
     * Test that Page::isVisible() returns false for future scheduled pages.
     * This is the core logic that repository queries should respect.
     */
    public function testPageIsNotVisibleWhenScheduledInFuture(): void
    {
        $page = new Page();
        $page->setTitle('Future Page');
        $page->setSlug('future-page');
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable('+1 day'));

        $this->assertFalse($page->isVisible(), 'Page with future publishedAt should not be visible');
    }

    /**
     * Test that Page::isVisible() returns true for past scheduled pages.
     */
    public function testPageIsVisibleWhenScheduledInPast(): void
    {
        $page = new Page();
        $page->setTitle('Past Page');
        $page->setSlug('past-page');
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable('-1 day'));

        $this->assertTrue($page->isVisible(), 'Page with past publishedAt should be visible');
    }

    /**
     * Test that Page::isVisible() returns true when no publishedAt is set.
     */
    public function testPageIsVisibleWhenNoPublishedAtSet(): void
    {
        $page = new Page();
        $page->setTitle('Immediate Page');
        $page->setSlug('immediate-page');
        $page->setIsPublished(true);
        $page->setPublishedAt(null);

        $this->assertTrue($page->isVisible(), 'Page with null publishedAt should be visible');
    }

    /**
     * Test that Page::isVisible() returns false when not published.
     */
    public function testPageIsNotVisibleWhenNotPublished(): void
    {
        $page = new Page();
        $page->setTitle('Draft Page');
        $page->setSlug('draft-page');
        $page->setIsPublished(false);
        $page->setPublishedAt(null);

        $this->assertFalse($page->isVisible(), 'Unpublished page should not be visible');
    }

    /**
     * Test that Page::isVisible() returns false when not published even with past date.
     */
    public function testPageIsNotVisibleWhenNotPublishedEvenWithPastDate(): void
    {
        $page = new Page();
        $page->setTitle('Draft Past Page');
        $page->setSlug('draft-past');
        $page->setIsPublished(false);
        $page->setPublishedAt(new \DateTimeImmutable('-1 day'));

        $this->assertFalse($page->isVisible(), 'Unpublished page should not be visible even with past date');
    }

    /**
     * Test edge case: publishedAt set to current time should be visible.
     */
    public function testPageIsVisibleWhenPublishedAtIsNow(): void
    {
        $page = new Page();
        $page->setTitle('Now Page');
        $page->setSlug('now-page');
        $page->setIsPublished(true);
        $page->setPublishedAt(new \DateTimeImmutable('now'));

        $this->assertTrue($page->isVisible(), 'Page with publishedAt=now should be visible');
    }

    /**
     * Verify visibility logic truth table.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('visibilityDataProvider')]
    public function testVisibilityLogicMatrix(
        bool $isPublished,
        ?string $publishedAtModifier,
        bool $expectedVisible,
        string $description,
    ): void {
        $page = new Page();
        $page->setTitle('Test Page');
        $page->setSlug('test');
        $page->setIsPublished($isPublished);

        if ($publishedAtModifier !== null) {
            $page->setPublishedAt(new \DateTimeImmutable($publishedAtModifier));
        }

        $this->assertEquals($expectedVisible, $page->isVisible(), $description);
    }

    /**
     * @return array<string, array{bool, ?string, bool, string}>
     */
    public static function visibilityDataProvider(): array
    {
        return [
            'published_no_date' => [true, null, true, 'Published with no date → visible'],
            'published_past_date' => [true, '-1 hour', true, 'Published with past date → visible'],
            'published_future_date' => [true, '+1 hour', false, 'Published with future date → NOT visible'],
            'unpublished_no_date' => [false, null, false, 'Unpublished with no date → NOT visible'],
            'unpublished_past_date' => [false, '-1 hour', false, 'Unpublished with past date → NOT visible'],
            'unpublished_future_date' => [false, '+1 hour', false, 'Unpublished with future date → NOT visible'],
        ];
    }
}
