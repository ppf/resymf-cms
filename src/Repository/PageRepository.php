<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Page>
 */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Page::class);
    }

    public function save(Page $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Page $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Add visibility conditions for published pages.
     * Checks both isPublished flag and publishedAt date for scheduled publishing.
     *
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param string $alias Entity alias in the query
     */
    private function addVisibilityConditions($qb, string $alias = 'p'): void
    {
        $qb->andWhere("$alias.isPublished = :published")
           ->andWhere("($alias.publishedAt IS NULL OR $alias.publishedAt <= :now)")
           ->setParameter('published', true)
           ->setParameter('now', new \DateTimeImmutable());
    }

    /**
     * Find page by slug.
     */
    public function findBySlug(string $slug): ?Page
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find published page by slug (public access).
     * Respects scheduled publishing via publishedAt date.
     */
    public function findPublishedBySlug(string $slug): ?Page
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->setParameter('slug', $slug);

        $this->addVisibilityConditions($qb);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all published pages.
     * Respects scheduled publishing via publishedAt date.
     *
     * @return Page[]
     */
    public function findPublished(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC');

        $this->addVisibilityConditions($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find homepage page.
     * Respects scheduled publishing via publishedAt date.
     */
    public function findHomepage(): ?Page
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.isHomepage = :homepage')
            ->setParameter('homepage', true)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1);

        $this->addVisibilityConditions($qb);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find pages by category.
     * Respects scheduled publishing via publishedAt date when publishedOnly=true.
     *
     * @return Page[]
     */
    public function findByCategory(Category $category, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.categories', 'c')
            ->andWhere('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId());

        if ($publishedOnly) {
            $this->addVisibilityConditions($qb);
        }

        return $qb->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pages by author.
     *
     * @return Page[]
     */
    public function findByAuthor(User $author): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.author = :author')
            ->setParameter('author', $author)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent pages.
     * Respects scheduled publishing via publishedAt date when publishedOnly=true.
     *
     * @return Page[]
     */
    public function findRecent(int $limit = 10, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $this->addVisibilityConditions($qb);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find popular pages by view count.
     * Respects scheduled publishing via publishedAt date when publishedOnly=true.
     *
     * @return Page[]
     */
    public function findPopular(int $limit = 10, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $this->addVisibilityConditions($qb);
        }

        return $qb->orderBy('p.viewCount', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search pages by title or content.
     * Respects scheduled publishing via publishedAt date when publishedOnly=true.
     *
     * @return Page[]
     */
    public function search(string $query, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :query OR p.content LIKE :query OR p.metaDescription LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($publishedOnly) {
            $this->addVisibilityConditions($qb);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count all pages.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count published pages.
     * Respects scheduled publishing via publishedAt date.
     */
    public function countPublished(): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        $this->addVisibilityConditions($qb);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find pages with pagination.
     * Includes eager loading for author and categories to prevent N+1 queries.
     * Respects scheduled publishing via publishedAt date when publishedOnly=true.
     *
     * @return Page[]
     */
    public function findPaginated(int $page = 1, int $limit = 20, bool $publishedOnly = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->leftJoin('p.categories', 'c')
            ->addSelect('a', 'c');

        if ($publishedOnly) {
            $this->addVisibilityConditions($qb);
        }

        return $qb->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pages ordered by display order.
     * Respects scheduled publishing via publishedAt date when publishedOnly=true.
     *
     * @return Page[]
     */
    public function findOrdered(bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $this->addVisibilityConditions($qb);
        }

        return $qb->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.title', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all pages ordered by creation date (newest first).
     *
     * @return Page[]
     */
    public function findAllOrderedByCreated(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find first published page (for fallback homepage).
     * Respects scheduled publishing via publishedAt date.
     */
    public function findFirstPublished(): ?Page
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'ASC')
            ->setMaxResults(1);

        $this->addVisibilityConditions($qb);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Unset all homepage flags.
     */
    public function unsetAllHomepages(): void
    {
        $this->createQueryBuilder('p')
            ->update()
            ->set('p.isHomepage', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }

    /**
     * Atomically increment view count for a page.
     * Uses SQL UPDATE to prevent race conditions under high concurrency.
     *
     * @param int $pageId The page ID
     *
     * @return int Number of affected rows (should be 1)
     */
    public function incrementViewCount(int $pageId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->update()
            ->set('p.viewCount', 'p.viewCount + 1')
            ->where('p.id = :id')
            ->setParameter('id', $pageId)
            ->getQuery()
            ->execute();
    }
}
