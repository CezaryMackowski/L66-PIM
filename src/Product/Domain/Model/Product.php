<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Model;

use DateTimeImmutable;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\ValueObject\Price;
use PIM\Product\Domain\ValueObject\ProductName;
use PIM\Product\Domain\ValueObject\Sku;
use Symfony\Component\Uid\Uuid;

class Product
{
    private Uuid $id;
    private string $name;
    private string $sku;
    private Price $price;
    private ProductStatus $status;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;
    private ?DateTimeImmutable $deletedAt;

    private function __construct(
        Uuid $id,
        string $name,
        string $sku,
        Price $price,
        ProductStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->sku = $sku;
        $this->price = $price;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }

    public static function create(
        Uuid $id,
        ProductName $name,
        Sku $sku,
        Price $price,
        ProductStatus $status,
        DateTimeImmutable $now,
    ): self {
        return new self(
            $id,
            $name->value(),
            $sku->value(),
            $price,
            $status,
            $now,
            $now,
            null,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): ProductName
    {
        return new ProductName($this->name);
    }

    public function sku(): Sku
    {
        return new Sku($this->sku);
    }

    public function price(): Price
    {
        return $this->price;
    }

    public function status(): ProductStatus
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function update(
        ProductName $name,
        Sku $sku,
        Price $price,
        ProductStatus $status,
        DateTimeImmutable $updatedAt,
    ): void {
        $this->name = $name->value();
        $this->sku = $sku->value();
        $this->price = $price;
        $this->status = $status;
        $this->updatedAt = $updatedAt;
    }
}
