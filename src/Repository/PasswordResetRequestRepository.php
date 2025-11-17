<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for PasswordResetRequest entity.
 *
 * @extends ServiceEntityRepository<PasswordResetRequest>
 */
class PasswordResetRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetRequest::class);
    }

    /**
     * Find a valid (non-expired, unused) reset request by token.
     *
     * @param string $token The reset token
     *
     * @return PasswordResetRequest|null The reset request or null if not found/invalid
     */
    public function findValidByToken(string $token): ?PasswordResetRequest
    {
        return $this->createQueryBuilder('prr')
            ->where('prr.token = :token')
            ->andWhere('prr.expiresAt > :now')
            ->andWhere('prr.isUsed = :isUsed')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('isUsed', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find the most recent reset request for a user.
     *
     * @param User $user The user
     *
     * @return PasswordResetRequest|null The most recent reset request or null
     */
    public function findMostRecentForUser(User $user): ?PasswordResetRequest
    {
        return $this->createQueryBuilder('prr')
            ->where('prr.user = :user')
            ->setParameter('user', $user)
            ->orderBy('prr.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Delete all expired reset requests.
     *
     * @return int The number of deleted requests
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('prr')
            ->delete()
            ->where('prr.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Delete all reset requests for a specific user.
     *
     * @param User $user The user
     *
     * @return int The number of deleted requests
     */
    public function deleteForUser(User $user): int
    {
        return $this->createQueryBuilder('prr')
            ->delete()
            ->where('prr.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Count active reset requests for a user (non-expired, unused).
     *
     * @param User $user The user
     *
     * @return int The count of active requests
     */
    public function countActiveForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('prr')
            ->select('COUNT(prr.id)')
            ->where('prr.user = :user')
            ->andWhere('prr.expiresAt > :now')
            ->andWhere('prr.isUsed = :isUsed')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('isUsed', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
