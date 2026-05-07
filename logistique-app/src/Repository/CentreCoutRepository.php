<?php

namespace App\Repository;

use App\Entity\CentreCout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CentreCout>
 *
 * @method CentreCout|null find($id, $lockMode = null, $lockVersion = null)
 * @method CentreCout|null findOneBy(array $criteria, array $orderBy = null)
 * @method CentreCout[]    findAll()
 * @method CentreCout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CentreCoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CentreCout::class);
    }

    public function save(CentreCout $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CentreCout $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les centres de coût actifs, ordonnés par libellé
     */
    public function findActifsOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.estActif = true')
            ->orderBy('c.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un centre de coût par son libellé
     */
    public function findByLibelle(string $libelle): ?CentreCout
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.libelle = :libelle')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les centres de coût avec le nombre de bons de réparation associés
     */
    public function findWithCountBons(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c, COUNT(b.id) as nbBons')
            ->leftJoin('c.bonsReparation', 'b')
            ->groupBy('c.id')
            ->orderBy('c.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les centres de coût avec le coût total des réparations
     */
    public function findWithCoutTotal(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c, SUM(b.montantReparation + b.plusOuMoinsValueValeur) as coutTotal')
            ->leftJoin('c.bonsReparation', 'b')
            ->groupBy('c.id')
            ->orderBy('coutTotal', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un libellé existe déjà (pour éviter les doublons)
     */
    public function libelleExists(string $libelle, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.libelle = :libelle')
            ->setParameter('libelle', $libelle);

        if ($excludeId !== null) {
            $qb->andWhere('c.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}