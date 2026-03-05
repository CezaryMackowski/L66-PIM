<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Http\Controller;

use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use PIM\Security\Application\Command\LogoutCommand;
use PIM\Security\Infrastructure\Http\Request\LogoutRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class LogoutController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout and invalidate refresh token',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Refresh token invalidated'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Unauthorized'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Security(name: 'Bearer')]
    #[Route(path: '/api/logout', name: 'security_logout', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        LogoutRequest $logoutRequest,
    ): Response {
        try {
            $this->messageBus->dispatch(new LogoutCommand($logoutRequest->refreshToken));

            return new Response(status: Response::HTTP_NO_CONTENT);
        } catch (HandlerFailedException $exception) {
            $rootException = $exception;
            foreach ($exception->getWrappedExceptions() as $wrappedException) {
                $rootException = $wrappedException;

                break;
            }

            return new JsonResponse([
                'error' => $rootException->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable) {
            return new JsonResponse([
                'error' => 'Internal server error.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
