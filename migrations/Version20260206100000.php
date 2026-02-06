<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260206100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change Page author foreign key from CASCADE to SET NULL to preserve pages when users are deleted';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resymf_pages DROP FOREIGN KEY FK_PAGES_AUTHOR');
        $this->addSql('ALTER TABLE resymf_pages ADD CONSTRAINT FK_PAGES_AUTHOR FOREIGN KEY (author_id) REFERENCES resymf_users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resymf_pages DROP FOREIGN KEY FK_PAGES_AUTHOR');
        $this->addSql('ALTER TABLE resymf_pages ADD CONSTRAINT FK_PAGES_AUTHOR FOREIGN KEY (author_id) REFERENCES resymf_users (id) ON DELETE CASCADE');
    }
}
