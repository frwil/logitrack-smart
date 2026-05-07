<?php

namespace App\Repository;

use App\Entity\Chauffeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Chauffeur>
 *
 * @method Chauffeur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Chauffeur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Chauffeur[]    findAll()
 * @method Chauffeur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChauffeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chauffeur::class);
    }

    public function findDriverActivityStats(?\DateTime $startDate = null, ?\DateTime $endDate = null, ?string $statut = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.affectationVehicules', 'a')
            ->leftJoin('a.voyages', 'v')
            ->leftJoin('v.voyageVehicules', 'vv')
            ->leftJoin('vv.destination', 'd')
            ->select([
                'c',
                'COUNT(v.id) as nombreVoyages',
                'SUM(d.distance) as kilometrageTotal',
                'MAX(v.dateVoyage) as dernierVoyage'
            ])
            ->groupBy('c.id');

        if ($startDate && $endDate) {
            $qb->andWhere('v.dateVoyage BETWEEN :start AND :end')
                ->setParameter('start', $startDate->format('Y-m-d 00:00:00'))
                ->setParameter('end', $endDate->format('Y-m-d 23:59:59'));
        }

        if ($statut !== null) {
            $qb->andWhere('c.estActif = :statut')
                ->setParameter('statut', $statut === 'actif');
        }

        $results = $qb->getQuery()->getResult();

        $driverActivity = [];
        foreach ($results as $result) {
            $driverActivity[] = [
                'chauffeur' => $result[0],
                'nombreVoyages' => $result['nombreVoyages'],
                'kilometrageTotal' => $result['kilometrageTotal'] ?? 0,
                'dernierVoyage' => $result['dernierVoyage']
            ];
        }

        return $driverActivity;
    }
}
