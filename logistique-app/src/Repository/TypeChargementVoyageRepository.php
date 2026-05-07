<?php

namespace App\Repository;

use App\Entity\TypeChargementVoyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeChargementVoyage>
 *
 * @method TypeChargementVoyage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeChargementVoyage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeChargementVoyage[]    findAll()
 * @method TypeChargementVoyage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeChargementVoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeChargementVoyage::class);
    }
}