<?php

declare(strict_types=1);

namespace PIM\Product\Domain\ValueObject;

use InvalidArgumentException;
use PIM\Product\Domain\Enum\Currency;

final class Price
{
    private int $amountMinor;
    private Currency $currency;

    public function __construct(
        int $amountMinor,
        Currency $currency,
    ) {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Price must be greater than zero.');
        }

        $this->amountMinor = $amountMinor;
        $this->currency = $currency;
    }

    public static function fromDecimal(string $decimal, string $currency): self
    {
        $normalizedDecimal = trim($decimal);
        if (1 !== preg_match('/^\d+(\.\d{1,2})?$/', $normalizedDecimal)) {
            throw new InvalidArgumentException('Price must be a valid decimal with up to two decimals.');
        }

        $parts = explode('.', $normalizedDecimal, 2);
        $major = (int) $parts[0];
        $minorFraction = str_pad($parts[1] ?? '', 2, '0');
        $minor = (int) $minorFraction;
        $amountMinor = ($major * 100) + $minor;

        return new self($amountMinor, Currency::fromString($currency));
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function toDecimal(): string
    {
        return number_format($this->amountMinor / 100, 2, '.', '');
    }
}
