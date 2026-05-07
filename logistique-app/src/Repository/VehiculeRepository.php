<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 *
 * @method Vehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vehicule[]    findAll()
 * @method Vehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    public function findActiveVehicules()
    {
        return $this->createQueryBuilder('v')
            ->where('v.statut = :statut')
            ->setParameter('statut', true)
            ->getQuery()
            ->getResult();
    }
    public function search(string $query): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.immatriculation_vehicule LIKE :query')
            ->orWhere('v.modele LIKE :query')
            ->orWhere('v.marque LIKE :query')
            ->orWhere('v.numero_chassis LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }
}
