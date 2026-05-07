<?php

namespace App\Repository;

use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voyage>
 *
 * @method Voyage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Voyage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Voyage[]    findAll()
 * @method Voyage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    public function countThisMonth(): int
    {
        $startDate = new \DateTime('first day of this month');
        $endDate = new \DateTime('last day of this month');
        
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.dateVoyage BETWEEN :start AND :end')
            ->setParameter('start', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('end', $endDate->format('Y-m-d 23:59:59'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByAffectationAndPeriod($affectationId, $dateDebut, $dateFin, $regionId = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.affectation = :affectationId')
            ->andWhere('v.dateVoyage BETWEEN :debut AND :fin')
            ->setParameter('affectationId', $affectationId)
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->orderBy('v.dateVoyage', 'ASC');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByPeriod($dateDebut, $dateFin, $regionId = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.dateVoyage BETWEEN :debut AND :fin')
            ->setParameter('debut', $dateDebut)
            ->setParameter('fin', $dateFin)
            ->orderBy('v.dateVoyage', 'ASC');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->leftJoin('a.id_region', 'r')
               ->andWhere('r.id = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findTripsByPeriod($dateDebut = null, $dateFin = null, $regionId = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.affectation', 'a')
            ->leftJoin('a.id_vehicule', 'veh')
            ->leftJoin('v.voyageVehicules', 'vv')
            ->leftJoin('vv.destination', 'dest')
            ->leftJoin('v.typeChargement', 'tc')
            ->addSelect('a', 'veh', 'vv', 'dest', 'tc')
            ->orderBy('v.dateVoyage', 'DESC');

        if ($dateDebut && $dateFin) {
            $qb->andWhere('v.dateVoyage BETWEEN :debut AND :fin')
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin);
        }

        if ($regionId) {
            $qb->andWhere('a.id_region = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getResult();
    }

    public function getMonthlyStats(int $year, ?int $vehiculeId = null, $regionId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.affectation', 'a')
            ->leftJoin('a.id_vehicule', 'veh')
            ->select([
                "MONTH(v.datePrevueVoyage) as month_prevue",
                "MONTH(v.dateVoyage) as month_reelle",
                "COUNT(v.id) as total",
                "SUM(CASE WHEN MONTH(v.dateVoyage) = MONTH(v.datePrevueVoyage) THEN 1 ELSE 0 END) as respecte_delai",
                "SUM(CASE WHEN v.dateVoyage > v.datePrevueVoyage THEN 1 ELSE 0 END) as en_retard",
                "SUM(CASE WHEN v.dateVoyage < v.datePrevueVoyage THEN 1 ELSE 0 END) as en_avance",
                "SUM(v.quantiteChargement) as total_chargement",
                "SUM(CASE WHEN MONTH(v.dateVoyage) = MONTH(v.datePrevueVoyage) THEN v.quantiteChargement ELSE 0 END) as chargement_dans_delai",
                "SUM(CASE WHEN v.dateVoyage > v.datePrevueVoyage THEN v.quantiteChargement ELSE 0 END) as chargement_retard",
                "SUM(CASE WHEN v.dateVoyage < v.datePrevueVoyage THEN v.quantiteChargement ELSE 0 END) as chargement_avance"
            ])
            ->where('YEAR(v.datePrevueVoyage) = :year OR YEAR(v.dateVoyage) = :year')
            ->setParameter('year', $year)
            ->groupBy('month_prevue, month_reelle')
            ->orderBy('month_prevue', 'ASC');

        if ($vehiculeId) {
            $qb->andWhere('veh.id = :vehiculeId')
                ->setParameter('vehiculeId', $vehiculeId);
        }

        if ($regionId) {
            $qb->andWhere('a.id_region = :regionId')
               ->setParameter('regionId', $regionId);
        }

        $results = $qb->getQuery()->getResult();

        // Organiser les données par mois
        $monthlyStats = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyStats[$i] = [
                'prevus' => 0,
                'realises' => 0,
                'respecte_delai' => 0,
                'en_retard' => 0,
                'en_avance' => 0,
                'total_chargement' => 0,
                'chargement_dans_delai' => 0,
                'chargement_retard' => 0,
                'chargement_avance' => 0
            ];
        }

        foreach ($results as $result) {
            $month = $result['month_prevue'] ?: $result['month_reelle'];
            if ($month >= 1 && $month <= 12) {
                $monthlyStats[$month]['prevus'] += $result['total'];
                $monthlyStats[$month]['realises'] += $result['total'];
                $monthlyStats[$month]['respecte_delai'] += $result['respecte_delai'];
                $monthlyStats[$month]['en_retard'] += $result['en_retard'];
                $monthlyStats[$month]['en_avance'] += $result['en_avance'];
                $monthlyStats[$month]['total_chargement'] += $result['total_chargement'];
                $monthlyStats[$month]['chargement_dans_delai'] += $result['chargement_dans_delai'];
                $monthlyStats[$month]['chargement_retard'] += $result['chargement_retard'];
                $monthlyStats[$month]['chargement_avance'] += $result['chargement_avance'];
            }
        }

        return $monthlyStats;
    }

    public function getAvailableYears($regionId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->select('DISTINCT YEAR(v.dateVoyage) as year')
            ->orderBy('year', 'DESC');

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->andWhere('a.id_region = :regionId')
               ->setParameter('regionId', $regionId);
        }

        $results = $qb->getQuery()->getResult();
        $years = [];

        foreach ($results as $result) {
            if ($result['year']) {
                $years[] = $result['year'];
            }
        }

        return $years;
    }

    public function getStatsByLoadingType(int $year, ?int $vehiculeId, int $typeChargementId, $regionId = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
        SELECT
            COALESCE(SUM(v.qte_chargement), 0) as total,
            COALESCE(SUM(CASE WHEN v.date_voyage <= v.date_prevue_voyage THEN v.qte_chargement ELSE 0 END), 0) as dans_delai,
            COALESCE(SUM(CASE WHEN v.date_voyage < v.date_prevue_voyage THEN v.qte_chargement ELSE 0 END), 0) as en_avance,
            COALESCE(SUM(CASE WHEN v.date_voyage > v.date_prevue_voyage THEN v.qte_chargement ELSE 0 END), 0) as en_retard
        FROM voyage v
        LEFT JOIN affectation_vehicule a ON v.id_affectation = a.id
        LEFT JOIN vehicule veh ON a.id_vehicule_id = veh.id
        WHERE (YEAR(v.date_prevue_voyage) = :year OR YEAR(v.date_voyage) = :year)
        AND v.id_type_chargement = :typeChargementId
    ";

        $params = [
            'year' => $year,
            'typeChargementId' => $typeChargementId
        ];

        if ($vehiculeId) {
            $sql .= " AND veh.id = :vehiculeId";
            $params['vehiculeId'] = $vehiculeId;
        }

        if ($regionId) {
            $sql .= " AND a.id_region_id = :regionId";
            $params['regionId'] = $regionId;
        }

        $stmt = $conn->executeQuery($sql, $params);
        $result = $stmt->fetchAssociative();

        return [
            'total' => (float) ($result['total'] ?? 0),
            'dans_delai' => (float) ($result['dans_delai'] ?? 0),
            'en_avance' => (float) ($result['en_avance'] ?? 0),
            'en_retard' => (float) ($result['en_retard'] ?? 0)
        ];
    }

    public function findFuelConsumptionStats($dateDebut = null, $dateFin = null, $affectationId = null, $regionId = null)
    {
        $qb = $this->createQueryBuilder('v')
            ->select([
                'SUM(v.quantiteCarburant) as total_carburant',
                'SUM(d.distance) as total_distance',
                'AVG(v.quantiteCarburant / d.distance * 100) as consommation_moyenne'
            ])
            ->leftJoin('v.voyageVehicules', 'vv')
            ->leftJoin('vv.destination', 'd')
            ->where('v.quantiteCarburant > 0')
            ->andWhere('d.distance > 0');

        if ($dateDebut && $dateFin) {
            $qb->andWhere('v.dateVoyage BETWEEN :debut AND :fin')
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin);
        }

        if ($affectationId) {
            $qb->andWhere('v.affectation = :affectationId')
                ->setParameter('affectationId', $affectationId);
        }

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->andWhere('a.id_region = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findConsumptionEvolution(\DateTime $startDate, \DateTime $endDate, ?int $affectationId = null, $regionId = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->select([
                "DATE_FORMAT(v.dateVoyage, '%d/%m') as date",
                'AVG(v.quantiteCarburant / d.distance * 100) as consommation_moyenne',
                'SUM(v.quantiteCarburant) as total_carburant',
                'SUM(d.distance) as total_distance'
            ])
            ->leftJoin('v.voyageVehicules', 'vv')
            ->leftJoin('vv.destination', 'd')
            ->where('v.dateVoyage BETWEEN :start AND :end')
            ->andWhere('v.quantiteCarburant > 0')
            ->andWhere('d.distance > 0')
            ->setParameter('start', $startDate->format('Y-m-d 00:00:00'))
            ->setParameter('end', $endDate->format('Y-m-d 23:59:59'))
            ->groupBy('date')
            ->orderBy('v.dateVoyage', 'ASC');

        if ($affectationId) {
            $qb->andWhere('v.affectation = :affectationId')
                ->setParameter('affectationId', $affectationId);
        }

        if ($regionId) {
            $qb->leftJoin('v.affectation', 'a')
               ->andWhere('a.id_region = :regionId')
               ->setParameter('regionId', $regionId);
        }

        return $qb->getQuery()->getResult();
    }
}