<?php

namespace App\Repository;

use App\Entity\TypePermisVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypePermisVehicule>
 *
 * @method TypePermisVehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypePermisVehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypePermisVehicule[]    findAll()
 * @method TypePermisVehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypePermisVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypePermisVehicule::class);
    }
}