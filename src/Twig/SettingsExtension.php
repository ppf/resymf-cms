<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Settings;
use App\Repository\SettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * Twig extension to provide site settings globally.
 */
class SettingsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
    ) {
    }

    public function getGlobals(): array
    {
        try {
            $settings = $this->settingsRepository->getSettings();
        } catch (\Throwable) {
            // Return default settings if database is unavailable (e.g., during tests)
            $settings = new Settings();
        }

        return [
            'site_settings' => $settings,
        ];
    }
}
