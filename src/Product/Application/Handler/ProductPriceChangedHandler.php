<?php

declare(strict_types=1);

namespace PIM\Product\Application\Handler;

use PIM\Product\Domain\Event\ProductPriceChanged;
use PIM\Product\Domain\Model\ProductEvent;
use PIM\Product\Domain\Repository\ProductEventRepositoryInterface;
use PIM\Product\Domain\ValueObject\Price;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class ProductPriceChangedHandler
{
    public function __construct(
        private ProductEventRepositoryInterface $productEventRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ProductPriceChanged $event): void
    {
        $previousPrice = Price::fromMinor($event->previousAmountMinor, $event->previousCurrency);
        $newPrice = Price::fromMinor($event->newAmountMinor, $event->newCurrency);

        $this->productEventRepository->save(ProductEvent::productPriceChanged(
            Uuid::v4(),
            $event->productId,
            $event->actorIdentifier,
            $previousPrice,
            $newPrice,
            $event->occurredAt,
        ));

        $this->logger->info('Product price changed event handled.', [
            'event_name' => 'PRODUCT_PRICE_CHANGED',
            'product_id' => $event->productId->toRfc4122(),
            'actor_identifier' => $event->actorIdentifier,
            'previous_amount_minor' => $event->previousAmountMinor,
            'previous_currency' => $event->previousCurrency,
            'new_amount_minor' => $event->newAmountMinor,
            'new_currency' => $event->newCurrency,
            'occurred_at' => $event->occurredAt->format(DATE_ATOM),
        ]);
    }
}
