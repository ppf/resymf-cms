<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205200415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE resymf_categories (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, slug VARCHAR(128) NOT NULL, display_order INTEGER NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D5E8777C989D9B62 ON resymf_categories (slug)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CATEGORY_NAME ON resymf_categories (name)');
        $this->addSql('CREATE TABLE resymf_pages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, author_id INTEGER DEFAULT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, content CLOB NOT NULL, meta_description VARCHAR(255) DEFAULT NULL, meta_keywords VARCHAR(255) DEFAULT NULL, is_published BOOLEAN NOT NULL, is_homepage BOOLEAN NOT NULL, display_order INTEGER NOT NULL, view_count INTEGER NOT NULL, published_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_6B5FCC94F675F31B FOREIGN KEY (author_id) REFERENCES resymf_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_6B5FCC94F675F31B ON resymf_pages (author_id)');
        $this->addSql('CREATE INDEX IDX_PAGE_PUBLISHED_CREATED ON resymf_pages (is_published, created_at)');
        $this->addSql('CREATE INDEX IDX_PAGE_VIEW_COUNT ON resymf_pages (view_count)');
        $this->addSql('CREATE INDEX IDX_PAGE_DISPLAY_ORDER ON resymf_pages (display_order)');
        $this->addSql('CREATE INDEX IDX_PAGE_HOMEPAGE ON resymf_pages (is_homepage)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PAGE_SLUG ON resymf_pages (slug)');
        $this->addSql('CREATE TABLE resymf_page_categories (page_id INTEGER NOT NULL, category_id INTEGER NOT NULL, PRIMARY KEY(page_id, category_id), CONSTRAINT FK_25717AC2C4663E4 FOREIGN KEY (page_id) REFERENCES resymf_pages (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_25717AC212469DE2 FOREIGN KEY (category_id) REFERENCES resymf_categories (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_25717AC2C4663E4 ON resymf_page_categories (page_id)');
        $this->addSql('CREATE INDEX IDX_25717AC212469DE2 ON resymf_page_categories (category_id)');
        $this->addSql('CREATE TABLE resymf_password_reset_requests (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, user_id INTEGER NOT NULL, token VARCHAR(100) NOT NULL, expires_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , is_used BOOLEAN NOT NULL, ip_address VARCHAR(255) DEFAULT NULL, CONSTRAINT FK_2079041CA76ED395 FOREIGN KEY (user_id) REFERENCES resymf_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2079041C5F37A13B ON resymf_password_reset_requests (token)');
        $this->addSql('CREATE INDEX IDX_2079041CA76ED395 ON resymf_password_reset_requests (user_id)');
        $this->addSql('CREATE TABLE resymf_settings (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, site_name VARCHAR(255) NOT NULL, site_tagline VARCHAR(100) DEFAULT NULL, seo_description CLOB DEFAULT NULL, seo_keywords VARCHAR(255) DEFAULT NULL, admin_email VARCHAR(255) DEFAULT NULL, google_analytics_key VARCHAR(100) DEFAULT NULL, google_tag_manager_key VARCHAR(100) DEFAULT NULL, maintenance_mode BOOLEAN NOT NULL, maintenance_message CLOB DEFAULT NULL, default_locale VARCHAR(10) NOT NULL, timezone VARCHAR(50) NOT NULL, items_per_page INTEGER NOT NULL, registration_enabled BOOLEAN NOT NULL, email_verification_required BOOLEAN NOT NULL, facebook_url VARCHAR(50) DEFAULT NULL, twitter_url VARCHAR(50) DEFAULT NULL, linkedin_url VARCHAR(50) DEFAULT NULL, github_url VARCHAR(50) DEFAULT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE resymf_themes (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, secondary_color VARCHAR(7) DEFAULT NULL, stylesheet VARCHAR(255) DEFAULT NULL, is_active BOOLEAN NOT NULL, is_default BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_THEME_NAME ON resymf_themes (name)');
        $this->addSql('CREATE TABLE resymf_users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, theme_id INTEGER DEFAULT NULL, username VARCHAR(25) NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , updated_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        , CONSTRAINT FK_5FA88C0859027487 FOREIGN KEY (theme_id) REFERENCES resymf_themes (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5FA88C0859027487 ON resymf_users (theme_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USERNAME ON resymf_users (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EMAIL ON resymf_users (email)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , available_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , delivered_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE resymf_categories');
        $this->addSql('DROP TABLE resymf_pages');
        $this->addSql('DROP TABLE resymf_page_categories');
        $this->addSql('DROP TABLE resymf_password_reset_requests');
        $this->addSql('DROP TABLE resymf_settings');
        $this->addSql('DROP TABLE resymf_themes');
        $this->addSql('DROP TABLE resymf_users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
