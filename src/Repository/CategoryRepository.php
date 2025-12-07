<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active categories.
     *
     * @return Category[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.displayOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find categories ordered by display order.
     *
     * @return Category[]
     */
    public function findOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.displayOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find category by name.
     */
    public function findByName(string $name): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count all categories.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count active categories.
     */
    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find categories with page count
     * Returns array with category and page_count.
     */
    public function findWithPageCount(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.pages', 'p')
            ->select('c', 'COUNT(p.id) as page_count')
            ->groupBy('c.id')
            ->orderBy('c.displayOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search categories by name.
     *
     * @return Category[]
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :query OR c.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find categories with pagination.
     *
     * @return Category[]
     */
    public function findPaginated(int $page = 1, int $limit = 20): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.displayOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all categories ordered by display order
     * Alias for findOrdered() for controller consistency.
     *
     * @return Category[]
     */
    public function findAllOrderedByOrder(): array
    {
        return $this->findOrdered();
    }
}
