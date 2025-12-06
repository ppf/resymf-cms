<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Simplify Settings entity: remove 15 unused columns, add adminPanelTitle.
 *
 * Removed settings:
 * - siteTagline, adminEmail, googleAnalyticsId, googleTagManagerKey
 * - maintenanceMode, maintenanceMessage, timezone, itemsPerPage
 * - facebookUrl, twitterUrl, linkedinUrl, githubUrl
 * - registrationEnabled, emailVerificationRequired
 *
 * Added: adminPanelTitle (for customizing admin panel navbar title)
 * Added: headlessMode (to disable frontend routes)
 */
final class Version20251206120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Simplify Settings: remove 15 columns, add adminPanelTitle + headlessMode';
    }

    public function up(Schema $schema): void
    {
        // Add new column
        $this->addSql('ALTER TABLE resymf_settings ADD admin_panel_title VARCHAR(255) NOT NULL DEFAULT \'ReSymf CMS Admin\'');
        $this->addSql('ALTER TABLE resymf_settings ADD headless_mode TINYINT(1) NOT NULL DEFAULT 0');

        // Drop unused columns
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN site_tagline');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN admin_email');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN google_analytics_key');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN google_tag_manager_key');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN maintenance_mode');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN maintenance_message');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN timezone');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN items_per_page');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN facebook_url');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN twitter_url');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN linkedin_url');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN github_url');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN registration_enabled');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN email_verification_required');
    }

    public function down(Schema $schema): void
    {
        // Restore dropped columns
        $this->addSql('ALTER TABLE resymf_settings ADD site_tagline VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD admin_email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD google_analytics_key VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD google_tag_manager_key VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD maintenance_mode TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE resymf_settings ADD maintenance_message LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD timezone VARCHAR(50) NOT NULL DEFAULT \'UTC\'');
        $this->addSql('ALTER TABLE resymf_settings ADD items_per_page INT NOT NULL DEFAULT 10');
        $this->addSql('ALTER TABLE resymf_settings ADD facebook_url VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD twitter_url VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD linkedin_url VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD github_url VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_settings ADD registration_enabled TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE resymf_settings ADD email_verification_required TINYINT(1) NOT NULL DEFAULT 0');

        // Drop new columns
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN admin_panel_title');
        $this->addSql('ALTER TABLE resymf_settings DROP COLUMN headless_mode');
    }
}
