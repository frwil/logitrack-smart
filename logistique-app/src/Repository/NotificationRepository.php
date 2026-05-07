<?php

// src/Repository/NotificationRepository.php
namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Récupère les notifications non lues d'un utilisateur
     */
    public function findUnreadByUser($user, $limit = null): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les notifications d'un utilisateur avec pagination
     */
    public function findByUser($user, $page = 1, $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de notifications non lues d'un utilisateur
     */
    public function countUnreadByUser($user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead($user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les notifications anciennes (plus de 30 jours)
     */
    public function removeOldNotifications(int $days = 30): int
    {
        $date = new \DateTime();
        $date->modify("-$days days");

        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les notifications par type
     */
    public function findByType($user, $type, $limit = null): array
    {
        $queryBuilder = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    // Vous pouvez ajouter d'autres méthodes personnalisés ici
}