<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Phase 6: Services
 *
 * Creates the password_reset_requests table for handling password reset functionality.
 */
final class Version20251116184500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 6: Creates password_reset_requests table for password reset functionality';
    }

    public function up(Schema $schema): void
    {
        // Create password_reset_requests table
        $this->addSql('CREATE TABLE resymf_password_reset_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            user_id INTEGER NOT NULL,
            token VARCHAR(100) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            is_used BOOLEAN NOT NULL DEFAULT 0,
            ip_address VARCHAR(255) DEFAULT NULL,
            CONSTRAINT FK_PASS_RESET_USER FOREIGN KEY (user_id) REFERENCES resymf_users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE
        )');

        // Create unique index on token
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PASS_RESET_TOKEN ON resymf_password_reset_requests (token)');

        // Create index on user_id for faster lookups
        $this->addSql('CREATE INDEX IDX_PASS_RESET_USER ON resymf_password_reset_requests (user_id)');

        // Create index on expires_at for cleanup queries
        $this->addSql('CREATE INDEX IDX_PASS_RESET_EXPIRES ON resymf_password_reset_requests (expires_at)');
    }

    public function down(Schema $schema): void
    {
        // Drop the password_reset_requests table
        $this->addSql('DROP TABLE resymf_password_reset_requests');
    }
}
