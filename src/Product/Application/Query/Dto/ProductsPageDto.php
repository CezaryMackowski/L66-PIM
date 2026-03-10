<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query\Dto;

use JsonSerializable;

final readonly class ProductsPageDto implements JsonSerializable
{
    /**
     * @param list<ProductListItemDto> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $perPage,
        public int $totalItems,
        public int $totalPages,
    ) {
    }

    /**
     * @param list<ProductListItemDto> $items
     */
    public static function fromPagination(array $items, int $page, int $perPage, int $totalItems): self
    {
        $totalPages = 0 === $totalItems ? 0 : (int) ceil($totalItems / $perPage);

        return new self(
            items: $items,
            page: $page,
            perPage: $perPage,
            totalItems: $totalItems,
            totalPages: $totalPages,
        );
    }

    /**
     * @return array{
     *   items: list<ProductListItemDto>,
     *   page: int,
     *   perPage: int,
     *   totalItems: int,
     *   totalPages: int
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => $this->items,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'totalItems' => $this->totalItems,
            'totalPages' => $this->totalPages,
        ];
    }
}
