<?php
// src/DataFixtures/StatutReparationFixtures.php

namespace App\DataFixtures;

use App\Entity\StatutReparation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StatutReparationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Vérifier si des statuts existent déjà pour éviter les doublons
        $existingStatuts = $manager->getRepository(StatutReparation::class)->findAll();
        if (!empty($existingStatuts)) {
            return;
        }

        $statuts = [
            [
                'libelle' => StatutReparation::EN_ATTENTE,
                'description' => 'La réparation est en attente de prise en charge par un technicien',
                'couleur' => '#ffc107',
                'ordre' => 1,
                'estActif' => true,
                'reference' => 'en_attente',
            ],
            [
                'libelle' => StatutReparation::EN_COURS,
                'description' => 'La réparation est en cours de réalisation',
                'couleur' => '#17a2b8',
                'ordre' => 2,
                'estActif' => true,
                'reference' => 'en_cours',
            ],
            [
                'libelle' => StatutReparation::TERMINE,
                'description' => 'La réparation a été terminée avec succès',
                'couleur' => '#28a745',
                'ordre' => 3,
                'estActif' => true,
                'reference' => 'termine',
            ],
            [
                'libelle' => StatutReparation::ANNULE,
                'description' => 'La réparation a été annulée',
                'couleur' => '#dc3545',
                'ordre' => 4,
                'estActif' => true,
                'reference' => 'annule',
            ],
        ];

        foreach ($statuts as $statutData) {
            $statut = new StatutReparation();
            $statut->setLibelle($statutData['libelle']);
            $statut->setDescription($statutData['description']);
            $statut->setCouleur($statutData['couleur']);
            $statut->setOrdre($statutData['ordre']);
            $statut->setEstActif($statutData['estActif']);

            $manager->persist($statut);
            $this->addReference('statut_' . $statutData['reference'], $statut);
        }

        $manager->flush();
    }
}