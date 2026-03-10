<?php

declare(strict_types=1);

namespace PIM\Product\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Sku
{
    private const int MAX_LENGTH = 64;

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));
        if ('' === $normalized) {
            throw new InvalidArgumentException('SKU cannot be empty.');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('SKU cannot be longer than %d characters.', self::MAX_LENGTH));
        }

        if (1 !== preg_match('/^[A-Z0-9_-]+$/', $normalized)) {
            throw new InvalidArgumentException('SKU can contain only letters, digits, "_" and "-".');
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }
}
