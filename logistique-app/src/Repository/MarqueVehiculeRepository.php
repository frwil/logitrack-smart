<?php

namespace App\Repository;

use App\Entity\MarqueVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarqueVehicule>
 *
 * @method MarqueVehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method MarqueVehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method MarqueVehicule[]    findAll()
 * @method MarqueVehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarqueVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarqueVehicule::class);
    }
}
