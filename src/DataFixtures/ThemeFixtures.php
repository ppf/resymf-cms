<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Theme Fixtures.
 *
 * Creates sample UI themes for testing
 * Load order: 1 (before UserFixtures since users may reference themes)
 */
class ThemeFixtures extends Fixture
{
    public const THEME_DEFAULT = 'theme-default';
    public const THEME_DARK = 'theme-dark';
    public const THEME_BLUE = 'theme-blue';

    public function load(ObjectManager $manager): void
    {
        // Default theme (light)
        $defaultTheme = new Theme();
        $defaultTheme->setName('Default Light');
        $defaultTheme->setDescription('Clean light theme with default colors');
        $defaultTheme->setPrimaryColor('#3498db');
        $defaultTheme->setSecondaryColor('#2ecc71');
        $defaultTheme->setStylesheet('themes/default.css');
        $defaultTheme->setIsActive(true);
        $defaultTheme->setIsDefault(true);
        $manager->persist($defaultTheme);
        $this->addReference(self::THEME_DEFAULT, $defaultTheme);

        // Dark theme
        $darkTheme = new Theme();
        $darkTheme->setName('Dark Mode');
        $darkTheme->setDescription('Modern dark theme for reduced eye strain');
        $darkTheme->setPrimaryColor('#2c3e50');
        $darkTheme->setSecondaryColor('#34495e');
        $darkTheme->setStylesheet('themes/dark.css');
        $darkTheme->setIsActive(true);
        $darkTheme->setIsDefault(false);
        $manager->persist($darkTheme);
        $this->addReference(self::THEME_DARK, $darkTheme);

        // Blue theme
        $blueTheme = new Theme();
        $blueTheme->setName('Ocean Blue');
        $blueTheme->setDescription('Professional blue color scheme');
        $blueTheme->setPrimaryColor('#1e3a8a');
        $blueTheme->setSecondaryColor('#3b82f6');
        $blueTheme->setStylesheet('themes/blue.css');
        $blueTheme->setIsActive(true);
        $blueTheme->setIsDefault(false);
        $manager->persist($blueTheme);
        $this->addReference(self::THEME_BLUE, $blueTheme);

        // Inactive theme (for testing)
        $inactiveTheme = new Theme();
        $inactiveTheme->setName('Legacy Theme');
        $inactiveTheme->setDescription('Deprecated theme - no longer available');
        $inactiveTheme->setPrimaryColor('#95a5a6');
        $inactiveTheme->setSecondaryColor('#7f8c8d');
        $inactiveTheme->setStylesheet('themes/legacy.css');
        $inactiveTheme->setIsActive(false);
        $inactiveTheme->setIsDefault(false);
        $manager->persist($inactiveTheme);

        $manager->flush();
    }
}
