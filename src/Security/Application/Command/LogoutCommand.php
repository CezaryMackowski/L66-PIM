<?php

declare(strict_types=1);

namespace PIM\Security\Application\Command;

final readonly class LogoutCommand
{
    public function __construct(
        public string $refreshToken,
    ) {
    }
}
