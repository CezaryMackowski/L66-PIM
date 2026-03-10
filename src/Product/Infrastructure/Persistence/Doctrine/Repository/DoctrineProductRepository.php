<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PIM\Product\Domain\Enum\ProductStatus;
use PIM\Product\Domain\Model\Product;
use PIM\Product\Domain\Repository\ProductRepositoryInterface;
use PIM\Product\Domain\ValueObject\Sku;
use Symfony\Component\Uid\Uuid;

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

    public function findById(Uuid $id): ?Product
    {
        $entity = $this->createQueryBuilder('product')
            ->andWhere('product.id = :id')
            ->andWhere('product.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $entity instanceof Product ? $entity : null;
    }

    public function existsActiveBySku(Sku $sku, ?Uuid $excludeProductId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('product')
            ->select('1')
            ->andWhere('product.sku = :sku')
            ->andWhere('product.deletedAt IS NULL')
            ->andWhere('product.status = :status')
            ->setParameter('sku', $sku->value())
            ->setParameter('status', ProductStatus::ACTIVE->value)
            ->setMaxResults(1);

        if (null !== $excludeProductId) {
            $queryBuilder
                ->andWhere('product.id != :excludeProductId')
                ->setParameter('excludeProductId', $excludeProductId);
        }

        $result = $queryBuilder
            ->getQuery()
            ->getOneOrNullResult();

        return null !== $result;
    }
}
