<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Repository;

use PIM\Product\Domain\Model\ProductEvent;

interface ProductEventRepositoryInterface
{
    public function save(ProductEvent $productEvent): void;
}
