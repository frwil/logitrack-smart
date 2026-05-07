<?php

namespace App\Repository;

use App\Entity\BonReparation;
use App\Entity\StatutReparation;
use App\Entity\AffectationVehicule;
use App\Entity\PrestataireIntervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<BonReparation>
 *
 * @method BonReparation|null find($id, $lockMode = null, $lockVersion = null)
 * @method BonReparation|null findOneBy(array $criteria, array $orderBy = null)
 * @method BonReparation[]    findAll()
 * @method BonReparation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BonReparationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonReparation::class);
    }

    public function save(BonReparation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BonReparation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Retourne une query builder de base pour les bons de réparation
     */
    public function createQueryBuilderWithJoins(string $alias = 'b'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->leftJoin($alias . '.affectation', 'a')
            ->leftJoin('a.id_vehicule', 'v')
            ->leftJoin('a.id_chauffeur', 'c')
            ->leftJoin($alias . '.prestataire', 'p')
            ->leftJoin($alias . '.statut', 's')
            ->leftJoin($alias . '.centreCout', 'cc')
            ->addSelect('a', 'v', 'c', 'p', 's', 'cc');
    }

    /**
     * Trouve les bons de réparation avec filtres optionnels
     */
    public function findWithFilters(array $filters = [], string $orderBy = 'b.dateEntree', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilderWithJoins('b');

        // Filtre par statut
        if (!empty($filters['statut'])) {
            $qb->andWhere('b.statut = :statut')
                ->setParameter('statut', $filters['statut']);
        }

        // Filtre par prestataire
        if (!empty($filters['prestataire'])) {
            $qb->andWhere('b.prestataire = :prestataire')
                ->setParameter('prestataire', $filters['prestataire']);
        }

        // Filtre par centre de coût
        if (!empty($filters['centreCout'])) {
            $qb->andWhere('b.centreCout = :centreCout')
                ->setParameter('centreCout', $filters['centreCout']);
        }

        // Filtre par véhicule
        if (!empty($filters['vehicule'])) {
            $qb->andWhere('a.id_vehicule = :vehicule')
                ->setParameter('vehicule', $filters['vehicule']);
        }

        // Filtre par date de début
        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('b.dateEntree >= :dateDebut')
                ->setParameter('dateDebut', $filters['dateDebut']);
        }

        // Filtre par date de fin
        if (!empty($filters['dateFin'])) {
            $qb->andWhere('b.dateEntree <= :dateFin')
                ->setParameter('dateFin', $filters['dateFin']);
        }

        // Filtre par clôture
        if (isset($filters['cloture'])) {
            $qb->andWhere('b.cloture = :cloture')
                ->setParameter('cloture', $filters['cloture']);
        }

        // Filtre par retard
        if (!empty($filters['enRetard'])) {
            $qb->andWhere('b.cloture = false')
                ->andWhere('b.datePrevueSortie < :aujourdhui')
                ->setParameter('aujourdhui', new \DateTime());
        }

        $qb->orderBy($orderBy, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les bons de réparation en retard (non clôturés et date prévue dépassée)
     */
    public function findEnRetard(): array
    {
        return $this->createQueryBuilderWithJoins('b')
            ->andWhere('b.cloture = false')
            ->andWhere('b.datePrevueSortie < :aujourdhui')
            ->setParameter('aujourdhui', new \DateTime())
            ->orderBy('b.datePrevueSortie', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les bons de réparation non clôturés
     */
    public function findNonClotures(): array
    {
        return $this->createQueryBuilderWithJoins('b')
            ->andWhere('b.cloture = false')
            ->orderBy('b.datePrevueSortie', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les bons de réparation par statut
     */
    public function findByStatut(StatutReparation $statut): array
    {
        return $this->createQueryBuilderWithJoins('b')
            ->andWhere('b.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('b.dateEntree', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les bons de réparation par prestataire
     */
    public function findByPrestataire(PrestataireIntervention $prestataire): array
    {
        return $this->createQueryBuilderWithJoins('b')
            ->andWhere('b.prestataire = :prestataire')
            ->setParameter('prestataire', $prestataire)
            ->orderBy('b.dateEntree', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les bons de réparation par affectation
     */
    public function findByAffectation(AffectationVehicule $affectation): array
    {
        return $this->createQueryBuilderWithJoins('b')
            ->andWhere('b.affectation = :affectation')
            ->setParameter('affectation', $affectation)
            ->orderBy('b.dateEntree', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le coût total des réparations pour une période donnée
     */
    public function getCoutTotalPeriod(\DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $result = $this->createQueryBuilder('b')
            ->select('SUM(b.montantReparation + b.plusOuMoinsValueValeur) as coutTotal')
            ->where('b.dateEntree BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Calcule le coût moyen des réparations par prestataire
     */
    public function getCoutMoyenParPrestataire(): array
    {
        return $this->createQueryBuilder('b')
            ->select('p.nom as prestataire, AVG(b.montantReparation + b.plusOuMoinsValueValeur) as coutMoyen')
            ->leftJoin('b.prestataire', 'p')
            ->groupBy('b.prestataire')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les statistiques de réparation par mois pour une année donnée
     */
    public function getStatistiquesParMois(int $annee): array
    {
        return $this->createQueryBuilder('b')
            ->select('MONTH(b.dateEntree) as mois, COUNT(b.id) as nombre, SUM(b.montantReparation + b.plusOuMoinsValueValeur) as coutTotal')
            ->where('YEAR(b.dateEntree) = :annee')
            ->setParameter('annee', $annee)
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le prochain numéro de bon de réparation à attribuer
     */
    public function getNextNumero(): string
    {
        $lastNumero = $this->createQueryBuilder('b')
            ->select('b.numero')
            ->orderBy('b.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastNumero || !isset($lastNumero['numero'])) {
            return 'BR-' . date('Ymd') . '-001';
        }

        $lastNum = $lastNumero['numero'];
        $parts = explode('-', $lastNum);

        if (count($parts) >= 3 && is_numeric($parts[2])) {
            $nextNum = (int) $parts[2] + 1;
            return 'BR-' . date('Ymd') . '-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        }

        return 'BR-' . date('Ymd') . '-001';
    }

    /**
     * Trouve les bons de réparation avec pagination
     */
    public function findPaginated(int $page = 1, int $limit = 10, array $filters = []): array
    {
        $qb = $this->createQueryBuilderWithJoins('b')
            ->orderBy('b.dateEntree', 'DESC');

        // Appliquer les filtres
        if (!empty($filters['statut'])) {
            $qb->andWhere('b.statut = :statut')
                ->setParameter('statut', $filters['statut']);
        }

        if (!empty($filters['cloture'])) {
            $qb->andWhere('b.cloture = :cloture')
                ->setParameter('cloture', $filters['cloture']);
        }

        $query = $qb->getQuery();
        $query->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return $query->getResult();
    }

    /**
     * Compte le nombre total de bons de réparation avec filtres
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)');

        if (!empty($filters['statut'])) {
            $qb->andWhere('b.statut = :statut')
                ->setParameter('statut', $filters['statut']);
        }

        if (!empty($filters['cloture'])) {
            $qb->andWhere('b.cloture = :cloture')
                ->setParameter('cloture', $filters['cloture']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findMaintenanceCostsByPeriod(): array
    {
        $dateDebut = new \DateTime();
        $dateDebut->modify('-12 months');

        return $this->createQueryBuilderWithJoins('b')
            ->andWhere('b.dateEntree >= :dateDebut')
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('b.dateEntree', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
