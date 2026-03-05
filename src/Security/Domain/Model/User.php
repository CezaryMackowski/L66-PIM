<?php

declare(strict_types=1);

namespace PIM\Security\Domain\Model;

use DateTimeImmutable;
use LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private Uuid $id;
    private string $email;

    /**
     * @var string[]
     */
    private array $roles;
    private string $password;
    private DateTimeImmutable $createdAt;

    /**
     * @param string[] $roles
     */
    public function __construct(
        Uuid $id,
        string $email,
        string $password,
        DateTimeImmutable $createdAt,
        array $roles = ['ROLE_USER'],
    ) {
        $this->id = $id;
        $this->email = mb_strtolower(trim($email));
        $this->password = $password;
        $this->createdAt = $createdAt;
        $this->roles = $roles;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = [
            'ROLE_USER',
            ...$this->roles,
        ];

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new LogicException('User email cannot be empty.');
        }

        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
