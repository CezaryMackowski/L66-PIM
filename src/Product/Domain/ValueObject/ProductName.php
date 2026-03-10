<?php

declare(strict_types=1);

namespace PIM\Product\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ProductName
{
    private const int MAX_LENGTH = 255;

    private string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);
        if ('' === $normalized) {
            throw new InvalidArgumentException('Product name cannot be empty.');
        }

        if (mb_strlen($normalized) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('Product name cannot be longer than %d characters.', self::MAX_LENGTH));
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }
}
