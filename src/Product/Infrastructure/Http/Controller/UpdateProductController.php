<?php

declare(strict_types=1);

namespace PIM\Product\Infrastructure\Http\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\OptimisticLockException;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use PIM\Product\Application\Command\UpdateProductCommand;
use PIM\Product\Domain\Exception\ProductNotFound;
use PIM\Product\Domain\Exception\ProductSkuAlreadyExists;
use PIM\Product\Infrastructure\Http\Request\UpdateProductRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class UpdateProductController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[OA\Put(
        path: '/api/products/{id}',
        summary: 'Update product',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: 'f2e31abf-71c3-429d-a6fd-3d4985a7fcb5',
            ),
            new OA\Parameter(
                name: 'If-Match',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: '"2"',
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_ACCEPTED,
                description: 'Product update accepted',
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
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'SKU conflict or stale product version'),
            new OA\Response(response: Response::HTTP_PRECONDITION_REQUIRED, description: 'Missing If-Match header'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Security(name: 'Bearer')]
    #[Route(path: '/api/products/{id}', name: 'product_update', methods: ['PUT'])]
    public function __invoke(
        string $id,
        Request $request,
        #[CurrentUser]
        ?UserInterface $currentUser,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        UpdateProductRequest $updateProductRequest,
    ): Response {
        try {
            if (!Uuid::isValid($id)) {
                throw new InvalidArgumentException('Invalid product id.');
            }

            if (null === $currentUser) {
                throw new InvalidArgumentException('Current user is required.');
            }

            $actorIdentifier = trim($currentUser->getUserIdentifier());
            if ('' === $actorIdentifier) {
                throw new InvalidArgumentException('Current user identifier cannot be empty.');
            }

            $ifMatch = $request->headers->get('If-Match');
            if (null === $ifMatch) {
                return new JsonResponse(
                    ['error' => 'If-Match header is required.'],
                    Response::HTTP_PRECONDITION_REQUIRED,
                );
            }

            $expectedVersion = $this->parseExpectedVersion($ifMatch);
            $productId = Uuid::fromString($id);
            $this->messageBus->dispatch(new UpdateProductCommand(
                $productId,
                $expectedVersion,
                $updateProductRequest->name,
                $updateProductRequest->sku,
                $updateProductRequest->price,
                $updateProductRequest->currency,
                $updateProductRequest->status,
                $actorIdentifier,
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

            if ($rootException instanceof ProductNotFound) {
                return new JsonResponse(['error' => $rootException->getMessage()], Response::HTTP_NOT_FOUND);
            }

            if ($rootException instanceof ProductSkuAlreadyExists) {
                return new JsonResponse(['error' => $rootException->getMessage()], Response::HTTP_CONFLICT);
            }

            if ($rootException instanceof UniqueConstraintViolationException) {
                return new JsonResponse(['error' => 'Active product with this SKU already exists.'], Response::HTTP_CONFLICT);
            }

            if ($rootException instanceof OptimisticLockException) {
                return new JsonResponse(
                    ['error' => 'Product has been modified by another user. Fetch latest version and retry.'],
                    Response::HTTP_CONFLICT,
                );
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

    /**
     * @throws InvalidArgumentException
     */
    private function parseExpectedVersion(string $ifMatch): int
    {
        $ifMatch = trim($ifMatch);
        if (!preg_match('/^"([1-9]\d*)"$/', $ifMatch, $matches)) {
            throw new InvalidArgumentException('Invalid If-Match header. Expected format: "N".');
        }

        return (int) $matches[1];
    }
}
