<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Page;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Category entity.
 */
class CategoryTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $category = new Category();

        $this->assertNull($category->getId());
        $this->assertTrue($category->getIsActive());
        $this->assertEquals(0, $category->getDisplayOrder());
        $this->assertInstanceOf(\DateTimeImmutable::class, $category->getCreatedAt());
        $this->assertNull($category->getUpdatedAt());
        $this->assertCount(0, $category->getPages());
    }

    public function testNameGetterAndSetter(): void
    {
        $category = new Category();
        $category->setName('Technology');

        $this->assertEquals('Technology', $category->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $category = new Category();

        $this->assertNull($category->getDescription());

        $category->setDescription('Tech articles');

        $this->assertEquals('Tech articles', $category->getDescription());
    }

    public function testSlugGetterAndSetter(): void
    {
        $category = new Category();
        $category->setSlug('technology');

        $this->assertEquals('technology', $category->getSlug());
    }

    public function testDisplayOrderGetterAndSetter(): void
    {
        $category = new Category();
        $category->setDisplayOrder(10);

        $this->assertEquals(10, $category->getDisplayOrder());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $category = new Category();

        $this->assertTrue($category->getIsActive());

        $category->setIsActive(false);

        $this->assertFalse($category->getIsActive());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $category = new Category();
        $date = new \DateTimeImmutable('2024-01-01 12:00:00');
        $category->setCreatedAt($date);

        $this->assertEquals($date, $category->getCreatedAt());
    }

    public function testUpdatedAtInitiallyNull(): void
    {
        $category = new Category();

        $this->assertNull($category->getUpdatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $category = new Category();
        $before = new \DateTimeImmutable();

        $category->setUpdatedAt();

        $after = new \DateTimeImmutable();

        $this->assertNotNull($category->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before, $category->getUpdatedAt());
        $this->assertLessThanOrEqual($after, $category->getUpdatedAt());
    }

    public function testPagesCollection(): void
    {
        $category = new Category();

        $this->assertCount(0, $category->getPages());
    }

    public function testAddPage(): void
    {
        $category = new Category();
        $page = $this->createMock(Page::class);

        $page->expects($this->once())
            ->method('addCategory')
            ->with($category);

        $category->addPage($page);

        $this->assertCount(1, $category->getPages());
        $this->assertTrue($category->getPages()->contains($page));
    }

    public function testAddPageDoesNotDuplicate(): void
    {
        $category = new Category();
        $page = $this->createMock(Page::class);

        $page->expects($this->once())
            ->method('addCategory');

        $category->addPage($page);
        $category->addPage($page);

        $this->assertCount(1, $category->getPages());
    }

    public function testRemovePage(): void
    {
        $category = new Category();
        $page = $this->createMock(Page::class);

        $page->expects($this->once())
            ->method('addCategory')
            ->with($category);

        $page->expects($this->once())
            ->method('removeCategory')
            ->with($category);

        $category->addPage($page);
        $category->removePage($page);

        $this->assertCount(0, $category->getPages());
    }

    public function testGetPageCount(): void
    {
        $category = new Category();
        $page1 = $this->createMock(Page::class);
        $page2 = $this->createMock(Page::class);

        $page1->method('addCategory');
        $page2->method('addCategory');

        $this->assertEquals(0, $category->getPageCount());

        $category->addPage($page1);
        $this->assertEquals(1, $category->getPageCount());

        $category->addPage($page2);
        $this->assertEquals(2, $category->getPageCount());
    }

    public function testToString(): void
    {
        $category = new Category();
        $category->setName('Technology');

        $this->assertEquals('Technology', (string) $category);
    }

    public function testFluentInterface(): void
    {
        $category = new Category();

        $result = $category
            ->setName('Test')
            ->setSlug('test')
            ->setDescription('Description')
            ->setDisplayOrder(5)
            ->setIsActive(true);

        $this->assertSame($category, $result);
    }
}
