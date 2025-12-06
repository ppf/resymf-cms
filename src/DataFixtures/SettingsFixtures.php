<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Settings Fixtures.
 *
 * Loads initial site configuration for development and testing
 * Implements singleton pattern - only one Settings record should exist
 */
class SettingsFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create default site settings
        $settings = new Settings();
        $settings->setSiteName('ReSymf CMS');
        $settings->setAdminPanelTitle('ReSymf CMS Admin');
        $settings->setSeoDescription('ReSymf CMS is a powerful and flexible content management system built with Symfony 7, featuring modern architecture, robust security, and an intuitive admin interface.');
        $settings->setSeoKeywords('symfony, cms, content management, php, symfony 7, resymf');
        $settings->setDefaultLocale('en');
        $settings->setHeadlessMode(false);

        $manager->persist($settings);
        $manager->flush();

        // Output confirmation (visible when running fixtures)
        echo "✅ Created site settings:\n";
        echo "   - Site Name: {$settings->getSiteName()}\n";
        echo "   - Admin Panel Title: {$settings->getAdminPanelTitle()}\n";
        echo "   - Default Locale: {$settings->getDefaultLocale()}\n";
        echo '   - Headless Mode: ' . ($settings->isHeadlessMode() ? 'Yes' : 'No') . "\n";
        echo "\n💡 Customize these settings via the admin panel.\n";
    }
}
