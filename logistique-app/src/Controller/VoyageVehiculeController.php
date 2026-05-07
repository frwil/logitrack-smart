<?php
// src/Controller/VoyageVehiculeController.php

namespace App\Controller;

use App\Entity\VoyageVehicule;
use App\Entity\DestinationVoyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\AuditLogger;

#[Route('/voyage/vehicule')]
class VoyageVehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/{id}/edit', name: 'app_voyage_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, VoyageVehicule $voyageVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage_vehicule.update')) {
            $this->auditLogger->log(
                'tentative_update', 
                'VoyageVehicule', 
                $voyageVehicule->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier un trajet de voyage.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $voyage = $voyageVehicule->getVoyage();

        // Récupérer la région depuis la session
        $session = $request->getSession();
        $regionId = $session->get('region');

        // Sauvegarder les anciennes valeurs pour le log
        $oldData = $this->getVoyageVehiculeDataForLog($voyageVehicule);

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $destinationId = $request->request->get('destination');
            $commentaire = $request->request->get('commentaire');

            // Validation
            $errors = [];
            if (empty($destinationId)) {
                $errors[] = 'La destination est obligatoire.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                $this->auditLogger->log(
                    'tentative_update', 
                    'VoyageVehicule', 
                    $voyageVehicule->getId(), 
                    ['errors' => $errors], 
                    'error'
                );
                return $this->redirectToRoute('app_voyage_vehicule_edit', ['id' => $voyageVehicule->getId()]);
            }

            try {
                $destination = $entityManager->getRepository(DestinationVoyage::class)->find($destinationId);
                if (!$destination) {
                    $this->auditLogger->log(
                        'update', 
                        'VoyageVehicule', 
                        $voyageVehicule->getId(), 
                        ['error' => 'Destination non trouvée'], 
                        'error'
                    );
                    $this->addFlash('error', 'Destination non trouvée.');
                    return $this->redirectToRoute('app_voyage_vehicule_edit', ['id' => $voyageVehicule->getId()]);
                }

                $voyageVehicule->setDestination($destination);
                $voyageVehicule->setCommentaire($commentaire);

                $entityManager->flush();

                // Log de modification réussie
                $newData = $this->getVoyageVehiculeDataForLog($voyageVehicule);
                $changes = $this->getChanges($oldData, $newData);
                
                $this->auditLogger->log(
                    'update', 
                    'VoyageVehicule', 
                    $voyageVehicule->getId(), 
                    $changes
                );

                $this->addFlash('success', 'Trajet modifié avec succès.');
                return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'update', 
                    'VoyageVehicule', 
                    $voyageVehicule->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du trajet: ' . $e->getMessage());
                return $this->redirectToRoute('app_voyage_vehicule_edit', ['id' => $voyageVehicule->getId()]);
            }
        }

        // Récupérer toutes les destinations pour le formulaire
        $destinations = $entityManager->getRepository(DestinationVoyage::class)->findAll();

        return $this->render('voyage_vehicule/edit.html.twig', [
            'voyage_vehicule' => $voyageVehicule,
            'voyage' => $voyage,
            'destinations' => $destinations,
        ]);
    }

    #[Route('/{id}', name: 'app_voyage_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, VoyageVehicule $voyageVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage_vehicule.delete')) {
            $this->auditLogger->log(
                'tentative_delete', 
                'VoyageVehicule', 
                $voyageVehicule->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer un trajet de voyage.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $voyage = $voyageVehicule->getVoyage();

        // Vérification du token CSRF
        if (!$this->isCsrfTokenValid('delete' . $voyageVehicule->getId(), $request->request->get('_token'))) {
            $this->auditLogger->log(
                'tentative_delete', 
                'VoyageVehicule', 
                $voyageVehicule->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
        }

        try {
            // Vérifier qu'il y a plus d'un trajet avant de supprimer
            if ($voyage->getVoyageVehicules()->count() <= 1) {
                $this->auditLogger->log(
                    'delete', 
                    'VoyageVehicule', 
                    $voyageVehicule->getId(), 
                    ['error' => 'Impossible de supprimer le seul trajet'], 
                    'error'
                );
                $this->addFlash('error', 'Impossible de supprimer le seul trajet du voyage.');
                return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
            }

            // Sauvegarder les données avant suppression pour le log
            $voyageVehiculeData = $this->getVoyageVehiculeDataForLog($voyageVehicule);
            
            $entityManager->remove($voyageVehicule);
            $entityManager->flush();

            // Log de suppression réussie
            $this->auditLogger->log(
                'delete', 
                'VoyageVehicule', 
                $voyageVehicule->getId(), 
                ['deleted_data' => $voyageVehiculeData]
            );

            $this->addFlash('success', 'Trajet supprimé avec succès.');
        } catch (\Exception $e) {
            $this->auditLogger->log(
                'delete', 
                'VoyageVehicule', 
                $voyageVehicule->getId(), 
                ['error' => $e->getMessage()], 
                'error'
            );
            $this->addFlash('error', 'Une erreur est survenue lors de la suppression du trajet: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
    }

    /**
     * Préparer les données du voyage vehicule pour le log
     */
    private function getVoyageVehiculeDataForLog(VoyageVehicule $voyageVehicule): array
    {
        return [
            'voyage_id' => $voyageVehicule->getVoyage()->getId(),
            'destination_id' => $voyageVehicule->getDestination() ? $voyageVehicule->getDestination()->getId() : null,
            'destination_libelle' => $voyageVehicule->getDestination() ? $voyageVehicule->getDestination()->getLibelle() : null,
            'commentaire' => $voyageVehicule->getCommentaire(),
            'distance' => $voyageVehicule->getDestination() ? $voyageVehicule->getDestination()->getDistance() : null
        ];
    }

    /**
     * Comparer les anciennes et nouvelles valeurs pour détecter les changements
     */
    private function getChanges(array $oldData, array $newData): array
    {
        $changes = [];
        
        foreach ($oldData as $key => $oldValue) {
            if (array_key_exists($key, $newData) && $oldValue != $newData[$key]) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newData[$key]
                ];
            }
        }
        
        return $changes;
    }
}