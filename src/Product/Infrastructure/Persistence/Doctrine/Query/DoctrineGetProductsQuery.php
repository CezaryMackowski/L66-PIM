<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Persistence\Doctrine\Query;

use Doctrine\ORM\Tools\Pagination\Paginator;
use PIM\Product\Application\Query\Dto\ProductListItemDto;
use PIM\Product\Application\Query\Dto\ProductsPageDto;
use PIM\Product\Application\Query\GetProductsQueryInterface;
use PIM\Product\Domain\Model\Product;
use PIM\Product\Infrastructure\Persistence\Doctrine\Repository\DoctrineProductRepository;

final readonly class DoctrineGetProductsQuery implements GetProductsQueryInterface
{
    public function __construct(
        private DoctrineProductRepository $productRepository,
    ) {
    }

    public function find(int $page, int $perPage, ?string $status): ProductsPageDto
    {
        $queryBuilder = $this->productRepository
            ->createQueryBuilder('product')
            ->andWhere('product.deletedAt IS NULL')
            ->orderBy('product.createdAt', 'DESC')
            ->addOrderBy('product.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        if (null !== $status) {
            $queryBuilder
                ->andWhere('product.status = :status')
                ->setParameter('status', $status);
        }

        $paginator = new Paginator($queryBuilder->getQuery(), false);

        /** @var list<ProductListItemDto> $items */
        $items = [];
        foreach ($paginator as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $items[] = ProductListItemDto::fromProduct($product);
        }

        return ProductsPageDto::fromPagination(
            items: $items,
            page: $page,
            perPage: $perPage,
            totalItems: count($paginator),
        );
    }
}
