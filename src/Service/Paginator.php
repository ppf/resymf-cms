<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

/**
 * Simple pagination service for admin list views
 */
class Paginator
{
    private const DEFAULT_PER_PAGE = 20;

    public function __construct(
        private QueryBuilder $queryBuilder,
        private int $currentPage = 1,
        private int $perPage = self::DEFAULT_PER_PAGE
    ) {
        if ($this->currentPage < 1) {
            $this->currentPage = 1;
        }
    }

    public function paginate(): DoctrinePaginator
    {
        $offset = ($this->currentPage - 1) * $this->perPage;

        $this->queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($this->perPage);

        return new DoctrinePaginator($this->queryBuilder);
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getTotalItems(DoctrinePaginator $paginator): int
    {
        return count($paginator);
    }

    public function getTotalPages(DoctrinePaginator $paginator): int
    {
        $total = $this->getTotalItems($paginator);
        return (int) ceil($total / $this->perPage);
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage(DoctrinePaginator $paginator): bool
    {
        return $this->currentPage < $this->getTotalPages($paginator);
    }

    public function getPreviousPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function getNextPage(): int
    {
        return $this->currentPage + 1;
    }

    /**
     * Get array of pagination data for template
     */
    public function getPaginationData(DoctrinePaginator $paginator): array
    {
        $totalPages = $this->getTotalPages($paginator);

        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total_items' => $this->getTotalItems($paginator),
            'total_pages' => $totalPages,
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->hasNextPage($paginator),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage(),
            'pages' => $this->getPageRange($totalPages),
        ];
    }

    /**
     * Get range of page numbers to display
     * Shows current page +/- 2 pages
     */
    private function getPageRange(int $totalPages): array
    {
        $range = [];
        $start = max(1, $this->currentPage - 2);
        $end = min($totalPages, $this->currentPage + 2);

        // Always show first page
        if ($start > 1) {
            $range[] = 1;
            if ($start > 2) {
                $range[] = '...';
            }
        }

        // Show range around current page
        for ($i = $start; $i <= $end; $i++) {
            $range[] = $i;
        }

        // Always show last page
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $range[] = '...';
            }
            $range[] = $totalPages;
        }

        return $range;
    }

    /**
     * Static factory method for quick pagination
     */
    public static function create(QueryBuilder $qb, int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): self
    {
        return new self($qb, $page, $perPage);
    }
}
