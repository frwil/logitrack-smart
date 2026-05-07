<?php
// src/DataFixtures/PlusOuMoinsValueFixtures.php

namespace App\DataFixtures;

use App\Entity\PlusOuMoinsValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class PlusOuMoinsValueFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Vérifier si des valeurs existent déjà pour éviter les doublons
        $existingValeurs = $manager->getRepository(PlusOuMoinsValue::class)->findAll();
        if (!empty($existingValeurs)) {
            return;
        }

        $valeurs = [
            [
                'libelle' => 'Valeur ajoutée', 
                'type' => PlusOuMoinsValue::TYPE_PLUS_VALUE,
                'description' => 'Augmentation de la valeur après réparation',
                'reference' => 'valeur_ajoutee'
            ],
            [
                'libelle' => 'Majoration', 
                'type' => PlusOuMoinsValue::TYPE_PLUS_VALUE,
                'description' => 'Majoration du coût due à des travaux supplémentaires',
                'reference' => 'majoration'
            ],
            [
                'libelle' => 'Dépréciation', 
                'type' => PlusOuMoinsValue::TYPE_MOINS_VALUE,
                'description' => 'Diminution de valeur malgré la réparation',
                'reference' => 'depreciation'
            ],
            [
                'libelle' => 'Dévalorisation', 
                'type' => PlusOuMoinsValue::TYPE_MOINS_VALUE,
                'description' => 'Perte de valeur suite à la réparation',
                'reference' => 'devalorisation'
            ],
        ];

        foreach ($valeurs as $valeurData) {
            $valeur = new PlusOuMoinsValue();
            $valeur->setLibelle($valeurData['libelle']);
            $valeur->setTypeValeur($valeurData['type']);
            $valeur->setDescription($valeurData['description']);
            // Les autres champs (dateCreation, estActif) sont définis automatiquement dans le constructeur
            
            $manager->persist($valeur);
            $this->addReference('plus_ou_moins_' . $valeurData['reference'], $valeur);
        }

        $manager->flush();
    }
}