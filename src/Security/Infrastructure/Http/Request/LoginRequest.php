<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Http\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['email', 'password'])]
final readonly class LoginRequest
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'email', example: 'john.doe@example.com')]
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[OA\Property(type: 'string', maxLength: 255, minLength: 8, example: 'StrongPass123!')]
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public string $password,
    ) {
    }
}

