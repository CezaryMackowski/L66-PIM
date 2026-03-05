<?php

declare(strict_types=1);

namespace PIM\Security\Application\Handler;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use PIM\Security\Application\Command\LogoutCommand;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class LogoutHandler
{
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
    ) {
    }

    public function __invoke(LogoutCommand $command): void
    {
        $refreshToken = $this->refreshTokenManager->get($command->refreshToken);

        if (null === $refreshToken) {
            return;
        }

        $this->refreshTokenManager->delete($refreshToken);
    }
}
