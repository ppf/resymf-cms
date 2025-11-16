<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Settings Fixtures
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
        $settings->setSiteTagline('A Modern Symfony Content Management System');
        $settings->setSeoDescription('ReSymf CMS is a powerful and flexible content management system built with Symfony 7, featuring modern architecture, robust security, and an intuitive admin interface.');
        $settings->setSeoKeywords('symfony, cms, content management, php, symfony 7, resymf');
        $settings->setAdminEmail('admin@resymf.local');

        // Analytics - disabled by default
        $settings->setGoogleAnalyticsKey(null);
        $settings->setGoogleTagManagerKey(null);

        // Maintenance mode - disabled by default
        $settings->setMaintenanceMode(false);
        $settings->setMaintenanceMessage('We are currently performing scheduled maintenance. Please check back soon.');

        // Locale and timezone
        $settings->setDefaultLocale('en');
        $settings->setTimezone('UTC');

        // Pagination
        $settings->setItemsPerPage(10);

        // User registration settings
        $settings->setRegistrationEnabled(true);
        $settings->setEmailVerificationRequired(false);

        // Social media links - set to null by default
        $settings->setFacebookUrl(null);
        $settings->setTwitterUrl(null);
        $settings->setLinkedinUrl(null);
        $settings->setGithubUrl('https://github.com/resymf/resymf-cms');

        $manager->persist($settings);
        $manager->flush();

        // Output confirmation (visible when running fixtures)
        echo "✅ Created site settings:\n";
        echo "   - Site Name: {$settings->getSiteName()}\n";
        echo "   - Tagline: {$settings->getSiteTagline()}\n";
        echo "   - Default Locale: {$settings->getDefaultLocale()}\n";
        echo "   - Timezone: {$settings->getTimezone()}\n";
        echo "   - Items Per Page: {$settings->getItemsPerPage()}\n";
        echo "   - Registration Enabled: " . ($settings->isRegistrationEnabled() ? 'Yes' : 'No') . "\n";
        echo "   - Maintenance Mode: " . ($settings->isMaintenanceMode() ? 'Yes' : 'No') . "\n";
        echo "\n💡 Customize these settings via the admin panel or config files.\n";
    }
}
