<?php
// src/Workflow/ReparationWorkflow.php

namespace App\Workflow;

use App\Entity\BonReparation;
use App\Entity\StatutReparation;

class ReparationWorkflow
{
    private const TRANSITIONS = [
        StatutReparation::EN_ATTENTE => [StatutReparation::EN_COURS, StatutReparation::ANNULE],
        StatutReparation::EN_COURS => [StatutReparation::TERMINE, StatutReparation::ANNULE],
        StatutReparation::TERMINE => [],
        StatutReparation::ANNULE => [],
    ];

    public function getTransitionsPossibles(StatutReparation $statutActuel): array
    {
        return self::TRANSITIONS[$statutActuel->getLibelle()] ?? [];
    }

    public function peutChangerStatut(StatutReparation $statutActuel, StatutReparation $nouveauStatut): bool
    {
        return in_array($nouveauStatut->getLibelle(), $this->getTransitionsPossibles($statutActuel), true);
    }

    public function changerStatut(BonReparation $bonReparation, StatutReparation $nouveauStatut): bool
    {
        $statutActuel = $bonReparation->getStatut();

        if (!$statutActuel || !$this->peutChangerStatut($statutActuel, $nouveauStatut)) {
            return false;
        }

        $bonReparation->setStatut($nouveauStatut);
        
        // Logique supplémentaire selon le statut
        switch ($nouveauStatut->getLibelle()) {
            case StatutReparation::TERMINE:
                $bonReparation->setDateFinReparation(new \DateTime());
                $bonReparation->setCloture(true);
                break;
            case StatutReparation::ANNULE:
                $bonReparation->setCloture(true);
                break;
        }

        return true;
    }

    public function getStatutsSuivantsPossibles(StatutReparation $statutActuel): array
    {
        return array_map(function ($libelle) {
            return ['libelle' => $libelle];
        }, $this->getTransitionsPossibles($statutActuel));
    }
}