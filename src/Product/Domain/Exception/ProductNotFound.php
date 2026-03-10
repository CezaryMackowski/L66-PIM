<?php

declare(strict_types=1);

namespace PIM\Product\Domain\Exception;

use Exception;

final class ProductNotFound extends Exception
{
    public static function byId(string $id): self
    {
        return new self(sprintf('Product with id "%s" was not found.', $id));
    }
}
