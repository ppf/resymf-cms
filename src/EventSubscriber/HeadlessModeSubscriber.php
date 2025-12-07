<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Repository\SettingsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Blocks CMS frontend routes when headless mode is enabled.
 *
 * When headless mode is active, only admin and API routes are accessible.
 * Frontend routes (cms_homepage, cms_page) return 404.
 */
class HeadlessModeSubscriber implements EventSubscriberInterface
{
    private const CMS_ROUTES = ['cms_homepage', 'cms_page'];

    public function __construct(
        private readonly SettingsRepository $settingsRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');

        if (!in_array($route, self::CMS_ROUTES, true)) {
            return;
        }

        try {
            $settings = $this->settingsRepository->getSettings();

            if ($settings->isHeadlessMode()) {
                $event->setResponse(new Response(
                    'Frontend disabled - headless mode active',
                    Response::HTTP_NOT_FOUND,
                ));
            }
        } catch (\Exception) {
            // If settings can't be loaded, allow request to continue
        }
    }
}
