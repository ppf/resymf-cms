<?php

declare(strict_types=1);

namespace App\Twig;

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
        return [
            'site_settings' => $this->settingsRepository->getSettings(),
        ];
    }
}
