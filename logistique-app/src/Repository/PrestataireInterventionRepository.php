<?php

namespace App\Repository;

use App\Entity\PrestataireIntervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PrestataireIntervention>
 */
class PrestataireInterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrestataireIntervention::class);
    }

    // Méthodes personnalisées si nécessaire

    /**
     * Trouve les prestataires par nom
     */
    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.nom LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre d'interventions par prestataire
     */
    public function countInterventionsByPrestataire(): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.nom, COUNT(v.id) as vidanges, COUNT(b.id) as reparations')
            ->leftJoin('p.vidanges', 'v')
            ->leftJoin('p.bonReparations', 'b')
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();
    }

    // Ajoutez d'autres méthodes personnalisées selon vos besoins
}