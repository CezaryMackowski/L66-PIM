<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310011239 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Added product events table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_events (id UUID NOT NULL, product_id UUID NOT NULL, event_name VARCHAR(64) NOT NULL, actor_identifier VARCHAR(255) NOT NULL, payload JSON NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_product_events_product_id_occurred_at ON product_events (product_id, occurred_at)');
        $this->addSql('CREATE INDEX idx_product_events_event_name ON product_events (event_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE product_events');
    }
}
