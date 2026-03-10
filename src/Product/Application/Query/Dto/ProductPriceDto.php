<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query\Dto;

use JsonSerializable;
use RuntimeException;

final readonly class ProductPriceDto implements JsonSerializable
{
    public function __construct(
        public int $amountMinor,
        public string $decimal,
        public string $currency,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $amountMinor = $data['amount_minor'] ?? $data['amountMinor'] ?? null;
        $decimal = $data['decimal'] ?? null;
        $currency = $data['currency'] ?? null;

        if (!is_int($amountMinor) && !is_float($amountMinor) && !is_string($amountMinor)) {
            throw new RuntimeException('Missing or invalid amount minor.');
        }

        if (!is_string($currency)) {
            throw new RuntimeException('Missing or invalid currency.');
        }

        $amountMinorInt = (int) $amountMinor;
        $decimalString = is_string($decimal)
            ? $decimal
            : number_format($amountMinorInt / 100, 2, '.', '');

        return new self(
            amountMinor: $amountMinorInt,
            decimal: $decimalString,
            currency: $currency,
        );
    }

    /**
     * @return array{amountMinor: int, decimal: string, currency: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amountMinor' => $this->amountMinor,
            'decimal' => $this->decimal,
            'currency' => $this->currency,
        ];
    }
}
