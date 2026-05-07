<?php
// src/Repository/VidangeVehiculeRepository.php

namespace App\Repository;

use App\Entity\VidangeVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VidangeVehicule>
 *
 * @method VidangeVehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method VidangeVehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method VidangeVehicule[]    findAll()
 * @method VidangeVehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VidangeVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VidangeVehicule::class);
    }

    public function save(VidangeVehicule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VidangeVehicule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Méthodes personnalisées

    /**
     * Trouve les vidanges d'une affectation spécifique
     */
    public function findByAffectation($affectationId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.affectation = :affectationId')
            ->setParameter('affectationId', $affectationId)
            ->orderBy('v.dateVidange', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Trouve les vidanges d'un véhicule spécifique (via l'affectation)
     */
    public function findByVehicule(int $vehiculeId): array
    {
        return $this->createQueryBuilder('v')
            ->join('v.affectation', 'a')
            ->join('a.id_vehicule', 'v2')
            ->where('v2.id = :vehiculeId')
            ->setParameter('vehiculeId', $vehiculeId)
            ->orderBy('v.dateVidange', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée une query pour les vidanges d'un véhicule spécifique
     */
    public function findByVehiculeQuery(int $vehiculeId)
    {
        return $this->createQueryBuilder('v')
            ->join('v.affectation', 'a')
            ->join('a.id_vehicule', 'v2')
            ->where('v2.id = :vehiculeId')
            ->setParameter('vehiculeId', $vehiculeId)
            ->orderBy('v.dateVidange', 'DESC')
            ->getQuery();
    }

    /**
     * Crée une query pour les vidanges d'une région spécifique
     */
    public function findByRegionQuery($regionId)
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.affectation', 'a')
            ->leftJoin('a.id_region', 'r')
            ->andWhere('r.id = :regionId')
            ->setParameter('regionId', $regionId)
            ->orderBy('v.dateVidange', 'DESC')
            ->getQuery();
    }

    /**
     * Crée une query pour les vidanges d'une région spécifique ou toutes les vidanges si région est null
     */
    public function findByRegionOrAllQuery($regionId = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->orderBy('v.dateVidange', 'DESC');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery();
    }

    /**
     * Trouve la dernière vidange d'un véhicule
     */
    public function findLastVidangeByVehicule($vehiculeId): ?VidangeVehicule
    {
        return $this->createQueryBuilder('v')
            ->join('v.affectation', 'a')
            ->andWhere('a.id_vehicule = :vehiculeId')
            ->setParameter('vehiculeId', $vehiculeId)
            ->orderBy('v.dateVidange', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Trouve les vidanges dans une période donnée
     */
    public function findVidangesByPeriod(\DateTime $startDate, \DateTime $endDate, $regionId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.dateVidange BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('v.dateVidange', 'DESC');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les prochaines vidanges prévues
     */
    public function findUpcomingVidanges($regionId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->join('v.affectation', 'a')
            ->andWhere('a.is_ferme = false')
            ->orderBy('v.dateVidange', 'DESC');

        if ($regionId) {
            $qb->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Calcule le coût total de toutes les vidanges
     */
    public function getTotalCost($regionId = null): float
    {
        $qb = $this->createQueryBuilder('v')
            ->select('SUM(v.cout) as total');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        
        return $result ? (float) $result : 0;
    }

    /**
     * Trouve la prochaine vidange prévue
     */
    public function findNextScheduledVidange($regionId = null): ?VidangeVehicule
    {
        $qb = $this->createQueryBuilder('v')
            ->where('v.prochaineVidangePrevue IS NOT NULL')
            ->andWhere('v.prochaineVidangePrevue > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('v.prochaineVidangePrevue', 'ASC')
            ->setMaxResults(1);

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Compte le nombre de vidanges
     */
    public function countByRegion($regionId = null): int
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}