<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Repository;

use PIM\Product\Domain\Model\Product;
use PIM\Product\Domain\ValueObject\Sku;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function existsActiveBySku(Sku $sku): bool;
}
