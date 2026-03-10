<?php

declare(strict_types=1);

namespace PIM\Product\Application\Command;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateProductCommand
{
    public function __construct(
        public Uuid $productId,
        public string $name,
        public string $sku,
        public string $price,
        public string $currency,
        public string $status,
        public string $actorIdentifier,
    ) {
    }
}
