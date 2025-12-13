<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AlertEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlertEvent>
 *
 * @method AlertEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method AlertEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method AlertEvent[]    findAll()
 * @method AlertEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlertEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlertEvent::class);
    }

    /**
     * Find the most recent alert event for a rule with the given dedupe hash.
     */
    public function findLatestByDedupeHash(string $dedupeHash): ?AlertEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dedupeHash = :hash')
            ->setParameter('hash', $dedupeHash)
            ->orderBy('e.triggeredAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find alert events that are currently in cooldown for a specific rule.
     *
     * @return AlertEvent[]
     */
    public function findInCooldown(string $ruleName): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.ruleName = :ruleName')
            ->andWhere('e.cooldownUntil > :now')
            ->setParameter('ruleName', $ruleName)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a rule is currently in cooldown for a specific dedupe hash.
     */
    public function isInCooldown(string $ruleName, ?string $dedupeHash = null): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.ruleName = :ruleName')
            ->andWhere('e.cooldownUntil > :now')
            ->setParameter('ruleName', $ruleName)
            ->setParameter('now', new \DateTimeImmutable());

        if ($dedupeHash !== null) {
            $qb->andWhere('e.dedupeHash = :hash')
                ->setParameter('hash', $dedupeHash);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Find recent alert events.
     *
     * @return AlertEvent[]
     */
    public function findRecent(int $limit = 50): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.triggeredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find alert events by status.
     *
     * @return AlertEvent[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.triggeredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find triggered (unacknowledged) alerts.
     *
     * @return AlertEvent[]
     */
    public function findTriggered(): array
    {
        return $this->findByStatus(AlertEvent::STATUS_TRIGGERED);
    }

    /**
     * Find alert events within a time window for a specific rule.
     *
     * @return AlertEvent[]
     */
    public function findWithinWindow(string $ruleName, \DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.ruleName = :ruleName')
            ->andWhere('e.triggeredAt >= :since')
            ->setParameter('ruleName', $ruleName)
            ->setParameter('since', $since)
            ->orderBy('e.triggeredAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count events by rule name within a time window.
     */
    public function countWithinWindow(string $ruleName, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.ruleName = :ruleName')
            ->andWhere('e.triggeredAt >= :since')
            ->setParameter('ruleName', $ruleName)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Clean up old resolved/suppressed events.
     */
    public function cleanupOldEvents(\DateTimeImmutable $before): int
    {
        return (int) $this->createQueryBuilder('e')
            ->delete()
            ->andWhere('e.triggeredAt < :before')
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('before', $before)
            ->setParameter('statuses', [AlertEvent::STATUS_RESOLVED, AlertEvent::STATUS_SUPPRESSED])
            ->getQuery()
            ->execute();
    }
}
