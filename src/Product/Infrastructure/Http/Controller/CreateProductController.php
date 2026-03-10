<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use PIM\Product\Application\Command\CreateProductCommand;
use PIM\Product\Domain\Exception\ProductSkuAlreadyExists;
use PIM\Product\Infrastructure\Http\Request\CreateProductRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class CreateProductController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[OA\Post(
        path: '/api/products',
        summary: 'Create product',
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: Response::HTTP_ACCEPTED,
                description: 'Product creation accepted',
                content: new OA\JsonContent(
                    required: ['id', 'status'],
                    properties: [
                        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: 'f2e31abf-71c3-429d-a6fd-3d4985a7fcb5'),
                        new OA\Property(property: 'status', type: 'string', example: 'ACCEPTED'),
                    ],
                ),
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'SKU conflict'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Security(name: 'Bearer')]
    #[Route(path: '/api/products', name: 'product_create', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CreateProductRequest $createProductRequest,
    ): Response {
        try {
            $productId = Uuid::v4();
            $this->messageBus->dispatch(new CreateProductCommand(
                $productId,
                $createProductRequest->name,
                $createProductRequest->sku,
                $createProductRequest->price,
                $createProductRequest->currency,
                $createProductRequest->status,
            ));

            return new JsonResponse(
                ['id' => $productId->toRfc4122(), 'status' => 'ACCEPTED'],
                Response::HTTP_ACCEPTED,
                ['Location' => sprintf('/api/products/%s', $productId->toRfc4122())],
            );
        } catch (HandlerFailedException $exception) {
            $rootException = $exception;
            foreach ($exception->getWrappedExceptions() as $wrappedException) {
                $rootException = $wrappedException;

                break;
            }

            if ($rootException instanceof ProductSkuAlreadyExists) {
                return new JsonResponse(['error' => $rootException->getMessage()], Response::HTTP_CONFLICT);
            }

            if ($rootException instanceof UniqueConstraintViolationException) {
                return new JsonResponse(['error' => 'Active product with this SKU already exists.'], Response::HTTP_CONFLICT);
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
