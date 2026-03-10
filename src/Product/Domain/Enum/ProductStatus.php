<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Enum;

use InvalidArgumentException;

enum ProductStatus: string
{
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        return match ($normalized) {
            self::ACTIVE->value => self::ACTIVE,
            self::INACTIVE->value => self::INACTIVE,
            default => throw new InvalidArgumentException('Status is not supported.'),
        };
    }
}
