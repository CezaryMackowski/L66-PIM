<?php

declare(strict_types=1);

namespace PIM\Tests\Product\Integration\Query;

use PIM\Product\Application\Command\CreateProductCommand;
use PIM\Product\Application\Command\DeleteProductCommand;
use PIM\Product\Application\Command\UpdateProductCommand;
use PIM\Product\Application\Handler\CreateProductHandler;
use PIM\Product\Application\Handler\DeleteProductHandler;
use PIM\Product\Application\Handler\UpdateProductHandler;
use PIM\Product\Application\Query\GetProductsQueryInterface;
use PIM\Product\Application\Query\GetProductWithPriceHistoryQueryInterface;
use PIM\Tests\Shared\Integration\IntegrationKernelTestCase;
use Symfony\Component\Uid\Uuid;

final class ProductQueriesIntegrationTest extends IntegrationKernelTestCase
{
    private CreateProductHandler $createProductHandler;
    private UpdateProductHandler $updateProductHandler;
    private DeleteProductHandler $deleteProductHandler;
    private GetProductsQueryInterface $getProductsQuery;
    private GetProductWithPriceHistoryQueryInterface $getProductWithPriceHistoryQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProductHandler = $this->container()->get(CreateProductHandler::class);
        $this->updateProductHandler = $this->container()->get(UpdateProductHandler::class);
        $this->deleteProductHandler = $this->container()->get(DeleteProductHandler::class);
        $this->getProductsQuery = $this->container()->get(GetProductsQueryInterface::class);
        $this->getProductWithPriceHistoryQuery = $this->container()->get(GetProductWithPriceHistoryQueryInterface::class);
    }

    public function testGetProductWithPriceHistoryReturnsCurrentStateAndOrderedHistory(): void
    {
        // Arrange
        $productId = Uuid::v4();
        $this->createProduct(
            productId: $productId,
            name: 'Classic T-Shirt',
            sku: 'TSHIRT-001',
            price: '99.99',
            currency: 'PLN',
            status: 'ACTIVE',
        );

        $this->updateProduct($productId, 1, 'Classic T-Shirt Updated', 'TSHIRT-001', '109.99', 'PLN', 'ACTIVE', 'product.viewer@example.com');
        $this->updateProduct($productId, 2, 'Classic T-Shirt Updated v2', 'TSHIRT-001', '119.99', 'PLN', 'ACTIVE', 'product.viewer@example.com');

        // Act
        $dto = $this->getProductWithPriceHistoryQuery->byId($productId);

        // Assert
        self::assertNotNull($dto);
        self::assertSame($productId->toRfc4122(), $dto->id);
        self::assertSame('Classic T-Shirt Updated v2', $dto->name);
        self::assertSame('119.99', $dto->price->decimal);
        self::assertSame(3, $dto->version());
        self::assertCount(2, $dto->priceHistory);
        self::assertSame('119.99', $dto->priceHistory[0]->newPrice->decimal);
        self::assertSame('109.99', $dto->priceHistory[1]->newPrice->decimal);
        self::assertSame('product.viewer@example.com', $dto->priceHistory[0]->actorIdentifier);
    }

    public function testGetProductWithPriceHistoryReturnsNullForMissingProduct(): void
    {
        // Arrange
        $missingId = Uuid::v4();

        // Act
        $dto = $this->getProductWithPriceHistoryQuery->byId($missingId);

        // Assert
        self::assertNull($dto);
    }

    public function testGetProductsReturnsPaginatedAndFilteredResultWithoutSoftDeletedItems(): void
    {
        // Arrange
        $activeProductId = Uuid::v4();
        $deletedProductId = Uuid::v4();
        $inactiveProductId = Uuid::v4();

        $this->createProduct($activeProductId, 'Active Product', 'ACTIVE-001', '99.99', 'PLN', 'ACTIVE');
        $this->createProduct($deletedProductId, 'Deleted Product', 'ACTIVE-002', '149.99', 'PLN', 'ACTIVE');
        $this->createProduct($inactiveProductId, 'Inactive Product', 'INACTIVE-001', '199.99', 'PLN', 'INACTIVE');
        $this->deleteProduct($deletedProductId, 1);

        // Act
        $activeOnly = $this->getProductsQuery->find(page: 1, perPage: 20, status: 'ACTIVE');
        $allVisible = $this->getProductsQuery->find(page: 1, perPage: 20, status: null);

        // Assert
        self::assertSame(1, $activeOnly->totalItems);
        self::assertCount(1, $activeOnly->items);
        self::assertSame('ACTIVE-001', $activeOnly->items[0]->sku);

        self::assertSame(2, $allVisible->totalItems);
        self::assertCount(2, $allVisible->items);
        self::assertSame(1, $allVisible->page);
        self::assertSame(20, $allVisible->perPage);
        self::assertSame(1, $allVisible->totalPages);
    }

    private function createProduct(
        Uuid $productId,
        string $name,
        string $sku,
        string $price,
        string $currency,
        string $status,
    ): void {
        ($this->createProductHandler)(new CreateProductCommand(
            $productId,
            $name,
            $sku,
            $price,
            $currency,
            $status,
        ));
    }

    private function updateProduct(
        Uuid $productId,
        int $expectedVersion,
        string $name,
        string $sku,
        string $price,
        string $currency,
        string $status,
        string $actorIdentifier,
    ): void {
        ($this->updateProductHandler)(new UpdateProductCommand(
            $productId,
            $expectedVersion,
            $name,
            $sku,
            $price,
            $currency,
            $status,
            $actorIdentifier,
        ));
    }

    private function deleteProduct(Uuid $productId, int $expectedVersion): void
    {
        ($this->deleteProductHandler)(new DeleteProductCommand($productId, $expectedVersion));
    }
}
