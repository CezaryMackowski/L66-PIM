<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query;

use PIM\Product\Application\Query\Dto\ProductWithPriceHistoryDto;
use Symfony\Component\Uid\Uuid;

interface GetProductWithPriceHistoryQueryInterface
{
    public function byId(Uuid $productId): ?ProductWithPriceHistoryDto;
}
