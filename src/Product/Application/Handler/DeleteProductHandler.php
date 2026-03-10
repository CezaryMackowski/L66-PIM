<?php

declare(strict_types=1);

namespace PIM\Product\Application\Handler;

use PIM\Product\Application\Command\DeleteProductCommand;
use PIM\Product\Domain\Exception\ProductNotFound;
use PIM\Product\Domain\Repository\ProductRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class DeleteProductHandler
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws ProductNotFound
     */
    public function __invoke(DeleteProductCommand $command): void
    {
        $product = $this->productRepository->findById($command->productId);
        if (null === $product) {
            throw ProductNotFound::byId($command->productId->toRfc4122());
        }

        $product->softDelete($this->clock->now());
        $this->productRepository->save($product);
    }
}
