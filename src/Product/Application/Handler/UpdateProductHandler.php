<?php

declare(strict_types=1);

namespace PIM\Product\Application\Handler;

use PIM\Product\Application\Command\UpdateProductCommand;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\Event\ProductPriceChanged;
use PIM\Product\Domain\Exception\ProductNotFound;
use PIM\Product\Domain\Exception\ProductSkuAlreadyExists;
use PIM\Product\Domain\Repository\ProductRepositoryInterface;
use PIM\Product\Domain\ValueObject\Price;
use PIM\Product\Domain\ValueObject\ProductName;
use PIM\Product\Domain\ValueObject\Sku;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
final readonly class UpdateProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private MessageBusInterface $messageBus,
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
        $previousPrice = $product->price();
        $isPriceChanged = $previousPrice->amountMinor() !== $price->amountMinor()
            || $previousPrice->currency() !== $price->currency();
        $now = $this->clock->now();

        if (ProductStatus::ACTIVE === $status && $this->productRepository->existsActiveBySku($sku, $product->id())) {
            throw ProductSkuAlreadyExists::bySku($sku->value());
        }

        $product->update(
            $name,
            $sku,
            $price,
            $status,
            $now,
        );

        $this->productRepository->save($product, $command->expectedVersion);

        if ($isPriceChanged) {
            $this->messageBus->dispatch(new ProductPriceChanged(
                $product->id(),
                $previousPrice->amountMinor(),
                $previousPrice->currency()->value,
                $price->amountMinor(),
                $price->currency()->value,
                $command->actorIdentifier,
                $now,
            ));
        }
    }
}
