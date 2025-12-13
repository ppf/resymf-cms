<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlertRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlertRule>
 *
 * @method AlertRule|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlertRule|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlertRule[]    findAll()
 * @method AlertRule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlertRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertRule::class);
    }

    /**
     * Find all enabled alert rules.
     *
     * @return AlertRule[]
     */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isEnabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find alert rules by type.
     *
     * @return AlertRule[]
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.type = :type')
            ->andWhere('r.isEnabled = :enabled')
            ->setParameter('type', $type)
            ->setParameter('enabled', true)
            ->orderBy('r.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find alert rules by priority.
     *
     * @return AlertRule[]
     */
    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.priority = :priority')
            ->andWhere('r.isEnabled = :enabled')
            ->setParameter('priority', $priority)
            ->setParameter('enabled', true)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find an alert rule by name.
     */
    public function findByName(string $name): ?AlertRule
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
