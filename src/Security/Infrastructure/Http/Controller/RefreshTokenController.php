<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Http\Controller;

use OpenApi\Attributes as OA;
use PIM\Security\Infrastructure\Http\Request\RefreshTokenRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RefreshTokenController extends AbstractController
{
    #[OA\Post(
        path: '/api/refresh-token',
        summary: 'Refresh access token',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Refreshed',
                content: new OA\JsonContent(
                    required: ['token', 'refresh_token', 'refresh_token_expiration'],
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                        new OA\Property(property: 'refresh_token', type: 'string', example: '66fa087da651a8413b4ea26e50a25f79...'),
                        new OA\Property(property: 'refresh_token_expiration', type: 'integer', example: 1772795466),
                    ],
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Invalid refresh token'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
        ],
    )]
    #[Route(path: '/api/refresh-token', name: 'api_refresh_token', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        RefreshTokenRequest $refreshTokenRequest,
    ): Response {
        return new JsonResponse(['error' => 'This endpoint should be handled by the security firewall.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

