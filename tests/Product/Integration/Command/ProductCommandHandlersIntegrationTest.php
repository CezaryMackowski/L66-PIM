<?php

declare(strict_types=1);

namespace PIM\Tests\Product\Integration\Command;

use Doctrine\ORM\OptimisticLockException;
use PIM\Product\Application\Command\CreateProductCommand;
use PIM\Product\Application\Command\DeleteProductCommand;
use PIM\Product\Application\Command\UpdateProductCommand;
use PIM\Product\Application\Handler\CreateProductHandler;
use PIM\Product\Application\Handler\DeleteProductHandler;
use PIM\Product\Application\Handler\UpdateProductHandler;
use PIM\Product\Domain\Exception\ProductSkuAlreadyExists;
use PIM\Tests\Shared\Integration\IntegrationKernelTestCase;
use Symfony\Component\Uid\Uuid;

final class ProductCommandHandlersIntegrationTest extends IntegrationKernelTestCase
{
    private CreateProductHandler $createProductHandler;
    private UpdateProductHandler $updateProductHandler;
    private DeleteProductHandler $deleteProductHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProductHandler = $this->container()->get(CreateProductHandler::class);
        $this->updateProductHandler = $this->container()->get(UpdateProductHandler::class);
        $this->deleteProductHandler = $this->container()->get(DeleteProductHandler::class);
    }

    public function testCreateProductPersistsAggregateWithInitialVersion(): void
    {
        // Arrange
        $productId = Uuid::v4();
        $command = new CreateProductCommand(
            $productId,
            'Classic T-Shirt',
            'TSHIRT-001',
            '99.99',
            'PLN',
            'ACTIVE',
        );

        // Act
        ($this->createProductHandler)($command);

        // Assert
        $row = $this->connection()->fetchAssociative(
            'SELECT id, name, sku, price_amount_minor, currency, status, version, deleted_at FROM products WHERE id = :id',
            ['id' => $productId->toRfc4122()],
        );

        self::assertIsArray($row);
        self::assertSame($productId->toRfc4122(), $row['id'] ?? null);
        self::assertSame('Classic T-Shirt', $row['name'] ?? null);
        self::assertSame('TSHIRT-001', $row['sku'] ?? null);
        self::assertSame(9999, (int) ($row['price_amount_minor'] ?? 0));
        self::assertSame('PLN', $row['currency'] ?? null);
        self::assertSame('ACTIVE', $row['status'] ?? null);
        self::assertSame(1, (int) ($row['version'] ?? 0));
        self::assertNull($row['deleted_at'] ?? null);
    }

    public function testCreateProductThrowsWhenActiveSkuAlreadyExists(): void
    {
        // Arrange
        $this->createProduct(
            productId: Uuid::v4(),
            name: 'Classic T-Shirt',
            sku: 'TSHIRT-001',
            price: '99.99',
            currency: 'PLN',
            status: 'ACTIVE',
        );

        // Assert
        $this->expectException(ProductSkuAlreadyExists::class);

        // Act
        $this->createProduct(
            productId: Uuid::v4(),
            name: 'Classic Hoodie',
            sku: 'TSHIRT-001',
            price: '149.99',
            currency: 'PLN',
            status: 'ACTIVE',
        );
    }

    public function testUpdateProductPersistsChangesAndStoresPriceChangeEvent(): void
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

        $command = new UpdateProductCommand(
            $productId,
            1,
            'Classic T-Shirt Updated',
            'TSHIRT-001',
            '109.99',
            'PLN',
            'ACTIVE',
            'product.editor@example.com',
        );

        // Act
        ($this->updateProductHandler)($command);

        // Assert
        $productRow = $this->connection()->fetchAssociative(
            'SELECT name, price_amount_minor, version FROM products WHERE id = :id',
            ['id' => $productId->toRfc4122()],
        );
        self::assertIsArray($productRow);
        self::assertSame('Classic T-Shirt Updated', $productRow['name'] ?? null);
        self::assertSame(10999, (int) ($productRow['price_amount_minor'] ?? 0));
        self::assertSame(2, (int) ($productRow['version'] ?? 0));

        $eventRow = $this->connection()->fetchAssociative(
            'SELECT event_name, actor_identifier, payload FROM product_events WHERE product_id = :id ORDER BY occurred_at DESC LIMIT 1',
            ['id' => $productId->toRfc4122()],
        );
        self::assertIsArray($eventRow);
        self::assertSame('PRODUCT_PRICE_CHANGED', $eventRow['event_name'] ?? null);
        self::assertSame('product.editor@example.com', $eventRow['actor_identifier'] ?? null);

        $payloadRaw = $eventRow['payload'] ?? null;
        $payload = is_string($payloadRaw)
            ? json_decode($payloadRaw, true, 512, JSON_THROW_ON_ERROR)
            : $payloadRaw;

        self::assertIsArray($payload);
        self::assertSame('99.99', $payload['previous']['decimal'] ?? null);
        self::assertSame('109.99', $payload['new']['decimal'] ?? null);
        self::assertSame('PLN', $payload['previous']['currency'] ?? null);
        self::assertSame('PLN', $payload['new']['currency'] ?? null);
    }

    public function testUpdateProductThrowsForStaleVersion(): void
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

        ($this->updateProductHandler)(new UpdateProductCommand(
            $productId,
            1,
            'Classic T-Shirt Updated',
            'TSHIRT-001',
            '109.99',
            'PLN',
            'ACTIVE',
            'product.editor@example.com',
        ));

        // Assert
        $this->expectException(OptimisticLockException::class);

        // Act
        ($this->updateProductHandler)(new UpdateProductCommand(
            $productId,
            1,
            'Classic T-Shirt Updated v2',
            'TSHIRT-001',
            '119.99',
            'PLN',
            'ACTIVE',
            'product.editor@example.com',
        ));
    }

    public function testDeleteProductSoftDeletesAndIncrementsVersion(): void
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

        $command = new DeleteProductCommand($productId, 1);

        // Act
        ($this->deleteProductHandler)($command);

        // Assert
        $row = $this->connection()->fetchAssociative(
            'SELECT deleted_at, version FROM products WHERE id = :id',
            ['id' => $productId->toRfc4122()],
        );

        self::assertIsArray($row);
        self::assertNotNull($row['deleted_at'] ?? null);
        self::assertSame(2, (int) ($row['version'] ?? 0));
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
}
