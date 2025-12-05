<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate limiting listener for API endpoints.
 *
 * Applies rate limiting to prevent API abuse and brute force attacks.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class ApiRateLimitListener
{
    public function __construct(
        private readonly RateLimiterFactory $apiLimiter,
        private readonly RateLimiterFactory $userCreationLimiter,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only rate limit API endpoints
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        $clientIp = $request->getClientIp() ?? 'unknown';

        // Stricter rate limiting for user creation endpoint
        if ($path === '/api/users' && $request->getMethod() === 'POST') {
            $limiter = $this->userCreationLimiter->create($clientIp);
            $limit = $limiter->consume();

            if (!$limit->isAccepted()) {
                $event->setResponse(new JsonResponse([
                    'error' => 'Too many user creation requests. Please try again later.',
                    'retry_after' => $limit->getRetryAfter()->getTimestamp() - time(),
                ], Response::HTTP_TOO_MANY_REQUESTS, [
                    'Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                    'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                    'X-RateLimit-Limit' => $limit->getLimit(),
                ]));

                return;
            }
        }

        // General API rate limiting
        $limiter = $this->apiLimiter->create($clientIp);
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $event->setResponse(new JsonResponse([
                'error' => 'Too many requests. Please slow down.',
                'retry_after' => $limit->getRetryAfter()->getTimestamp() - time(),
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                'X-RateLimit-Limit' => $limit->getLimit(),
            ]));
        }
    }
}
