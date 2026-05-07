<?php

namespace App\Repository;

use App\Entity\StatutReparation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StatutReparation>
 *
 * @method StatutReparation|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatutReparation|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatutReparation[]    findAll()
 * @method StatutReparation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatutReparationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatutReparation::class);
    }

    public function save(StatutReparation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(StatutReparation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les statuts actifs, ordonnés par leur ordre
     */
    public function findActifsOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.estActif = true')
            ->orderBy('s.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un statut par son libellé
     */
    public function findByLibelle(string $libelle): ?StatutReparation
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.libelle = :libelle')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le prochain ordre disponible pour un nouveau statut
     */
    public function findNextOrdre(): int
    {
        $result = $this->createQueryBuilder('s')
            ->select('MAX(s.ordre) as max_ordre')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result + 1;
    }

    /**
     * Trouve les statuts avec le nombre de bons de réparation associés
     */
    public function findWithCountBons(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s, COUNT(b.id) as nbBons')
            ->leftJoin('s.bonsReparation', 'b')
            ->groupBy('s.id')
            ->orderBy('s.ordre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Met à jour l'ordre des statuts après une suppression
     */
    public function reorderStatuts(): void
    {
        $statuts = $this->findBy([], ['ordre' => 'ASC']);
        
        $ordre = 1;
        foreach ($statuts as $statut) {
            $statut->setOrdre($ordre++);
        }
        
        $this->getEntityManager()->flush();
    }

    /**
     * Trouve les statuts initiaux (constants prédéfinis)
     */
    public function findInitialStatuts(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.libelle IN (:libelles)')
            ->setParameter('libelles', [
                StatutReparation::EN_ATTENTE,
                StatutReparation::EN_COURS,
                StatutReparation::TERMINE,
                StatutReparation::ANNULE
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un statut peut être supprimé (n'a pas de bons de réparation associés)
     */
    public function canDelete(StatutReparation $statut): bool
    {
        $count = $this->createQueryBuilder('s')
            ->select('COUNT(b.id)')
            ->leftJoin('s.bonsReparation', 'b')
            ->where('s.id = :id')
            ->setParameter('id', $statut->getId())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count === 0;
    }
}