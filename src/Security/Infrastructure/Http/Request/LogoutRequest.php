<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Http\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['refreshToken'])]
final readonly class LogoutRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...')]
        #[Assert\NotBlank]
        public string $refreshToken,
    ) {
    }
}
