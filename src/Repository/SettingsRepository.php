<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Settings>
 *
 * Singleton pattern repository for Settings entity
 * Ensures only one Settings record exists in the database
 */
class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }

    /**
     * Save a settings entity.
     */
    public function save(Settings $settings, bool $flush = false): void
    {
        $this->_em->persist($settings);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Remove a settings entity.
     */
    public function remove(Settings $settings, bool $flush = false): void
    {
        $this->_em->remove($settings);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Get the singleton Settings instance
     * Creates default settings if none exist.
     */
    public function getSettings(): Settings
    {
        $settings = $this->find(1);

        if (!$settings) {
            $settings = $this->createDefaultSettings();
            $this->save($settings, true);
        }

        return $settings;
    }

    /**
     * Get or create the Settings instance (alias for getSettings).
     */
    public function getOrCreate(): Settings
    {
        return $this->getSettings();
    }

    /**
     * Update settings with new values.
     */
    public function update(Settings $settings): void
    {
        $this->save($settings, true);
    }

    /**
     * Check if settings exist.
     */
    public function exists(): bool
    {
        return $this->count([]) > 0;
    }

    public function resetToDefaults(): Settings
    {
        $settings = $this->getSettings();

        $settings->setSiteName('ReSymf CMS');
        $settings->setAdminPanelTitle('ReSymf CMS Admin');
        $settings->setSeoDescription('ReSymf CMS - A powerful and flexible content management system built with Symfony');
        $settings->setSeoKeywords(null);
        $settings->setDefaultLocale('en');
        $settings->setHeadlessMode(false);

        $this->save($settings, true);

        return $settings;
    }

    private function createDefaultSettings(): Settings
    {
        $settings = new Settings();
        $settings->setSiteName('ReSymf CMS');
        $settings->setAdminPanelTitle('ReSymf CMS Admin');
        $settings->setSeoDescription('ReSymf CMS - A powerful and flexible content management system built with Symfony');
        $settings->setDefaultLocale('en');
        $settings->setHeadlessMode(false);

        return $settings;
    }
}
