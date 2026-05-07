<?php
// src/DataFixtures/CentreCoutFixtures.php

namespace App\DataFixtures;

use App\Entity\CentreCout;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CentreCoutFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Vérifier si des centres de coût existent déjà pour éviter les doublons
        $existingCentres = $manager->getRepository(CentreCout::class)->findAll();
        if (!empty($existingCentres)) {
            return;
        }

        $centres = [
            [
                'libelle' => 'ADMINISTRATION', 
                'description' => 'Centre de coût pour les dépenses administratives',
                'reference' => 'administration'
            ],
            [
                'libelle' => 'COMMERCIALE', 
                'description' => 'Centre de coût pour les dépenses commerciales',
                'reference' => 'commerciale'
            ],
            [
                'libelle' => 'DIRECTION GENERALE', 
                'description' => 'Centre de coût pour les dépenses de direction',
                'reference' => 'direction_generale'
            ],
            [
                'libelle' => 'FERMES', 
                'description' => 'Centre de coût pour les dépenses des fermes',
                'reference' => 'fermes'
            ],
        ];

        foreach ($centres as $centreData) {
            $centre = new CentreCout();
            $centre->setLibelle($centreData['libelle']);
            $centre->setDescription($centreData['description']);
            // Les autres champs (dateCreation, estActif) sont définis automatiquement dans le constructeur
            
            $manager->persist($centre);
            $this->addReference('centre_cout_' . $centreData['reference'], $centre);
        }

        $manager->flush();
    }
}