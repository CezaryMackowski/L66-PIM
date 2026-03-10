<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Enum;

enum ProductEventName: string
{
    case PRODUCT_PRICE_CHANGED = 'PRODUCT_PRICE_CHANGED';
}
