<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query\Dto;

use JsonSerializable;
use PIM\Product\Domain\Model\Product;

final readonly class ProductListItemDto implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $name,
        public string $sku,
        public ProductPriceDto $price,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromProduct(Product $product): self
    {
        $price = $product->price();

        return new self(
            id: $product->id()->toRfc4122(),
            name: $product->name()->value(),
            sku: $product->sku()->value(),
            price: new ProductPriceDto(
                amountMinor: $price->amountMinor(),
                decimal: $price->toDecimal(),
                currency: $price->currency()->value,
            ),
            status: $product->status()->value,
            createdAt: $product->createdAt()->format('Y-m-d H:i:sP'),
            updatedAt: $product->updatedAt()->format('Y-m-d H:i:sP'),
        );
    }

    /**
     * @return array{
     *   id: string,
     *   name: string,
     *   sku: string,
     *   price: ProductPriceDto,
     *   status: string,
     *   createdAt: string,
     *   updatedAt: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'price' => $this->price,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
