<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251206170512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE resymf_cron_jobs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, command VARCHAR(255) NOT NULL, cron_expression VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, last_run_at DATETIME DEFAULT NULL, last_status VARCHAR(20) DEFAULT NULL, last_output LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_CRONJOB_ACTIVE (is_active), UNIQUE INDEX UNIQ_CRONJOB_NAME (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE resymf_settings CHANGE admin_panel_title admin_panel_title VARCHAR(255) NOT NULL, CHANGE headless_mode headless_mode TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE resymf_cron_jobs');
        $this->addSql('ALTER TABLE resymf_settings CHANGE admin_panel_title admin_panel_title VARCHAR(255) DEFAULT \'ReSymf CMS Admin\' NOT NULL, CHANGE headless_mode headless_mode TINYINT DEFAULT 0 NOT NULL');
    }
}
