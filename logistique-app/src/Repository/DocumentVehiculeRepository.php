<?php

namespace App\Repository;

use App\Entity\DocumentVehicule;
use App\Entity\Vehicule;
use App\Entity\TypeDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DocumentVehicule>
 */
class DocumentVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentVehicule::class);
    }

    // Méthode pour trouver les documents actifs d'un véhicule
    public function findActiveDocumentsByVehicule(Vehicule $vehicule): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.vehicule = :vehicule')
            ->andWhere('d.isActive = :isActive')
            ->setParameter('vehicule', $vehicule)
            ->setParameter('isActive', true)
            ->orderBy('d.typeDocument', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // Méthode pour trouver le document actif d'un type spécifique pour un véhicule
    public function findActiveDocumentByType(Vehicule $vehicule, TypeDocument $typeDocument): ?DocumentVehicule
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.vehicule = :vehicule')
            ->andWhere('d.typeDocument = :typeDocument')
            ->andWhere('d.isActive = :isActive')
            ->setParameter('vehicule', $vehicule)
            ->setParameter('typeDocument', $typeDocument)
            ->setParameter('isActive', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // Méthode pour désactiver tous les documents d'un type spécifique pour un véhicule
    public function deactivateAllDocumentsByType(Vehicule $vehicule, TypeDocument $typeDocument): void
    {
        $this->createQueryBuilder('d')
            ->update()
            ->set('d.isActive', ':isActive')
            ->where('d.vehicule = :vehicule')
            ->andWhere('d.typeDocument = :typeDocument')
            ->setParameter('isActive', false)
            ->setParameter('vehicule', $vehicule)
            ->setParameter('typeDocument', $typeDocument)
            ->getQuery()
            ->execute()
        ;
    }

    // Méthode pour trouver les documents expirant bientôt
    public function findExpiringDocuments(int $days = 30): array
    {
        $date = new \DateTime();
        $date->modify("+$days days");

        return $this->createQueryBuilder('d')
            ->andWhere('d.dateExpiration <= :date')
            ->andWhere('d.isActive = :isActive')
            ->setParameter('date', $date)
            ->setParameter('isActive', true)
            ->orderBy('d.dateExpiration', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // Méthode pour compter les véhicules avec des documents expirant
    public function countDocumentsExpirant(\DateTime $dateLimite): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.vehicule)')
            ->where('d.dateExpiration <= :dateLimite')
            ->andWhere('d.dateExpiration >= :today')
            ->setParameter('dateLimite', $dateLimite)
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Méthode pour compter les véhicules avec des documents expirés
    public function countDocumentsExpires(\DateTime $dateNow): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(DISTINCT d.vehicule)')
            ->where('d.dateExpiration < :today')
            ->setParameter('today', $dateNow)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Méthode pour trouver les documents nécessitant une attention
    public function findDocumentsAlerte(): array
    {
        $dateNow = new \DateTime();
        $dateLimite = new \DateTime('+30 days');
        
        $documents = $this->createQueryBuilder('d')
            ->innerJoin('d.vehicule', 'v')
            ->innerJoin('d.typeDocument', 't')
            ->where('d.dateExpiration <= :dateLimite')
            ->andWhere('v.statut = true')
            ->setParameter('dateLimite', $dateLimite)
            ->orderBy('d.dateExpiration', 'ASC')
            ->getQuery()
            ->getResult();
        
        $result = [];
        foreach ($documents as $document) {
            $interval = $dateNow->diff($document->getDateExpiration());
            $joursRestants = $interval->invert ? -$interval->days : $interval->days;
            
            $result[] = [
                'vehicule' => $document->getVehicule(),
                'document' => $document,
                'joursRestants' => $joursRestants
            ];
        }
        
        return $result;
    }

    
}