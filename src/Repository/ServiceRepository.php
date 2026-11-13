<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findFiltered(?string $search = null, ?string $status = null): array
    {
        $query = $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC');

        if ($search !== null && trim($search) !== '') {
            $query
                ->andWhere('(s.name LIKE :search OR s.description LIKE :search)')
                ->setParameter('search', '%'.trim($search).'%');
        }

        if ($status === 'active') {
            $query->andWhere('s.isActive = :active')->setParameter('active', true);
        } elseif ($status === 'inactive') {
            $query->andWhere('s.isActive = :active')->setParameter('active', false);
        }

        return $query->getQuery()->getResult();
    }
}
