<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added optimistic lock version for products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD version INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP version');
    }
}
