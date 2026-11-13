<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countForService(Service $service): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.service = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByPaymentStatus(string $status): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.paymentStatus = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Booking[]
     */
    public function findFiltered(
        ?string $search = null,
        ?string $status = null,
        ?string $paymentStatus = null,
        ?int $staffId = null,
        ?int $serviceId = null,
    ): array {
        $query = $this->createQueryBuilder('b')
            ->innerJoin('b.service', 's')
            ->addSelect('s')
            ->orderBy('b.createdAt', 'DESC');

        if ($staffId !== null) {
            $query
                ->innerJoin('b.createdBy', 'u')
                ->addSelect('u')
                ->andWhere('u.id = :staffId')
                ->setParameter('staffId', $staffId);
        } else {
            $query->leftJoin('b.createdBy', 'u')->addSelect('u');
        }

        if ($serviceId !== null) {
            $query->andWhere('s.id = :serviceId')->setParameter('serviceId', $serviceId);
        }

        if ($search !== null && trim($search) !== '') {
            $query
                ->andWhere('(b.trackingCode LIKE :search OR b.customerName LIKE :search OR b.customerEmail LIKE :search OR u.name LIKE :search OR u.username LIKE :search)')
                ->setParameter('search', '%'.trim($search).'%');
        }

        if ($status !== null) {
            $query->andWhere('b.status = :status')->setParameter('status', $status);
        }

        if ($paymentStatus !== null) {
            $query->andWhere('b.paymentStatus = :paymentStatus')->setParameter('paymentStatus', $paymentStatus);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @return Booking[]
     */
    public function findRecent(int $limit = 6): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.service', 's')
            ->addSelect('s')
            ->leftJoin('b.createdBy', 'u')
            ->addSelect('u')
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
