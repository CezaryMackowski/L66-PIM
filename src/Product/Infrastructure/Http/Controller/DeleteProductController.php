<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Controller;

use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use PIM\Product\Application\Command\DeleteProductCommand;
use PIM\Product\Domain\Exception\ProductNotFound;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class DeleteProductController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[OA\Delete(
        path: '/api/products/{id}',
        summary: 'Soft delete product',
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
                response: Response::HTTP_ACCEPTED,
                description: 'Product delete accepted',
                content: new OA\JsonContent(
                    required: ['id', 'status'],
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f2e31abf-71c3-429d-a6fd-3d4985a7fcb5'),
                        new OA\Property(property: 'status', type: 'string', example: 'ACCEPTED'),
                    ],
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Product not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Security(name: 'Bearer')]
    #[Route(path: '/api/products/{id}', name: 'product_delete', methods: ['DELETE'])]
    public function __invoke(string $id): Response
    {
        try {
            if (!Uuid::isValid($id)) {
                throw new InvalidArgumentException('Invalid product id.');
            }

            $productId = Uuid::fromString($id);
            $this->messageBus->dispatch(new DeleteProductCommand($productId));

            return new JsonResponse(
                ['id' => $productId->toRfc4122(), 'status' => 'ACCEPTED'],
                Response::HTTP_ACCEPTED,
            );
        } catch (HandlerFailedException $exception) {
            $rootException = $exception;
            foreach ($exception->getWrappedExceptions() as $wrappedException) {
                $rootException = $wrappedException;

                break;
            }

            if ($rootException instanceof ProductNotFound) {
                return new JsonResponse(['error' => $rootException->getMessage()], Response::HTTP_NOT_FOUND);
            }

            if ($rootException instanceof InvalidArgumentException) {
                return new JsonResponse(['error' => $rootException->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable) {
            return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
