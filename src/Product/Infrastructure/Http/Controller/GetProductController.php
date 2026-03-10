<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Controller;

use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use PIM\Product\Application\Query\GetProductWithPriceHistoryQueryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class GetProductController extends AbstractController
{
    public function __construct(
        private readonly GetProductWithPriceHistoryQueryInterface $getProductWithPriceHistoryQuery,
    ) {
    }

    #[OA\Get(
        path: '/api/products/{id}',
        summary: 'Get product with price history',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: 'f2e31abf-71c3-429d-a6fd-3d4985a7fcb5',
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Product returned',
                headers: [
                    new OA\Header(
                        header: 'ETag',
                        description: 'Current product version, required in If-Match for update/delete.',
                        schema: new OA\Schema(type: 'string', example: '"2"'),
                    ),
                ],
                content: new OA\JsonContent(
                    required: ['id', 'name', 'sku', 'price', 'status', 'createdAt', 'updatedAt', 'priceHistory'],
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f2e31abf-71c3-429d-a6fd-3d4985a7fcb5'),
                        new OA\Property(property: 'name', type: 'string', example: 'Classic T-Shirt Updated v2'),
                        new OA\Property(property: 'sku', type: 'string', example: 'TSHIRT-001'),
                        new OA\Property(
                            property: 'price',
                            required: ['amountMinor', 'decimal', 'currency'],
                            properties: [
                                new OA\Property(property: 'amountMinor', type: 'integer', example: 11999),
                                new OA\Property(property: 'decimal', type: 'string', example: '119.99'),
                                new OA\Property(property: 'currency', type: 'string', example: 'PLN'),
                            ],
                            type: 'object',
                        ),
                        new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'INACTIVE'], example: 'ACTIVE'),
                        new OA\Property(property: 'createdAt', type: 'string', example: '2026-03-10 11:30:00+00'),
                        new OA\Property(property: 'updatedAt', type: 'string', example: '2026-03-10 11:45:00+00'),
                        new OA\Property(property: 'deletedAt', type: 'string', example: null, nullable: true),
                        new OA\Property(
                            property: 'priceHistory',
                            type: 'array',
                            items: new OA\Items(
                                required: ['previousPrice', 'newPrice', 'changedAt', 'actorIdentifier'],
                                properties: [
                                    new OA\Property(
                                        property: 'previousPrice',
                                        required: ['amountMinor', 'decimal', 'currency'],
                                        properties: [
                                            new OA\Property(property: 'amountMinor', type: 'integer', example: 10999),
                                            new OA\Property(property: 'decimal', type: 'string', example: '109.99'),
                                            new OA\Property(property: 'currency', type: 'string', example: 'PLN'),
                                        ],
                                        type: 'object',
                                    ),
                                    new OA\Property(
                                        property: 'newPrice',
                                        required: ['amountMinor', 'decimal', 'currency'],
                                        properties: [
                                            new OA\Property(property: 'amountMinor', type: 'integer', example: 11999),
                                            new OA\Property(property: 'decimal', type: 'string', example: '119.99'),
                                            new OA\Property(property: 'currency', type: 'string', example: 'PLN'),
                                        ],
                                        type: 'object',
                                    ),
                                    new OA\Property(property: 'changedAt', type: 'string', example: '2026-03-10 11:45:00+00'),
                                    new OA\Property(property: 'actorIdentifier', type: 'string', example: 'product.viewer@example.com'),
                                ],
                                type: 'object',
                            ),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Security(name: 'Bearer')]
    #[Route(path: '/api/products/{id}', name: 'product_get', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        try {
            if (!Uuid::isValid($id)) {
                throw new InvalidArgumentException('Invalid product id.');
            }

            $product = $this->getProductWithPriceHistoryQuery->byId(Uuid::fromString($id));
            if (null === $product) {
                return new JsonResponse(['error' => sprintf('Product with id "%s" was not found.', $id)], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse(
                $product,
                Response::HTTP_OK,
                ['ETag' => sprintf('"%d"', $product->version())],
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable) {
            return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
