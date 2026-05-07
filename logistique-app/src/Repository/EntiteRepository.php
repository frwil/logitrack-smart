<?php

namespace App\Repository;

use App\Entity\Entite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Entite>
 *
 * @method Entite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entite[]    findAll()
 * @method Entite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EntiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entite::class);
    }

    public function save(Entite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Entite $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
