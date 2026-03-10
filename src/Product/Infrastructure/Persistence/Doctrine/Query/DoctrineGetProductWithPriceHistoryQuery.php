<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Persistence\Doctrine\Query;

use Doctrine\DBAL\Connection;
use PIM\Product\Application\Query\Dto\ProductPriceHistoryItemDto;
use PIM\Product\Application\Query\Dto\ProductWithPriceHistoryDto;
use PIM\Product\Application\Query\GetProductWithPriceHistoryQueryInterface;
use PIM\Product\Domain\Enum\ProductEventName;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineGetProductWithPriceHistoryQuery implements GetProductWithPriceHistoryQueryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function byId(Uuid $productId): ?ProductWithPriceHistoryDto
    {
        $product = $this->connection->fetchAssociative(
            <<<SQL
            SELECT p.id, 
                   p.version,
                   p.name,
                   p.sku,
                   p.price_amount_minor,
                   p.currency,
                   p.status,
                   p.created_at,
                   p.updated_at,
                   p.deleted_at
            FROM products p
            WHERE p.id = :id AND p.deleted_at IS NULL
            LIMIT 1
            SQL,
            ['id' => $productId->toRfc4122()],
        );

        if (false === $product) {
            return null;
        }

        $historyRows = $this->connection->fetchAllAssociative(
            <<<SQL
            SELECT pe.payload,
                   pe.actor_identifier,
                   pe.occurred_at
            FROM product_events pe
            WHERE pe.product_id = :productId AND pe.event_name = :eventName
            ORDER BY pe.occurred_at DESC
            SQL,
            [
                'productId' => $productId->toRfc4122(),
                'eventName' => ProductEventName::PRODUCT_PRICE_CHANGED->value,
            ],
        );

        $priceHistory = array_map(
            static function (array $historyRow): ProductPriceHistoryItemDto {
                /** @var array{payload: string, occurred_at: string, actor_identifier: string} $historyRow */
                return ProductPriceHistoryItemDto::fromArray($historyRow);
            },
            $historyRows,
        );

        return ProductWithPriceHistoryDto::fromArray($product, $priceHistory);
    }
}
