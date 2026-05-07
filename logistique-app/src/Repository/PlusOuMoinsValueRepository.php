<?php

namespace App\Repository;

use App\Entity\PlusOuMoinsValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlusOuMoinsValue>
 *
 * @method PlusOuMoinsValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method PlusOuMoinsValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method PlusOuMoinsValue[]    findAll()
 * @method PlusOuMoinsValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PlusOuMoinsValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlusOuMoinsValue::class);
    }

    public function save(PlusOuMoinsValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PlusOuMoinsValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les valeurs actives, ordonnées par type puis par libellé
     */
    public function findActivesOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.estActif = true')
            ->orderBy('p.typeValeur', 'DESC') // Plus-values d'abord
            ->addOrderBy('p.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les plus-values actives
     */
    public function findPlusValues(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.estActif = true')
            ->andWhere('p.typeValeur = :type')
            ->setParameter('type', true)
            ->orderBy('p.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les moins-values actives
     */
    public function findMoinsValues(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.estActif = true')
            ->andWhere('p.typeValeur = :type')
            ->setParameter('type', false)
            ->orderBy('p.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une valeur par son libellé
     */
    public function findByLibelle(string $libelle): ?PlusOuMoinsValue
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.libelle = :libelle')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si un libellé existe déjà (pour éviter les doublons)
     */
    public function libelleExists(string $libelle, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.libelle = :libelle')
            ->setParameter('libelle', $libelle);

        if ($excludeId !== null) {
            $qb->andWhere('p.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}