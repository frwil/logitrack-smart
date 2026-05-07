<?php

namespace App\Repository;

use App\Entity\VoyageVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VoyageVehicule>
 *
 * @method VoyageVehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method VoyageVehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method VoyageVehicule[]    findAll()
 * @method VoyageVehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoyageVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VoyageVehicule::class);
    }

    /**
     * Trouve les trajets de véhicules pour une région spécifique
     */
    public function findByRegion($regionId)
    {
        return $this->createQueryBuilder('vv')
            ->leftJoin('vv.voyage', 'v')
            ->leftJoin('v.affectation', 'a')
            ->leftJoin('a.id_region', 'r')
            ->andWhere('r.id = :regionId')
            ->setParameter('regionId', $regionId)
            ->orderBy('v.dateVoyage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}