<?php

declare(strict_types=1);

namespace PIM\Product\Application\Handler;

use PIM\Product\Application\Command\CreateProductCommand;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\Exception\ProductSkuAlreadyExists;
use PIM\Product\Domain\Model\Product;
use PIM\Product\Domain\Repository\ProductRepositoryInterface;
use PIM\Product\Domain\ValueObject\Price;
use PIM\Product\Domain\ValueObject\ProductName;
use PIM\Product\Domain\ValueObject\Sku;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CreateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductSkuAlreadyExists
     */
    public function __invoke(CreateProductCommand $command): void
    {
        $name = new ProductName($command->name);
        $sku = new Sku($command->sku);
        $price = Price::fromDecimal($command->price, $command->currency);
        $status = ProductStatus::fromString($command->status);

        if (ProductStatus::ACTIVE === $status && $this->productRepository->existsActiveBySku($sku)) {
            throw ProductSkuAlreadyExists::bySku($sku->value());
        }

        $this->productRepository->save(Product::create(
            $command->productId,
            $name,
            $sku,
            $price,
            $status,
            $this->clock->now(),
        ));
    }
}
