<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Exception;

use Exception;

final class ProductSkuAlreadyExists extends Exception
{
    public static function bySku(string $sku): self
    {
        return new self(sprintf('Active product with SKU "%s" already exists.', $sku));
    }
}
