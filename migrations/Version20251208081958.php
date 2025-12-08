<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208081958 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add 2FA fields to User and Settings entities';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resymf_settings ADD enforce2fa TINYINT NOT NULL');
        $this->addSql('ALTER TABLE resymf_users ADD totp_secret VARCHAR(255) DEFAULT NULL, ADD is_totp_enabled TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE resymf_settings DROP enforce2fa');
        $this->addSql('ALTER TABLE resymf_users DROP totp_secret, DROP is_totp_enabled');
    }
}
