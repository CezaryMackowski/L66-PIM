<?php

declare(strict_types=1);

namespace PIM\Security\Domain\Repository;

use PIM\Security\Domain\Model\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(Uuid $uuid): ?User;

    public function findByEmail(string $email): ?User;
}
