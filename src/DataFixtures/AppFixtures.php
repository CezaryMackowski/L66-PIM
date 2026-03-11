<?php

declare(strict_types=1);

namespace PIM\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\Model\Product;
use PIM\Product\Domain\ValueObject\Price;
use PIM\Product\Domain\ValueObject\ProductName;
use PIM\Product\Domain\ValueObject\Sku;
use PIM\Security\Domain\Model\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class AppFixtures extends Fixture
{
    private array $users = [
        ['email' => 'john.doe@example.com', 'password' => 'StrongPass123!', 'roles' => ['ROLE_USER']],
        ['email' => 'jane.doe@example.com', 'password' => 'StrongPass123!', 'roles' => ['ROLE_USER']],
        ['email' => 'admin@example.com', 'password' => 'AdminPass123!', 'roles' => ['ROLE_ADMIN']],
    ];

    private array $products = [
        ['name' => 'Apple iPhone 15', 'sku' => 'IPHONE-15-128-BLK', 'price' => '3799.00', 'currency' => 'PLN', 'status' => 'ACTIVE'],
        ['name' => 'Samsung Galaxy S24', 'sku' => 'GALAXY-S24-256-GRY', 'price' => '4299.00', 'currency' => 'PLN', 'status' => 'ACTIVE'],
        ['name' => 'Sony WH-1000XM5', 'sku' => 'SONY-WH1000XM5-BLK', 'price' => '1499.00', 'currency' => 'PLN', 'status' => 'ACTIVE'],
        ['name' => 'Logitech MX Master 3S', 'sku' => 'LOGI-MX3S-GRAPHITE', 'price' => '549.00', 'currency' => 'PLN', 'status' => 'ACTIVE'],
        ['name' => 'Dell UltraSharp U2723QE', 'sku' => 'DELL-U2723QE-27', 'price' => '2799.00', 'currency' => 'PLN', 'status' => 'INACTIVE'],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $this->loadProducts($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $now = $this->clock->now();
        foreach ($this->users as $data) {
            $user = new User(
                Uuid::v4(),
                $data['email'],
                '',
                $now,
                $data['roles'],
            );
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

            $manager->persist($user);
        }
    }

    private function loadProducts(ObjectManager $manager): void
    {
        $now = $this->clock->now();
        foreach ($this->products as $data) {
            $product = Product::create(
                Uuid::v4(),
                new ProductName($data['name']),
                new Sku($data['sku']),
                Price::fromDecimal($data['price'], $data['currency']),
                ProductStatus::fromString($data['status']),
                $now,
            );

            $manager->persist($product);
        }
    }
}
