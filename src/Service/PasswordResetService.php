<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service for handling password reset functionality.
 *
 * Manages the creation of password reset tokens, validation,
 * and password updates with security best practices.
 */
class PasswordResetService
{
    // Token validity duration (1 hour)
    private const TOKEN_LIFETIME = 3600;

    // Maximum number of active reset requests per user
    private const MAX_ACTIVE_REQUESTS = 3;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PasswordResetRequestRepository $resetRequestRepository,
        private readonly UserRepository $userRepository,
        private readonly EmailService $emailService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Create a password reset request for a user.
     *
     * @param string $email The user's email address
     * @param string|null $ipAddress The requester's IP address
     *
     * @return bool True if reset request was created and email sent
     */
    public function createResetRequest(string $email, ?string $ipAddress = null): bool
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);

        // Always return true to prevent email enumeration
        if ($user === null || !$user->isEnabled()) {
            return true;
        }

        // Check if user has too many active requests
        $activeRequests = $this->resetRequestRepository->countActiveForUser($user);
        if ($activeRequests >= self::MAX_ACTIVE_REQUESTS) {
            // Silently fail to prevent abuse
            return true;
        }

        // Generate secure token
        $token = $this->generateSecureToken();

        // Create reset request
        $resetRequest = new PasswordResetRequest();
        $resetRequest->setUser($user);
        $resetRequest->setToken($token);
        $resetRequest->setExpiresAt(
            (new \DateTimeImmutable())->modify('+' . self::TOKEN_LIFETIME . ' seconds')
        );
        $resetRequest->setIpAddress($ipAddress);

        $this->entityManager->persist($resetRequest);
        $this->entityManager->flush();

        // Generate reset URL
        $resetUrl = $this->urlGenerator->generate(
            'security_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send email
        try {
            $this->emailService->sendPasswordResetEmail(
                $user->getEmail(),
                $user->getUsername(),
                $token,
                $resetUrl
            );
        } catch (\Exception $e) {
            // Log error but don't reveal to user
            // In production, you should log this
            return true;
        }

        return true;
    }

    /**
     * Validate a reset token.
     *
     * @param string $token The reset token
     *
     * @return PasswordResetRequest|null The valid reset request or null
     */
    public function validateToken(string $token): ?PasswordResetRequest
    {
        return $this->resetRequestRepository->findValidByToken($token);
    }

    /**
     * Reset a user's password using a valid token.
     *
     * @param string $token The reset token
     * @param string $newPassword The new password
     *
     * @return bool True if password was reset successfully
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $resetRequest = $this->validateToken($token);

        if ($resetRequest === null) {
            return false;
        }

        $user = $resetRequest->getUser();

        // Hash and set new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);

        // Mark reset request as used
        $resetRequest->setIsUsed(true);

        // Delete all other reset requests for this user
        $this->resetRequestRepository->deleteForUser($user);

        $this->entityManager->flush();

        // Send confirmation email
        try {
            $this->emailService->sendPasswordChangedEmail(
                $user->getEmail(),
                $user->getUsername()
            );
        } catch (\Exception $e) {
            // Log error but password was still changed
        }

        return true;
    }

    /**
     * Clean up expired reset requests.
     *
     * Should be called periodically (e.g., via cron or scheduled task).
     *
     * @return int The number of deleted requests
     */
    public function cleanupExpiredRequests(): int
    {
        return $this->resetRequestRepository->deleteExpired();
    }

    /**
     * Generate a cryptographically secure token.
     *
     * @return string The generated token
     */
    private function generateSecureToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the token lifetime in seconds.
     *
     * @return int Token lifetime in seconds
     */
    public function getTokenLifetime(): int
    {
        return self::TOKEN_LIFETIME;
    }

    /**
     * Check if a user can request a password reset.
     *
     * @param User $user The user to check
     *
     * @return bool True if user can request a reset
     */
    public function canRequestReset(User $user): bool
    {
        if (!$user->isEnabled()) {
            return false;
        }

        $activeRequests = $this->resetRequestRepository->countActiveForUser($user);

        return $activeRequests < self::MAX_ACTIVE_REQUESTS;
    }
}
