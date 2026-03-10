<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Repository;

use PIM\Product\Domain\Model\Product;
use PIM\Product\Domain\ValueObject\Sku;
use Symfony\Component\Uid\Uuid;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(Uuid $id): ?Product;

    public function existsActiveBySku(Sku $sku, ?Uuid $excludeProductId = null): bool;
}
