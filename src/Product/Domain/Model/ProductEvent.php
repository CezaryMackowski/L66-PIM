<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use PIM\Product\Domain\Enum\ProductEventName;
use PIM\Product\Domain\ValueObject\Price;
use Symfony\Component\Uid\Uuid;

class ProductEvent
{
    private Uuid $id;
    private Uuid $productId;
    private ProductEventName $eventName;
    private string $actorIdentifier;

    /**
     * @var array<string, mixed>
     */
    private array $payload;
    private DateTimeImmutable $occurredAt;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        Uuid $id,
        Uuid $productId,
        ProductEventName $eventName,
        string $actorIdentifier,
        array $payload,
        DateTimeImmutable $occurredAt,
    ) {
        $normalizedActorIdentifier = trim($actorIdentifier);
        if ('' === $normalizedActorIdentifier) {
            throw new InvalidArgumentException('Event actor identifier cannot be empty.');
        }

        $this->id = $id;
        $this->productId = $productId;
        $this->eventName = $eventName;
        $this->actorIdentifier = $normalizedActorIdentifier;
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
    }

    public static function productPriceChanged(
        Uuid $id,
        Uuid $productId,
        string $actorIdentifier,
        Price $previousPrice,
        Price $newPrice,
        DateTimeImmutable $occurredAt,
    ): self {
        return new self(
            $id,
            $productId,
            ProductEventName::PRODUCT_PRICE_CHANGED,
            $actorIdentifier,
            [
                'previous' => [
                    'amount_minor' => $previousPrice->amountMinor(),
                    'decimal' => $previousPrice->toDecimal(),
                    'currency' => $previousPrice->currency()->value,
                ],
                'new' => [
                    'amount_minor' => $newPrice->amountMinor(),
                    'decimal' => $newPrice->toDecimal(),
                    'currency' => $newPrice->currency()->value,
                ],
            ],
            $occurredAt,
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function productId(): Uuid
    {
        return $this->productId;
    }

    public function eventName(): ProductEventName
    {
        return $this->eventName;
    }

    public function actorIdentifier(): string
    {
        return $this->actorIdentifier;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
