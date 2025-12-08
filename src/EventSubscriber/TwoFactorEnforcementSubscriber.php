<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\SettingsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Enforces 2FA setup for users when global enforcement is enabled.
 *
 * When Settings.enforce2fa is true, users who haven't set up 2FA
 * will be redirected to the setup page on any admin route access.
 */
class TwoFactorEnforcementSubscriber implements EventSubscriberInterface
{
    /** Routes that should be accessible without 2FA setup */
    private const ALLOWED_ROUTES = [
        '2fa_setup',
        '2fa_login',
        '2fa_login_check',
        'app_logout',
        'app_login',
    ];

    /** Path prefixes that should skip enforcement */
    private const SKIP_PATH_PREFIXES = [
        '/api/',      // API endpoints exempt
        '/_profiler', // Symfony profiler
        '/_wdt',      // Web debug toolbar
    ];

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $path = $request->getPathInfo();

        // Skip allowed routes
        if (in_array($route, self::ALLOWED_ROUTES, true)) {
            return;
        }

        // Skip path prefixes (API, profiler)
        foreach (self::SKIP_PATH_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        // Only enforce on admin routes
        if (!str_starts_with($path, '/admin')) {
            return;
        }

        // Check if user is authenticated
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Check if 2FA is enforced globally
        $settings = $this->settingsRepository->getOrCreate();
        if (!$settings->isEnforce2fa()) {
            return;
        }

        // Check if user has 2FA enabled
        if ($user->isTotpEnabled()) {
            return;
        }

        // Redirect to 2FA setup
        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('2fa_setup'),
        ));
    }
}
