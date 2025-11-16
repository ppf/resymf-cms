<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Phase 3 Migration: Content Management Entities
 *
 * Creates tables for:
 * - resymf_themes (UI theme configuration)
 * - resymf_categories (content categorization)
 * - resymf_pages (CMS page content)
 * - resymf_page_categories (many-to-many join table)
 *
 * Also adds theme_id foreign key to resymf_users table
 */
final class Version20251116160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: Create Theme, Category, and Page entities with relationships';
    }

    public function up(Schema $schema): void
    {
        // Create resymf_themes table
        $this->addSql('CREATE TABLE resymf_themes (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(50) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            primary_color VARCHAR(7) DEFAULT NULL,
            secondary_color VARCHAR(7) DEFAULT NULL,
            stylesheet VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL,
            is_default TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_THEME_NAME (name),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create resymf_categories table
        $this->addSql('CREATE TABLE resymf_categories (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            slug VARCHAR(128) NOT NULL,
            display_order INT NOT NULL,
            is_active TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_CATEGORY_NAME (name),
            UNIQUE INDEX UNIQ_CATEGORY_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create resymf_pages table
        $this->addSql('CREATE TABLE resymf_pages (
            id INT AUTO_INCREMENT NOT NULL,
            author_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            meta_description VARCHAR(255) DEFAULT NULL,
            meta_keywords VARCHAR(255) DEFAULT NULL,
            is_published TINYINT(1) NOT NULL,
            is_homepage TINYINT(1) NOT NULL,
            display_order INT NOT NULL,
            view_count INT NOT NULL,
            published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_PAGE_SLUG (slug),
            INDEX IDX_AUTHOR (author_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create resymf_page_categories join table
        $this->addSql('CREATE TABLE resymf_page_categories (
            page_id INT NOT NULL,
            category_id INT NOT NULL,
            INDEX IDX_PAGE (page_id),
            INDEX IDX_CATEGORY (category_id),
            PRIMARY KEY(page_id, category_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add theme_id to resymf_users table
        $this->addSql('ALTER TABLE resymf_users ADD theme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_users ADD CONSTRAINT FK_USER_THEME FOREIGN KEY (theme_id) REFERENCES resymf_themes (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_THEME ON resymf_users (theme_id)');

        // Add foreign key constraints
        $this->addSql('ALTER TABLE resymf_pages ADD CONSTRAINT FK_PAGE_AUTHOR FOREIGN KEY (author_id) REFERENCES resymf_users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resymf_page_categories ADD CONSTRAINT FK_PAGE_CATEGORY_PAGE FOREIGN KEY (page_id) REFERENCES resymf_pages (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE resymf_page_categories ADD CONSTRAINT FK_PAGE_CATEGORY_CATEGORY FOREIGN KEY (category_id) REFERENCES resymf_categories (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraints first
        $this->addSql('ALTER TABLE resymf_users DROP FOREIGN KEY FK_USER_THEME');
        $this->addSql('ALTER TABLE resymf_pages DROP FOREIGN KEY FK_PAGE_AUTHOR');
        $this->addSql('ALTER TABLE resymf_page_categories DROP FOREIGN KEY FK_PAGE_CATEGORY_PAGE');
        $this->addSql('ALTER TABLE resymf_page_categories DROP FOREIGN KEY FK_PAGE_CATEGORY_CATEGORY');

        // Drop indexes
        $this->addSql('DROP INDEX IDX_USER_THEME ON resymf_users');

        // Drop theme_id column from users
        $this->addSql('ALTER TABLE resymf_users DROP theme_id');

        // Drop tables
        $this->addSql('DROP TABLE resymf_page_categories');
        $this->addSql('DROP TABLE resymf_pages');
        $this->addSql('DROP TABLE resymf_categories');
        $this->addSql('DROP TABLE resymf_themes');
    }
}
