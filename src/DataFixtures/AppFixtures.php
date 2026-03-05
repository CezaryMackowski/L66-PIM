<?php

declare(strict_types=1);

namespace PIM\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use PIM\Security\Domain\Model\User;
use Psr\Clock\ClockInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

final class AppFixtures extends Fixture
{
    private array $users = [
        ['email' => 'john.doe@example.com', 'password' => 'StrongPass123!', 'roles' => ['ROLE_USER']],
        ['email' => 'jane.doe@example.com', 'password' => 'StrongPass123!', 'roles' => ['ROLE_USER']],
        ['email' => 'admin@example.com', 'password' => 'AdminPass123!', 'roles' => ['ROLE_ADMIN']],
    ];

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $now = $this->clock->now();
        foreach ($this->users as $data) {
            $user = new User(
                Uuid::v4(),
                $data['email'],
                '',
                $now,
                $data['roles'],
            );
            $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));

            $manager->persist($user);
        }
    }
}
