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
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Page $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
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
     */
    public function findPublishedBySlug(string $slug): ?Page
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.slug = :slug')
            ->andWhere('p.isPublished = :published')
            ->setParameter('slug', $slug)
            ->setParameter('published', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all published pages.
     *
     * @return Page[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find homepage page.
     */
    public function findHomepage(): ?Page
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isHomepage = :homepage')
            ->andWhere('p.isPublished = :published')
            ->setParameter('homepage', true)
            ->setParameter('published', true)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find pages by category.
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
            $qb->andWhere('p.isPublished = :published')
                ->setParameter('published', true);
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
     *
     * @return Page[]
     */
    public function findRecent(int $limit = 10, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $qb->andWhere('p.isPublished = :published')
                ->setParameter('published', true);
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find popular pages by view count.
     *
     * @return Page[]
     */
    public function findPopular(int $limit = 10, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $qb->andWhere('p.isPublished = :published')
                ->setParameter('published', true);
        }

        return $qb->orderBy('p.viewCount', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Search pages by title or content.
     *
     * @return Page[]
     */
    public function search(string $query, bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :query OR p.content LIKE :query OR p.metaDescription LIKE :query')
            ->setParameter('query', '%' . $query . '%');

        if ($publishedOnly) {
            $qb->andWhere('p.isPublished = :published')
                ->setParameter('published', true);
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
     */
    public function countPublished(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find pages with pagination.
     *
     * @return Page[]
     */
    public function findPaginated(int $page = 1, int $limit = 20, bool $publishedOnly = false): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $qb->andWhere('p.isPublished = :published')
                ->setParameter('published', true);
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
     *
     * @return Page[]
     */
    public function findOrdered(bool $publishedOnly = true): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($publishedOnly) {
            $qb->andWhere('p.isPublished = :published')
                ->setParameter('published', true);
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
     */
    public function findFirstPublished(): ?Page
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.displayOrder', 'ASC')
            ->addOrderBy('p.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
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
}
