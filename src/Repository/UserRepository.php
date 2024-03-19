<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByUsername(string $username): ?User
    {
        return $this->findOneBy(['email' => $username, 'active' => true]);
    }

    /**
     * @return User[]
     */
    public function findActiveUsersCreatedDuringLastWeek(): array
    {
        $lastWeekDate = new \DateTimeImmutable('-1 week');

        return $this->createQueryBuilder('u')
            ->where('u.active = :active')
            ->andWhere('u.createdAt >= :lastWeekDate')
            ->setParameter('active', true)
            ->setParameter('lastWeekDate', $lastWeekDate)
            ->getQuery()
            ->getResult();
    }

    public function add(User $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }
}
