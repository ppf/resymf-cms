<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PasswordResetService
 */
class PasswordResetServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private PasswordResetRequestRepository $repository;
    private PasswordResetService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PasswordResetRequestRepository::class);

        $this->service = new PasswordResetService(
            $this->entityManager,
            $this->repository
        );
    }

    public function testCreatePasswordResetRequest(): void
    {
        $user = $this->createUser();

        $this->repository->expects($this->once())
            ->method('countRecentRequestsForUser')
            ->with($user)
            ->willReturn(0);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PasswordResetRequest::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $request = $this->service->createPasswordResetRequest($user, '127.0.0.1');

        $this->assertInstanceOf(PasswordResetRequest::class, $request);
        $this->assertEquals($user, $request->getUser());
        $this->assertEquals('127.0.0.1', $request->getIpAddress());
        $this->assertNotNull($request->getToken());
        $this->assertNotNull($request->getExpiresAt());
        $this->assertFalse($request->isUsed());
    }

    public function testCreatePasswordResetRequestRateLimiting(): void
    {
        $user = $this->createUser();

        $this->repository->expects($this->once())
            ->method('countRecentRequestsForUser')
            ->with($user)
            ->willReturn(3); // Already 3 requests

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Too many password reset requests');

        $this->service->createPasswordResetRequest($user, '127.0.0.1');
    }

    public function testFindValidPasswordResetRequest(): void
    {
        $token = 'valid-token-123';
        $passwordResetRequest = new PasswordResetRequest();
        $passwordResetRequest->setUser($this->createUser());
        $passwordResetRequest->setToken($token);
        $passwordResetRequest->setExpiresAt(new \DateTimeImmutable('+1 hour'));
        $passwordResetRequest->setIpAddress('127.0.0.1');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($passwordResetRequest);

        $result = $this->service->findValidPasswordResetRequest($token);

        $this->assertEquals($passwordResetRequest, $result);
    }

    public function testFindValidPasswordResetRequestWithInvalidToken(): void
    {
        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => 'invalid-token'])
            ->willReturn(null);

        $result = $this->service->findValidPasswordResetRequest('invalid-token');

        $this->assertNull($result);
    }

    public function testFindValidPasswordResetRequestWithExpiredToken(): void
    {
        $token = 'expired-token-123';
        $passwordResetRequest = new PasswordResetRequest();
        $passwordResetRequest->setUser($this->createUser());
        $passwordResetRequest->setToken($token);
        $passwordResetRequest->setExpiresAt(new \DateTimeImmutable('-1 hour')); // Expired
        $passwordResetRequest->setIpAddress('127.0.0.1');

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($passwordResetRequest);

        $result = $this->service->findValidPasswordResetRequest($token);

        $this->assertNull($result);
    }

    public function testFindValidPasswordResetRequestWithUsedToken(): void
    {
        $token = 'used-token-123';
        $passwordResetRequest = new PasswordResetRequest();
        $passwordResetRequest->setUser($this->createUser());
        $passwordResetRequest->setToken($token);
        $passwordResetRequest->setExpiresAt(new \DateTimeImmutable('+1 hour'));
        $passwordResetRequest->setIpAddress('127.0.0.1');
        $passwordResetRequest->setUsed(true); // Already used

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['token' => $token])
            ->willReturn($passwordResetRequest);

        $result = $this->service->findValidPasswordResetRequest($token);

        $this->assertNull($result);
    }

    public function testMarkAsUsed(): void
    {
        $passwordResetRequest = new PasswordResetRequest();
        $passwordResetRequest->setUser($this->createUser());
        $passwordResetRequest->setToken('token-123');
        $passwordResetRequest->setExpiresAt(new \DateTimeImmutable('+1 hour'));
        $passwordResetRequest->setIpAddress('127.0.0.1');

        $this->assertFalse($passwordResetRequest->isUsed());

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->service->markAsUsed($passwordResetRequest);

        $this->assertTrue($passwordResetRequest->isUsed());
    }

    public function testCleanupExpiredRequests(): void
    {
        $expiredRequest1 = new PasswordResetRequest();
        $expiredRequest2 = new PasswordResetRequest();

        $this->repository->expects($this->once())
            ->method('findExpiredRequests')
            ->willReturn([$expiredRequest1, $expiredRequest2]);

        $this->entityManager->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([$expiredRequest1], [$expiredRequest2]);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $count = $this->service->cleanupExpiredRequests();

        $this->assertEquals(2, $count);
    }

    public function testCleanupExpiredRequestsWithNoExpiredRequests(): void
    {
        $this->repository->expects($this->once())
            ->method('findExpiredRequests')
            ->willReturn([]);

        $this->entityManager->expects($this->never())
            ->method('remove');

        $this->entityManager->expects($this->never())
            ->method('flush');

        $count = $this->service->cleanupExpiredRequests();

        $this->assertEquals(0, $count);
    }

    public function testTokenIsSecure(): void
    {
        $user = $this->createUser();

        $this->repository->expects($this->once())
            ->method('countRecentRequestsForUser')
            ->willReturn(0);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (PasswordResetRequest $request) {
                // Token should be at least 32 characters (hex encoded 16 bytes)
                return strlen($request->getToken()) >= 32;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $request = $this->service->createPasswordResetRequest($user, '127.0.0.1');

        $this->assertGreaterThanOrEqual(32, strlen($request->getToken()));
    }

    public function testExpirationTimeIsOneHour(): void
    {
        $user = $this->createUser();

        $this->repository->expects($this->once())
            ->method('countRecentRequestsForUser')
            ->willReturn(0);

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $beforeCreation = new \DateTimeImmutable();
        $request = $this->service->createPasswordResetRequest($user, '127.0.0.1');
        $afterCreation = new \DateTimeImmutable();

        // Expiration should be approximately 1 hour from now
        $expiresAt = $request->getExpiresAt();
        $expectedExpiration = $beforeCreation->modify('+1 hour');

        // Allow 1 minute tolerance for test execution time
        $this->assertGreaterThanOrEqual(
            $expectedExpiration->getTimestamp() - 60,
            $expiresAt->getTimestamp()
        );
        $this->assertLessThanOrEqual(
            $afterCreation->modify('+1 hour')->getTimestamp() + 60,
            $expiresAt->getTimestamp()
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');
        $user->setIsActive(true);

        return $user;
    }
}
