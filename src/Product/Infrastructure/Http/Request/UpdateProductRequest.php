<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Request;

use OpenApi\Attributes as OA;
use PIM\Product\Domain\Enum\Currency;
use PIM\Product\Domain\Enum\ProductStatus;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['name', 'sku', 'price', 'currency', 'status'])]
final readonly class UpdateProductRequest
{
    public function __construct(
        #[OA\Property(type: 'string', maxLength: 255, example: 'Classic T-Shirt v2')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $name,
        #[OA\Property(type: 'string', maxLength: 64, example: 'TSHIRT-CLASSIC-002')]
        #[Assert\NotBlank]
        #[Assert\Length(max: 64)]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9_-]+$/')]
        public string $sku,
        #[OA\Property(type: 'string', example: '149.99')]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/')]
        public string $price,
        #[OA\Property(type: 'string', enum: ['PLN', 'EUR', 'USD'], example: 'PLN')]
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [self::class, 'allowedCurrencies'])]
        public string $currency,
        #[OA\Property(type: 'string', enum: ['ACTIVE', 'INACTIVE'], example: 'ACTIVE')]
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [self::class, 'allowedStatuses'])]
        public string $status,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function allowedCurrencies(): array
    {
        return array_map(
            static fn (Currency $currency): string => $currency->value,
            Currency::cases(),
        );
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
