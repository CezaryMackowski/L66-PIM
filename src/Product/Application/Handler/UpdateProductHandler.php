<?php

declare(strict_types=1);

namespace PIM\Product\Application\Handler;

use PIM\Product\Application\Command\UpdateProductCommand;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\Exception\ProductNotFound;
use PIM\Product\Domain\Exception\ProductSkuAlreadyExists;
use PIM\Product\Domain\Repository\ProductRepositoryInterface;
use PIM\Product\Domain\ValueObject\Price;
use PIM\Product\Domain\ValueObject\ProductName;
use PIM\Product\Domain\ValueObject\Sku;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductNotFound
     * @throws ProductSkuAlreadyExists
     */
    public function __invoke(UpdateProductCommand $command): void
    {
        $product = $this->productRepository->findById($command->productId);
        if (null === $product) {
            throw ProductNotFound::byId($command->productId->toRfc4122());
        }

        $name = new ProductName($command->name);
        $sku = new Sku($command->sku);
        $price = Price::fromDecimal($command->price, $command->currency);
        $status = ProductStatus::fromString($command->status);

        if (ProductStatus::ACTIVE === $status && $this->productRepository->existsActiveBySku($sku, $product->id())) {
            throw ProductSkuAlreadyExists::bySku($sku->value());
        }

        $product->update(
            $name,
            $sku,
            $price,
            $status,
            $this->clock->now(),
        );

        $this->productRepository->save($product);
    }
}
