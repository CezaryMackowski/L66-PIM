<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Request;

use OpenApi\Attributes as OA;
use PIM\Product\Domain\Enum\ProductStatus;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema]
final readonly class GetProductsRequest
{
    public function __construct(
        #[OA\Property(type: 'integer', default: 1, minimum: 1, example: 1)]
        #[Assert\GreaterThanOrEqual(1)]
        public int $page = 1,
        #[OA\Property(type: 'integer', default: 20, maximum: 100, minimum: 1, example: 20)]
        #[Assert\Range(min: 1, max: 100)]
        public int $perPage = 20,
        #[OA\Property(type: 'string', enum: ['ACTIVE', 'INACTIVE'], example: 'ACTIVE', nullable: true)]
        #[Assert\Choice(callback: [self::class, 'allowedStatuses'])]
        public ?string $status = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function allowedStatuses(): array
    {
        return array_map(
            static fn (ProductStatus $status): string => $status->value,
            ProductStatus::cases(),
        );
    }
}
