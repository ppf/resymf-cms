<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Paginator;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Paginator service.
 */
class PaginatorTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb);

        $this->assertEquals(1, $paginator->getCurrentPage());
        $this->assertEquals(20, $paginator->getPerPage());
    }

    public function testConstructorWithCustomValues(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, 3, 15);

        $this->assertEquals(3, $paginator->getCurrentPage());
        $this->assertEquals(15, $paginator->getPerPage());
    }

    public function testConstructorNormalizesInvalidPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, -1, 20);

        $this->assertEquals(1, $paginator->getCurrentPage());
    }

    public function testPaginateSetsQueryBuilderParameters(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        // Expect setFirstResult and setMaxResults to be called
        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with(0)
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(20)
            ->willReturn($qb);

        $paginator = new Paginator($qb, 1, 20);
        $result = $paginator->paginate();

        $this->assertInstanceOf(DoctrinePaginator::class, $result);
    }

    public function testPaginateCalculatesCorrectOffset(): void
    {
        $qb = $this->createMock(QueryBuilder::class);

        // Page 3, 20 per page = offset 40
        $qb->expects($this->once())
            ->method('setFirstResult')
            ->with(40)
            ->willReturn($qb);

        $qb->expects($this->once())
            ->method('setMaxResults')
            ->with(20)
            ->willReturn($qb);

        $paginator = new Paginator($qb, 3, 20);
        $paginator->paginate();
    }

    public function testGetTotalItems(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->method('count')->willReturn(100);

        $paginator = new Paginator($qb);
        $total = $paginator->getTotalItems($doctrinePaginator);

        $this->assertEquals(100, $total);
    }

    public function testGetTotalPagesExactDivision(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->method('count')->willReturn(100);

        $paginator = new Paginator($qb, 1, 20);
        $totalPages = $paginator->getTotalPages($doctrinePaginator);

        $this->assertEquals(5, $totalPages);
    }

    public function testGetTotalPagesWithRemainder(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->method('count')->willReturn(105);

        $paginator = new Paginator($qb, 1, 20);
        $totalPages = $paginator->getTotalPages($doctrinePaginator);

        $this->assertEquals(6, $totalPages); // ceil(105/20) = 6
    }

    public function testHasPreviousPageOnFirstPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, 1, 20);

        $this->assertFalse($paginator->hasPreviousPage());
    }

    public function testHasPreviousPageOnSecondPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, 2, 20);

        $this->assertTrue($paginator->hasPreviousPage());
    }

    public function testHasNextPageOnLastPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->method('count')->willReturn(100);

        $paginator = new Paginator($qb, 5, 20);

        $this->assertFalse($paginator->hasNextPage($doctrinePaginator));
    }

    public function testHasNextPageNotOnLastPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->method('count')->willReturn(100);

        $paginator = new Paginator($qb, 3, 20);

        $this->assertTrue($paginator->hasNextPage($doctrinePaginator));
    }

    public function testGetPreviousPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, 3, 20);

        $this->assertEquals(2, $paginator->getPreviousPage());
    }

    public function testGetPreviousPageOnFirstPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, 1, 20);

        $this->assertEquals(1, $paginator->getPreviousPage());
    }

    public function testGetNextPage(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = new Paginator($qb, 3, 20);

        $this->assertEquals(4, $paginator->getNextPage());
    }

    public function testGetPaginationData(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $doctrinePaginator = $this->createMock(DoctrinePaginator::class);
        $doctrinePaginator->method('count')->willReturn(100);

        $paginator = new Paginator($qb, 3, 20);
        $data = $paginator->getPaginationData($doctrinePaginator);

        $this->assertEquals(3, $data['current_page']);
        $this->assertEquals(20, $data['per_page']);
        $this->assertEquals(100, $data['total_items']);
        $this->assertEquals(5, $data['total_pages']);
        $this->assertTrue($data['has_previous']);
        $this->assertTrue($data['has_next']);
        $this->assertEquals(2, $data['previous_page']);
        $this->assertEquals(4, $data['next_page']);
        $this->assertIsArray($data['pages']);
    }

    public function testStaticCreateMethod(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $paginator = Paginator::create($qb, 2, 15);

        $this->assertInstanceOf(Paginator::class, $paginator);
        $this->assertEquals(2, $paginator->getCurrentPage());
        $this->assertEquals(15, $paginator->getPerPage());
    }
}
