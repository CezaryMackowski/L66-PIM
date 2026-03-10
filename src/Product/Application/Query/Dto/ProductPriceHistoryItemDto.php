<?php

declare(strict_types=1);

namespace PIM\Product\Application\Query\Dto;

use JsonSerializable;

final readonly class ProductPriceHistoryItemDto implements JsonSerializable
{
    public function __construct(
        public ProductPriceDto $previousPrice,
        public ProductPriceDto $newPrice,
        public string $changedAt,
        public string $actorIdentifier,
    ) {
    }

    /**
     * @param array{
     *   payload: string,
     *   occurred_at: string,
     *   actor_identifier: string
     * } $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array{previous: array<string, mixed>, new: array<string, mixed>} $payload */
        $payload = json_decode($data['payload'], true, 512, JSON_THROW_ON_ERROR);

        return new self(
            previousPrice: ProductPriceDto::fromArray($payload['previous']),
            newPrice: ProductPriceDto::fromArray($payload['new']),
            changedAt: $data['occurred_at'],
            actorIdentifier: $data['actor_identifier'],
        );
    }

    /**
     * @return array{
     *   previousPrice: ProductPriceDto,
     *   newPrice: ProductPriceDto,
     *   changedAt: string,
     *   actorIdentifier: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'previousPrice' => $this->previousPrice,
            'newPrice' => $this->newPrice,
            'changedAt' => $this->changedAt,
            'actorIdentifier' => $this->actorIdentifier,
        ];
    }
}
