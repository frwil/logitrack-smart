<?php

namespace App\Repository;

use App\Entity\TypeDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeDocument>
 */
class TypeDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeDocument::class);
    }

    // Méthode pour trouver les types de documents par nom
    public function findByNom(string $nom): ?TypeDocument
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.nom = :nom')
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // Méthode pour trouver tous les types de documents actifs
    public function findAllActive()
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}