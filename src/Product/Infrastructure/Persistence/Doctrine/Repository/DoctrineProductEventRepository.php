<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PIM\Product\Domain\Model\ProductEvent;
use PIM\Product\Domain\Repository\ProductEventRepositoryInterface;

/**
 * @extends ServiceEntityRepository<ProductEvent>
 */
final class DoctrineProductEventRepository extends ServiceEntityRepository implements ProductEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductEvent::class);
    }

    public function save(ProductEvent $productEvent): void
    {
        $this->getEntityManager()->persist($productEvent);
        $this->getEntityManager()->flush();
    }
}
