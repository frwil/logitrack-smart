<?php

namespace App\Repository;

use App\Entity\ModeUtilisationVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ModeUtilisationVehicule>
 */
class ModeUtilisationVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ModeUtilisationVehicule::class);
    }
}
