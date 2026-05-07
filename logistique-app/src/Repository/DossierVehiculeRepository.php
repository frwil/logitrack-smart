<?php

namespace App\Repository;

use App\Entity\DossierVehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DossierVehicule>
 */
class DossierVehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DossierVehicule::class);
    }

    // Méthode pour trouver un dossier par référence
    public function findByRefDossier(string $refDossier): ?DossierVehicule
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.refDossier = :refDossier')
            ->setParameter('refDossier', $refDossier)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // Méthode pour générer une nouvelle référence de dossier
    public function generateRefDossier(): string
    {
        $prefix = 'DV-';
        $hash = sha1(uniqid(mt_rand(), true));
        
        return $prefix . $hash;
    }

    public function ensureUniqueReference(DossierVehicule $dossier): void
    {
        $originalReference = $dossier->getRefDossier();
        $isUnique = false;
        $attempts = 0;

        while (!$isUnique && $attempts < 10) {
            try {
                $this->getEntityManager()->flush();
                $isUnique = true;
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // Régénérer une nouvelle référence en cas de conflit
                $dossier->generateReference();
                $attempts++;
            }
        }

        if (!$isUnique) {
            throw new \RuntimeException('Impossible de générer une référence unique après plusieurs tentatives');
        }
    }
}