<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query\Dto;

use JsonSerializable;
use RuntimeException;

final readonly class ProductWithPriceHistoryDto implements JsonSerializable
{
    /**
     * @param list<ProductPriceHistoryItemDto> $priceHistory
     */
    public function __construct(
        private int $version,
        public string $id,
        public string $name,
        public string $sku,
        public ProductPriceDto $price,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
        public array $priceHistory,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param list<ProductPriceHistoryItemDto> $priceHistory
     */
    public static function fromArray(array $data, array $priceHistory): self
    {
        $id = $data['id'] ?? null;
        $versionRaw = $data['version'] ?? null;
        $version = null;
        if (is_int($versionRaw)) {
            $version = $versionRaw;
        } elseif (is_string($versionRaw) && preg_match('/^\d+$/', $versionRaw)) {
            $version = (int) $versionRaw;
        }
        $name = $data['name'] ?? null;
        $sku = $data['sku'] ?? null;
        $status = $data['status'] ?? null;
        $createdAt = $data['created_at'] ?? $data['createdAt'] ?? null;
        $updatedAt = $data['updated_at'] ?? $data['updatedAt'] ?? null;
        $deletedAt = $data['deleted_at'] ?? $data['deletedAt'] ?? null;

        if (
            !is_string($id)
            || !is_int($version)
            || 1 > $version
            || !is_string($name)
            || !is_string($sku)
            || !is_string($status)
            || !is_string($createdAt)
            || !is_string($updatedAt)
        ) {
            throw new RuntimeException('Invalid product row.');
        }

        if (null !== $deletedAt && !is_string($deletedAt)) {
            throw new RuntimeException('Invalid deletedAt value.');
        }

        /** @var array<string, mixed> $priceData */
        $priceData = [
            'amount_minor' => $data['price_amount_minor'] ?? $data['amount_minor'] ?? null,
            'decimal' => $data['price_decimal'] ?? $data['decimal'] ?? null,
            'currency' => $data['currency'] ?? null,
        ];

        return new self(
            version: $version,
            id: $id,
            name: $name,
            sku: $sku,
            price: ProductPriceDto::fromArray($priceData),
            status: $status,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
            priceHistory: $priceHistory,
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
     *   updatedAt: string,
     *   deletedAt: ?string,
     *   priceHistory: list<ProductPriceHistoryItemDto>
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
            'deletedAt' => $this->deletedAt,
            'priceHistory' => $this->priceHistory,
        ];
    }

    public function version(): int
    {
        return $this->version;
    }
}
