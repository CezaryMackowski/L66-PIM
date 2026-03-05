<?php

declare(strict_types=1);

namespace PIM\Security\Application\Handler;

use PIM\Security\Application\Command\RegisterUserCommand;
use PIM\Security\Domain\Exception\UserAlreadyExists;
use PIM\Security\Domain\Model\User;
use PIM\Security\Domain\Repository\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws UserAlreadyExists
     */
    public function __invoke(RegisterUserCommand $command): void
    {
        if (null !== $this->userRepository->findByEmail($command->email)) {
            throw UserAlreadyExists::ByEmail($command->email);
        }

        $user = new User(
            $command->userId,
            $command->email,
            '',
            $this->clock->now(),
        );

        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);
    }
}
