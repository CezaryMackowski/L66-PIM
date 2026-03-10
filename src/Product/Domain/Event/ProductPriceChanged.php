<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Event;

use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

final readonly class ProductPriceChanged
{
    public function __construct(
        public Uuid $productId,
        public int $previousAmountMinor,
        public string $previousCurrency,
        public int $newAmountMinor,
        public string $newCurrency,
        public string $actorIdentifier,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
