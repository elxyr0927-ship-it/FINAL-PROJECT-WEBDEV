<?php

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactMessage>
 */
final class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactMessage::class);
    }

    /**
     * @return ContactMessage[]
     */
    public function findForAdmin(string $filter, string $search): array
    {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')
            ->addSelect('u')
            ->orderBy('c.createdAt', 'DESC');

        if ($filter === 'archived') {
            $query->andWhere('c.deletedAt IS NOT NULL');
        } else {
            $query->andWhere('c.deletedAt IS NULL');
        }

        if ($filter === 'read' || $filter === 'unread') {
            $query->andWhere('c.isRead = :isRead')
                ->setParameter('isRead', $filter === 'read');
        }

        if ($search !== '') {
            $query->andWhere('(c.name LIKE :search OR c.email LIKE :search OR c.subject LIKE :search OR c.message LIKE :search)')
                ->setParameter('search', '%'.$search.'%');
        }

        return $query->getQuery()->getResult();
    }

    public function countUnread(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('c.isRead = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.deletedAt IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countArchived(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
