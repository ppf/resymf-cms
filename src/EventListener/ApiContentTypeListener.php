<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates Content-Type header on API mutation requests.
 *
 * Prevents CSRF attacks by requiring application/json Content-Type
 * on POST, PUT, and DELETE requests to API endpoints.
 * Browsers cannot send cross-origin requests with this Content-Type
 * without a CORS preflight, providing an additional layer of CSRF protection.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 12)]
class ApiContentTypeListener
{
    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Only check API endpoints
        if (!str_starts_with($path, '/api/')) {
            return;
        }

        // Only check mutation methods that send a request body
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        // Allow requests with no body (e.g., DELETE without payload)
        $content = $request->getContent();
        if ($content === '' || $content === '{}') {
            return;
        }

        $contentType = $request->headers->get('Content-Type', '');

        // Require application/json Content-Type for API mutations with body
        if (!str_contains($contentType, 'application/json')) {
            $event->setResponse(new JsonResponse([
                'error' => 'Content-Type must be application/json for API requests.',
            ], Response::HTTP_UNSUPPORTED_MEDIA_TYPE));
        }
    }
}
