<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Http\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use OpenApi\Attributes as OA;
use PIM\Security\Application\Command\RegisterUserCommand;
use PIM\Security\Domain\Exception\UserAlreadyExists;
use PIM\Security\Domain\Repository\UserRepositoryInterface;
use PIM\Security\Infrastructure\Http\Request\RegisterRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthenticationSuccessHandler $authenticationSuccessHandler,
    ) {
    }

    #[OA\Post(
        path: '/api/register',
        summary: 'Register user',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'User registered and authenticated',
                content: new OA\JsonContent(
                    required: ['token', 'refresh_token', 'refresh_token_expiration'],
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...'),
                        new OA\Property(property: 'refresh_token', type: 'string', example: '66fa087da651a8413b4ea26e50a25f79...'),
                        new OA\Property(property: 'refresh_token_expiration', type: 'integer', example: 1772795466),
                    ],
                ),
            ),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'User already exists'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Internal server error'),
        ],
    )]
    #[Route(path: '/api/register', name: 'security_register', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        RegisterRequest $registerRequest,
    ): Response {
        try {
            $userId = Uuid::v4();
            $email = mb_strtolower(trim($registerRequest->email));

            $this->messageBus->dispatch(new RegisterUserCommand(
                $userId,
                $email,
                $registerRequest->password,
            ));

            $user = $this->userRepository->findById($userId);
            if (null === $user) {
                return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return $this->authenticationSuccessHandler->handleAuthenticationSuccess($user);
        } catch (HandlerFailedException $exception) {
            $rootException = $exception;
            foreach ($exception->getWrappedExceptions() as $wrappedException) {
                $rootException = $wrappedException;

                break;
            }

            if ($rootException instanceof UserAlreadyExists) {
                return new JsonResponse(['error' => $rootException->getMessage()], Response::HTTP_CONFLICT);
            }

            return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable) {
            return new JsonResponse(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
