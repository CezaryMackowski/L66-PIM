<?php

declare(strict_types=1);

namespace PIM\Security\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PIM\Security\Domain\Model\User;
use PIM\Security\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<User>
 */
final class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findById(Uuid $uuid): ?User
    {
        $entity = $this->getEntityManager()->find(User::class, $uuid);

        return $entity instanceof User ? $entity : null;
    }

    public function findByEmail(string $email): ?User
    {
        $entity = $this->findOneBy(['email' => mb_strtolower(trim($email))]);

        return $entity instanceof User ? $entity : null;
    }
}
