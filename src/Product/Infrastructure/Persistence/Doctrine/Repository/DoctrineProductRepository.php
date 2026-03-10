<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\Model\Product;
use PIM\Product\Domain\Repository\ProductRepositoryInterface;
use PIM\Product\Domain\ValueObject\Sku;

/**
 * @extends ServiceEntityRepository<Product>
 */
final class DoctrineProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $product): void
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
    }

    public function existsActiveBySku(Sku $sku): bool
    {
        $result = $this->createQueryBuilder('product')
            ->select('1')
            ->andWhere('product.sku = :sku')
            ->andWhere('product.deletedAt IS NULL')
            ->andWhere('product.status = :status')
            ->setParameter('sku', $sku->value())
            ->setParameter('status', ProductStatus::ACTIVE->value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return null !== $result;
    }
}
