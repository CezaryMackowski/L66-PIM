<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Http\Request;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(required: ['refresh_token'])]
final readonly class RefreshTokenRequest
{
    public function __construct(
        #[OA\Property(type: 'string', example: '66fa087da651a8413b4ea26e50a25f79...')]
        #[Assert\NotBlank]
        public string $refresh_token,
    ) {
    }
}
