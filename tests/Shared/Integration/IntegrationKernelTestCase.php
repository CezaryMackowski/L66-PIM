<?php

declare(strict_types=1);

namespace PIM\Tests\Shared\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use PIM\Kernel;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class IntegrationKernelTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->resetDatabase();
    }

    protected function tearDown(): void
    {
        self::ensureKernelShutdown();

        parent::tearDown();
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function container(): ContainerInterface
    {
        return self::getContainer();
    }

    protected function connection(): Connection
    {
        $doctrine = $this->container()->get('doctrine');
        if (!$doctrine instanceof ManagerRegistry) {
            throw new RuntimeException('Doctrine manager registry is not available.');
        }

        return $doctrine->getConnection();
    }

    protected function resetDatabase(): void
    {
        $this->connection()->executeStatement(
            'TRUNCATE TABLE product_events, products, refresh_tokens, users RESTART IDENTITY CASCADE',
        );
    }
}
