<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\StatusHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatusHistory>
 */
final class StatusHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatusHistory::class);
    }

    /**
     * @return StatusHistory[]
     */
    public function findForBooking(Booking $booking): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.changedBy', 'u')
            ->addSelect('u')
            ->andWhere('h.booking = :booking')
            ->setParameter('booking', $booking)
            ->orderBy('h.createdAt', 'DESC')
            ->addOrderBy('h.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
