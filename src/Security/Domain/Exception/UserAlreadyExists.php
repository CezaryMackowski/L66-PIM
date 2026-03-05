<?php

declare(strict_types=1);

namespace PIM\Security\Domain\Exception;

use Exception;

class UserAlreadyExists extends Exception
{
    public static function ByEmail(string $email): self
    {
        return new self(sprintf('User with email: %s already exists', $email));
    }
}
