<?php
// src/Repository/AuditLogRepository.php
namespace App\Repository;

use App\Entity\AuditLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuditLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les logs d'audit pour une entité spécifique
     */
    public function findByEntity(string $entity, int $entityId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.entity = :entity')
            ->andWhere('a.entity_id = :entityId')
            ->setParameter('entity', $entity)
            ->setParameter('entityId', $entityId)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs d'audit par action
     */
    public function findByAction(string $action): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs d'audit par utilisateur
     */
    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les logs d'audit dans une période donnée
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.created_at BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}