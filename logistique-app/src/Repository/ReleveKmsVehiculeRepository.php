<?php

namespace App\Repository;

use App\Entity\ReleveKmsVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReleveKmsVehicule>
 *
 * @method ReleveKmsVehicule|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReleveKmsVehicule|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReleveKmsVehicule[]    findAll()
 * @method ReleveKmsVehicule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReleveKmsVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReleveKmsVehicule::class);
    }

    public function findMileageStatistics(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT 
            v.id, 
            v.immatriculation_vehicule as immatriculation,
            m.nom_modele as modele,
            MAX(r.kilometrage) as kilometrage,
            MAX(r.date_releve) as date_releve,
            (MAX(r.kilometrage) - MIN(r.kilometrage)) / GREATEST(TIMESTAMPDIFF(MONTH, MIN(r.date_releve), MAX(r.date_releve)), 1) as moyenne_mensuelle,
            CASE 
                WHEN COUNT(*) > 1 THEN 
                    ((MAX(r.kilometrage) - MIN(r.kilometrage)) / GREATEST(TIMESTAMPDIFF(MONTH, MIN(r.date_releve), MAX(r.date_releve)), 1)) - 
                    ((MAX(r.kilometrage) - MIN(r.kilometrage)) / GREATEST(TIMESTAMPDIFF(MONTH, MIN(r.date_releve), DATE_SUB(MAX(r.date_releve), INTERVAL 1 MONTH)), 1))
                ELSE 0
            END as evolution
        FROM releve_kms_vehicule r
        INNER JOIN affectation_vehicule a ON r.affectation_id = a.id
        INNER JOIN vehicule v ON a.id_vehicule_id = v.id
        LEFT JOIN modele_vehicule m ON v.modele_vehicule_id = m.id
        WHERE a.is_ferme = false
        GROUP BY v.id
    ';
        $stmt = $conn->executeQuery($sql);
        $results = $stmt->fetchAllAssociative();

        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'vehicule' => [
                    'immatriculation' => $result['immatriculation'],
                    'modele' => $result['modele']
                ],
                'dernierReleve' => [
                    'kilometrage' => (int) $result['kilometrage'],
                    'dateReleve' => new \DateTime($result['date_releve'])
                ],
                'moyenneMensuelle' => (float) $result['moyenne_mensuelle'],
                'evolution' => (float) $result['evolution']
            ];
        }

        return $data;
    }

    public function findMileageEvolution(int $affectationId, \DateTime $startDate, \DateTime $endDate): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
        SELECT 
            DATE(r.date_releve) as date,
            r.kilometrage
        FROM releve_kms_vehicule r
        WHERE r.affectation_id = :affectationId
        AND r.date_releve BETWEEN :startDate AND :endDate
        ORDER BY r.date_releve ASC
    ';

        $stmt = $conn->executeQuery($sql, [
            'affectationId' => $affectationId,
            'startDate' => $startDate->format('Y-m-d 00:00:00'),
            'endDate' => $endDate->format('Y-m-d 23:59:59')
        ]);

        return $stmt->fetchAllAssociative();
    }
}
