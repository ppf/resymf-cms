<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205200932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resymf_categories RENAME INDEX uniq_category_slug TO UNIQ_D5E8777C989D9B62');
        $this->addSql('ALTER TABLE resymf_pages CHANGE author_id author_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_PAGE_PUBLISHED_CREATED ON resymf_pages (is_published, created_at)');
        $this->addSql('CREATE INDEX IDX_PAGE_VIEW_COUNT ON resymf_pages (view_count)');
        $this->addSql('CREATE INDEX IDX_PAGE_DISPLAY_ORDER ON resymf_pages (display_order)');
        $this->addSql('CREATE INDEX IDX_PAGE_HOMEPAGE ON resymf_pages (is_homepage)');
        $this->addSql('ALTER TABLE resymf_pages RENAME INDEX idx_author TO IDX_6B5FCC94F675F31B');
        $this->addSql('ALTER TABLE resymf_page_categories RENAME INDEX idx_page TO IDX_25717AC2C4663E4');
        $this->addSql('ALTER TABLE resymf_page_categories RENAME INDEX idx_category TO IDX_25717AC212469DE2');
        $this->addSql('DROP INDEX IDX_PASS_RESET_EXPIRES ON resymf_password_reset_requests');
        $this->addSql('ALTER TABLE resymf_password_reset_requests CHANGE expires_at expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE is_used is_used TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE resymf_password_reset_requests RENAME INDEX uniq_pass_reset_token TO UNIQ_2079041C5F37A13B');
        $this->addSql('ALTER TABLE resymf_password_reset_requests RENAME INDEX idx_pass_reset_user TO IDX_2079041CA76ED395');
        $this->addSql('ALTER TABLE resymf_users RENAME INDEX idx_user_theme TO IDX_5FA88C0859027487');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resymf_users RENAME INDEX idx_5fa88c0859027487 TO IDX_USER_THEME');
        $this->addSql('ALTER TABLE resymf_password_reset_requests CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE is_used is_used TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('CREATE INDEX IDX_PASS_RESET_EXPIRES ON resymf_password_reset_requests (expires_at)');
        $this->addSql('ALTER TABLE resymf_password_reset_requests RENAME INDEX idx_2079041ca76ed395 TO IDX_PASS_RESET_USER');
        $this->addSql('ALTER TABLE resymf_password_reset_requests RENAME INDEX uniq_2079041c5f37a13b TO UNIQ_PASS_RESET_TOKEN');
        $this->addSql('DROP INDEX IDX_PAGE_PUBLISHED_CREATED ON resymf_pages');
        $this->addSql('DROP INDEX IDX_PAGE_VIEW_COUNT ON resymf_pages');
        $this->addSql('DROP INDEX IDX_PAGE_DISPLAY_ORDER ON resymf_pages');
        $this->addSql('DROP INDEX IDX_PAGE_HOMEPAGE ON resymf_pages');
        $this->addSql('ALTER TABLE resymf_pages CHANGE author_id author_id INT NOT NULL');
        $this->addSql('ALTER TABLE resymf_pages RENAME INDEX idx_6b5fcc94f675f31b TO IDX_AUTHOR');
        $this->addSql('ALTER TABLE resymf_categories RENAME INDEX uniq_d5e8777c989d9b62 TO UNIQ_CATEGORY_SLUG');
        $this->addSql('ALTER TABLE resymf_page_categories RENAME INDEX idx_25717ac212469de2 TO IDX_CATEGORY');
        $this->addSql('ALTER TABLE resymf_page_categories RENAME INDEX idx_25717ac2c4663e4 TO IDX_PAGE');
    }
}
