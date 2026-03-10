<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Controller;

use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use PIM\Product\Application\Query\GetProductsQueryInterface;
use PIM\Product\Infrastructure\Http\Request\GetProductsRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class GetProductsController extends AbstractController
{
    public function __construct(
        private readonly GetProductsQueryInterface $getProductsQuery,
    ) {
    }

    #[OA\Get(
        path: '/api/products',
        summary: 'Get products list',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
                example: 1,
            ),
            new OA\Parameter(
                name: 'perPage',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20),
                example: 20,
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['ACTIVE', 'INACTIVE']),
                example: 'ACTIVE',
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Products returned',
                content: new OA\JsonContent(
                    required: ['items', 'page', 'perPage', 'totalItems', 'totalPages'],
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                required: ['id', 'name', 'sku', 'price', 'status', 'createdAt', 'updatedAt'],
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f2e31abf-71c3-429d-a6fd-3d4985a7fcb5'),
                                    new OA\Property(property: 'name', type: 'string', example: 'Classic T-Shirt'),
                                    new OA\Property(property: 'sku', type: 'string', example: 'TSHIRT-001'),
                                    new OA\Property(
                                        property: 'price',
                                        required: ['amountMinor', 'decimal', 'currency'],
                                        properties: [
                                            new OA\Property(property: 'amountMinor', type: 'integer', example: 9999),
                                            new OA\Property(property: 'decimal', type: 'string', example: '99.99'),
                                            new OA\Property(property: 'currency', type: 'string', example: 'PLN'),
                                        ],
                                        type: 'object',
                                    ),
                                    new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'INACTIVE'], example: 'ACTIVE'),
                                    new OA\Property(property: 'createdAt', type: 'string', example: '2026-03-10 11:30:00+00:00'),
                                    new OA\Property(property: 'updatedAt', type: 'string', example: '2026-03-10 11:30:00+00:00'),
                                ],
                                type: 'object',
                            ),
                        ),
                        new OA\Property(property: 'page', type: 'integer', example: 1),
                        new OA\Property(property: 'perPage', type: 'integer', example: 20),
                        new OA\Property(property: 'totalItems', type: 'integer', example: 32),
                        new OA\Property(property: 'totalPages', type: 'integer', example: 2),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Security(name: 'Bearer')]
    #[Route(path: '/api/products', name: 'product_get_list', methods: ['GET'])]
    public function __invoke(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        GetProductsRequest $getProductsRequest,
    ): Response {
        try {
            $result = $this->getProductsQuery->find(
                page: $getProductsRequest->page,
                perPage: $getProductsRequest->perPage,
                status: $getProductsRequest->status,
            );

            return new JsonResponse($result, Response::HTTP_OK);
        } catch (Throwable) {
            return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
