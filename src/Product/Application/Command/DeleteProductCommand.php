<?php

declare(strict_types=1);

namespace PIM\Product\Application\Command;

use Symfony\Component\Uid\Uuid;

final readonly class DeleteProductCommand
{
    public function __construct(
        public Uuid $productId,
    ) {
    }
}
