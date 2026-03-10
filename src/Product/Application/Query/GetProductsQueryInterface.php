<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query;

use PIM\Product\Application\Query\Dto\ProductsPageDto;

interface GetProductsQueryInterface
{
    public function find(int $page, int $perPage, ?string $status): ProductsPageDto;
}
