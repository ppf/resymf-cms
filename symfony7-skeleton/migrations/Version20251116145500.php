<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Settings entity migration
 * Creates resymf_settings table for site-wide configuration
 */
final class Version20251116145500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates resymf_settings table for site-wide configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE resymf_settings (
              id INT AUTO_INCREMENT NOT NULL,
              site_name VARCHAR(255) NOT NULL,
              site_tagline VARCHAR(100) DEFAULT NULL,
              seo_description LONGTEXT DEFAULT NULL,
              seo_keywords VARCHAR(255) DEFAULT NULL,
              admin_email VARCHAR(255) DEFAULT NULL,
              google_analytics_key VARCHAR(100) DEFAULT NULL,
              google_tag_manager_key VARCHAR(100) DEFAULT NULL,
              maintenance_mode TINYINT(1) NOT NULL,
              maintenance_message LONGTEXT DEFAULT NULL,
              default_locale VARCHAR(10) NOT NULL,
              timezone VARCHAR(50) NOT NULL,
              items_per_page INT NOT NULL,
              registration_enabled TINYINT(1) NOT NULL,
              email_verification_required TINYINT(1) NOT NULL,
              facebook_url VARCHAR(50) DEFAULT NULL,
              twitter_url VARCHAR(50) DEFAULT NULL,
              linkedin_url VARCHAR(50) DEFAULT NULL,
              github_url VARCHAR(50) DEFAULT NULL,
              created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
              updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
              PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE resymf_settings');
    }
}
