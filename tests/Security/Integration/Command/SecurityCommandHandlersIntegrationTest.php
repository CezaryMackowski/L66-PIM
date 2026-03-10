<?php

declare(strict_types=1);

namespace PIM\Tests\Security\Integration\Command;

use DateTimeImmutable;
use PIM\Security\Application\Command\LogoutCommand;
use PIM\Security\Application\Command\RegisterUserCommand;
use PIM\Security\Application\Handler\LogoutHandler;
use PIM\Security\Application\Handler\RegisterUserHandler;
use PIM\Security\Domain\Exception\UserAlreadyExists;
use PIM\Tests\Shared\Integration\IntegrationKernelTestCase;
use Symfony\Component\Uid\Uuid;

final class SecurityCommandHandlersIntegrationTest extends IntegrationKernelTestCase
{
    private RegisterUserHandler $registerUserHandler;
    private LogoutHandler $logoutHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerUserHandler = $this->container()->get(RegisterUserHandler::class);
        $this->logoutHandler = $this->container()->get(LogoutHandler::class);
    }

    public function testRegisterUserCreatesPersistedHashedUser(): void
    {
        // Arrange
        $email = 'User.Registered@Example.com';
        $plainPassword = 'StrongPass123!';
        $command = new RegisterUserCommand(Uuid::v4(), $email, $plainPassword);

        // Act
        ($this->registerUserHandler)($command);

        // Assert
        $row = $this->connection()->fetchAssociative(
            'SELECT email, password FROM users WHERE email = :email',
            ['email' => mb_strtolower($email)],
        );

        self::assertIsArray($row);
        self::assertSame('user.registered@example.com', $row['email'] ?? null);
        self::assertNotSame($plainPassword, $row['password'] ?? null);
        self::assertIsString($row['password'] ?? null);
        self::assertTrue(password_verify($plainPassword, (string) $row['password']));
    }

    public function testRegisterUserThrowsWhenEmailAlreadyExists(): void
    {
        // Arrange
        ($this->registerUserHandler)(new RegisterUserCommand(
            Uuid::v4(),
            'user@example.com',
            'StrongPass123!',
        ));

        // Assert
        $this->expectException(UserAlreadyExists::class);

        // Act
        ($this->registerUserHandler)(new RegisterUserCommand(
            Uuid::v4(),
            'USER@example.com',
            'StrongPass123!',
        ));
    }

    public function testLogoutDeletesExistingRefreshToken(): void
    {
        // Arrange
        $refreshToken = 'refresh-token-to-delete';
        $this->connection()->executeStatement(
            'INSERT INTO refresh_tokens (refresh_token, username, valid) VALUES (:refreshToken, :username, :valid)',
            [
                'refreshToken' => $refreshToken,
                'username' => 'user@example.com',
                'valid' => (new DateTimeImmutable('+1 day'))->format('Y-m-d H:i:s'),
            ],
        );

        // Act
        ($this->logoutHandler)(new LogoutCommand($refreshToken));

        // Assert
        $remaining = (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM refresh_tokens WHERE refresh_token = :refreshToken',
            ['refreshToken' => $refreshToken],
        );
        self::assertSame(0, $remaining);
    }

    public function testLogoutIgnoresUnknownRefreshToken(): void
    {
        // Arrange
        $command = new LogoutCommand('missing-refresh-token');

        // Act
        ($this->logoutHandler)($command);

        // Assert
        self::assertTrue(true);
    }
}
