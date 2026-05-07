<?php

namespace App\Repository;

use App\Entity\Permis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permis>
 *
 * @method Permis|null find($id, $lockMode = null, $lockVersion = null)
 * @method Permis|null findOneBy(array $criteria, array $orderBy = null)
 * @method Permis[]    findAll()
 * @method Permis[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PermisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permis::class);
    }

    /**
     * @param string $categorie
     * @return Permis[]
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.categorie = :val')
            ->setParameter('val', $categorie)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param \DateTimeInterface $date
     * @return Permis[]
     */
    public function findByValiditeAvant(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.dateValidite < :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('p.dateValidite', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
