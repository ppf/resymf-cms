<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove theme system - themes table and user.theme_id FK.
 */
final class Version20251206060309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove theme system - drop resymf_themes table and theme_id from users';
    }

    public function up(Schema $schema): void
    {
        // Skip if themes table doesn't exist (fresh install)
        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist(['resymf_themes'])) {
            return;
        }

        // Check if theme_id column exists before attempting to drop
        $columns = $schemaManager->listTableColumns('resymf_users');
        if (!isset($columns['theme_id'])) {
            // Only drop themes table
            $this->addSql('DROP TABLE IF EXISTS resymf_themes');
            return;
        }

        // Drop FK and column from users first
        $this->addSql('ALTER TABLE resymf_users DROP FOREIGN KEY IF EXISTS `FK_USER_THEME`');
        $this->addSql('ALTER TABLE resymf_users DROP INDEX IF EXISTS `IDX_USER_THEME`');
        $this->addSql('ALTER TABLE resymf_users DROP COLUMN theme_id');

        // Drop themes table
        $this->addSql('DROP TABLE IF EXISTS resymf_themes');
    }

    public function down(Schema $schema): void
    {
        // Recreate themes table
        $this->addSql('CREATE TABLE resymf_themes (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, secondary_color VARCHAR(7) DEFAULT NULL, stylesheet VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL, is_default TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_THEME_NAME (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Add theme_id back to users
        $this->addSql('ALTER TABLE resymf_users ADD theme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE resymf_users ADD CONSTRAINT FK_USER_THEME FOREIGN KEY (theme_id) REFERENCES resymf_themes (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_THEME ON resymf_users (theme_id)');
    }
}
