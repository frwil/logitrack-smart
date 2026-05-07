<?php

namespace App\Repository;

use App\Entity\AffectationVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffectationVehicule>
 *
 * @method AffectationVehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method AffectationVehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method AffectationVehicule[]    findAll()
 * @method AffectationVehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AffectationVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectationVehicule::class);
    }

    public function findWithFilters(?int $entityId = null, ?int $regionId = null, ?string $status = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.id_vehicule', 'v')
            ->leftJoin('a.id_chauffeur', 'c')
            ->leftJoin('a.id_type_utilisation', 'tu')
            ->leftJoin('a.id_entite', 'e')
            ->leftJoin('a.id_region', 'r')
            ->addSelect('v', 'c', 'tu', 'e', 'r');

        // Filtre par entité
        if ($entityId) {
            $qb->andWhere('e.id = :entityId')
                ->setParameter('entityId', $entityId);
        }

        // Filtre par région
        if ($regionId) {
            $qb->andWhere('r.id = :regionId')
                ->setParameter('regionId', $regionId);
        }

        // Filtre par statut
        if ($status === 'active') {
            $qb->andWhere('a.is_ferme = false OR a.date_fin_affectation > :now OR a.date_fin_affectation IS NULL')
                ->setParameter('now', new \DateTime());
        } elseif ($status === 'closed') {
            $qb->andWhere('a.is_ferme = true OR a.date_fin_affectation <= :now')
                ->setParameter('now', new \DateTime());
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return AffectationVehicule[] Returns an array of AffectationVehicule objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AffectationVehicule
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
