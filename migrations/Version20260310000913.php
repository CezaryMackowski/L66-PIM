<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310000913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added products table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TYPE product_status AS ENUM ('ACTIVE', 'INACTIVE')");
        $this->addSql('CREATE TABLE products (id UUID NOT NULL, name VARCHAR(255) NOT NULL, sku VARCHAR(64) NOT NULL, price_amount_minor INT NOT NULL, currency VARCHAR(3) NOT NULL, status product_status NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql("CREATE UNIQUE INDEX uniq_products_active_sku ON products (sku) WHERE deleted_at IS NULL AND status = 'ACTIVE'::product_status");
        $this->addSql('CREATE INDEX idx_products_status_deleted_at ON products (status, deleted_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TYPE product_status');
    }
}
