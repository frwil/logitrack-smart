<?php
// src/Service/WorkflowReparationService.php

namespace App\Service;

use App\Entity\BonReparation;
use App\Entity\StatutReparation;
use App\Workflow\ReparationWorkflow;
use Doctrine\ORM\EntityManagerInterface;

class WorkflowReparationService
{
    private ReparationWorkflow $workflow;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->workflow = new ReparationWorkflow();
        $this->entityManager = $entityManager;
    }

    public function changerStatut(BonReparation $bonReparation, StatutReparation $nouveauStatut): bool
    {
        $success = $this->workflow->changerStatut($bonReparation, $nouveauStatut);
        
        if ($success) {
            $this->entityManager->persist($bonReparation);
            $this->entityManager->flush();
        }
        
        return $success;
    }

    public function getStatutsSuivantsPossibles(BonReparation $bonReparation): array
    {
        $statutActuel = $bonReparation->getStatut();
        
        if (!$statutActuel) {
            return [];
        }
        
        return $this->workflow->getStatutsSuivantsPossibles($statutActuel);
    }

    public function peutChangerStatut(BonReparation $bonReparation, StatutReparation $nouveauStatut): bool
    {
        $statutActuel = $bonReparation->getStatut();
        
        if (!$statutActuel) {
            return false;
        }
        
        return $this->workflow->peutChangerStatut($statutActuel, $nouveauStatut);
    }
}