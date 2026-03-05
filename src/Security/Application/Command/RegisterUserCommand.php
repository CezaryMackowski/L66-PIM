<?php

declare(strict_types=1);

namespace PIM\Security\Application\Command;

use Symfony\Component\Uid\Uuid;

final readonly class RegisterUserCommand
{
    public function __construct(
        public Uuid $userId,
        public string $email,
        public string $password,
    ) {
    }
}
