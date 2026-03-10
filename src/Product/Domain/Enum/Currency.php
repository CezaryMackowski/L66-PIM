<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Enum;

use InvalidArgumentException;

enum Currency: string
{
    case PLN = 'PLN';
    case EUR = 'EUR';
    case USD = 'USD';

    public static function fromString(string $value): self
    {
        $normalized = strtoupper(trim($value));

        return match ($normalized) {
            self::PLN->value => self::PLN,
            self::EUR->value => self::EUR,
            self::USD->value => self::USD,
            default => throw new InvalidArgumentException('Currency is not supported.'),
        };
    }
}
