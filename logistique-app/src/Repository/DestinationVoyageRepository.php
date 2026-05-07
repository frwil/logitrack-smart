<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\DestinationVoyage;

class DestinationVoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DestinationVoyage::class);
    }

    public function findByRegion($regionId)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.region = :regionId')
            ->setParameter('regionId', $regionId)
            ->getQuery()
            ->getResult();
    }

    public function findByLibelle($libelle)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.libelle LIKE :libelle')
            ->setParameter('libelle', '%' . $libelle . '%')
            ->getQuery()
            ->getResult();
    }
}