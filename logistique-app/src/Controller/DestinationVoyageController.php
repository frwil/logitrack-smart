<?php

namespace App\Controller;

use App\Entity\DestinationVoyage;
use App\Form\DestinationVoyageType;
use App\Repository\DestinationVoyageRepository;
use App\Repository\RegionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\AuditLogger;

#[Route('/destination/voyage')]
class DestinationVoyageController extends AbstractController
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    private function getDestinationVoyageData(DestinationVoyage $destinationVoyage): array
    {
        return [
            'lieuDepart' => $destinationVoyage->getLieuDepart(),
            'lieuArrivee' => $destinationVoyage->getLieuArrivee(),
            'distance' => $destinationVoyage->getDistance(),
            'region' => $destinationVoyage->getRegion() ? $destinationVoyage->getRegion()->getId() : null,
            'libelle' => $destinationVoyage->getLibelle()
        ];
    }

    #[Route('/', name: 'app_destination_voyage_index', methods: ['GET'])]
    public function index(DestinationVoyageRepository $destinationVoyageRepository, Request $request): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('destination_voyage.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des destinations de voyage.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $selectedRegionId = $request->getSession()->get('selected_region');

        if ($selectedRegionId) {
            $destination_voyages = $destinationVoyageRepository->findBy(['region' => $selectedRegionId]);
        } else {
            $destination_voyages = $destinationVoyageRepository->findAll();
        }

        return $this->render('destination_voyage/index.html.twig', [
            'destination_voyages' => $destination_voyages,
        ]);
    }

    #[Route('/new', name: 'app_destination_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, RegionRepository $regionRepository, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('destination_voyage.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer une destination de voyage.');
            return $this->redirectToRoute('app_destination_voyage_index');
        }

        $destinationVoyage = new DestinationVoyage();

        if ($request->isMethod('POST')) {
            // Récupération des données du formulaire
            $lieuDepart = trim($request->request->get('lieu_depart', ''));
            $lieuArrivee = trim($request->request->get('lieu_arrivee', ''));
            $distance = trim($request->request->get('distance', ''));
            $regionId = $request->request->get('region');

            // Validation des données
            $errors = [];
            if (empty($lieuDepart)) {
                $errors[] = 'Le lieu de départ est obligatoire.';
            }
            if (empty($lieuArrivee)) {
                $errors[] = 'Le lieu d\'arrivée est obligatoire.';
            }
            if (empty($distance)) {
                $errors[] = 'La distance est obligatoire.';
            }
            if (empty($regionId)) {
                $errors[] = 'La région est obligatoire.';
            }
            
            if (mb_strlen($lieuDepart) > 150) {
                $errors[] = 'Le lieu de départ ne doit pas dépasser 150 caractères.';
            }
            if (mb_strlen($lieuArrivee) > 150) {
                $errors[] = 'Le lieu d\'arrivée ne doit pas dépasser 150 caractères.';
            }
            if (!is_numeric($distance) || $distance <= 0) {
                $errors[] = 'La distance doit être un nombre positif.';
            }

            if (!empty($errors)) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => implode(' ', $errors)
                    ], 400);
                }

                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_destination_voyage_new');
            }

            // Récupération de la région
            $region = $regionRepository->find($regionId);
            if (!$region) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Région non trouvée.'
                    ], 400);
                }

                $this->addFlash('error', 'Région non trouvée.');
                return $this->redirectToRoute('app_destination_voyage_new');
            }

            // Génération du libellé automatiquement
            $libelle = $lieuDepart . ' - ' . $lieuArrivee;

            // Vérifier si une destination avec le même libellé existe déjà
            $existingDestination = $entityManager->getRepository(DestinationVoyage::class)
                ->findOneBy(['libelle' => $libelle, 'region' => $region]);

            if ($existingDestination) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Une destination avec ce libellé existe déjà dans cette région.'
                    ], 400);
                }

                $this->addFlash('error', 'Une destination avec ce libellé existe déjà dans cette région.');
                return $this->redirectToRoute('app_destination_voyage_new');
            }

            // Hydratation de l'objet
            $destinationVoyage->setLieuDepart($lieuDepart);
            $destinationVoyage->setLieuArrivee($lieuArrivee);
            $destinationVoyage->setDistance((int)$distance);
            $destinationVoyage->setRegion($region);

            // Validation de l'entité
            $validationErrors = $validator->validate($destinationVoyage);
            if (count($validationErrors) > 0) {
                $errorMessages = [];
                foreach ($validationErrors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => implode(' ', $errorMessages)
                    ], 400);
                }
                
                foreach ($errorMessages as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_destination_voyage_new');
            }

            try {
                // Persistence
                $entityManager->persist($destinationVoyage);
                $entityManager->flush();

                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    DestinationVoyage::class,
                    $destinationVoyage->getId(),
                    ['new' => $this->getDestinationVoyageData($destinationVoyage)]
                );

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'id' => $destinationVoyage->getId(),
                        'libelle' => $destinationVoyage->getLibelle()
                    ]);
                }

                $this->addFlash('success', 'Destination de voyage créée avec succès.');
                return $this->redirectToRoute('app_destination_voyage_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'create',
                    DestinationVoyage::class,
                    0,
                    ['error' => $e->getMessage()],
                    'error'
                );

                $errorMessage = 'Une erreur est survenue lors de la création de la destination de voyage.';
                
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => $errorMessage
                    ], 500);
                }

                $this->addFlash('error', $errorMessage);
                return $this->redirectToRoute('app_destination_voyage_new');
            }
        }

        $regions = $regionRepository->findAll();

        // Si c'est une requête AJAX (pour le modal), on retourne un JSON
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Méthode non autorisée'
            ], 405);
        }

        return $this->render('destination_voyage/new.html.twig', [
            'regions' => $regions,
        ]);
    }

    #[Route('/{id}', name: 'app_destination_voyage_show', methods: ['GET'])]
    public function show(DestinationVoyage $destinationVoyage): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('destination_voyage.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de cette destination de voyage.');
            return $this->redirectToRoute('app_destination_voyage_index');
        }

        return $this->render('destination_voyage/show.html.twig', [
            'destination_voyage' => $destinationVoyage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_destination_voyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, DestinationVoyage $destinationVoyage, EntityManagerInterface $entityManager, RegionRepository $regionRepository, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('destination_voyage.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier cette destination de voyage.');
            return $this->redirectToRoute('app_destination_voyage_index');
        }

        // Sauvegarder les anciennes données pour le log
        $oldData = $this->getDestinationVoyageData($destinationVoyage);

        if ($request->isMethod('POST')) {
            // Récupération des données du formulaire
            $lieuDepart = trim($request->request->get('lieu_depart', ''));
            $lieuArrivee = trim($request->request->get('lieu_arrivee', ''));
            $distance = trim($request->request->get('distance', ''));
            $regionId = $request->request->get('region');

            // Validation des données
            $errors = [];
            if (empty($lieuDepart)) {
                $errors[] = 'Le lieu de départ est obligatoire.';
            }
            if (empty($lieuArrivee)) {
                $errors[] = 'Le lieu d\'arrivée est obligatoire.';
            }
            if (empty($distance)) {
                $errors[] = 'La distance est obligatoire.';
            }
            if (empty($regionId)) {
                $errors[] = 'La région est obligatoire.';
            }
            
            if (mb_strlen($lieuDepart) > 150) {
                $errors[] = 'Le lieu de départ ne doit pas dépasser 150 caractères.';
            }
            if (mb_strlen($lieuArrivee) > 150) {
                $errors[] = 'Le lieu d\'arrivée ne doit pas dépasser 150 caractères.';
            }
            if (!is_numeric($distance) || $distance <= 0) {
                $errors[] = 'La distance doit être un nombre positif.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_destination_voyage_edit', ['id' => $destinationVoyage->getId()]);
            }

            // Récupération de la région
            $region = $regionRepository->find($regionId);
            if (!$region) {
                $this->addFlash('error', 'Région non trouvée.');
                return $this->redirectToRoute('app_destination_voyage_edit', ['id' => $destinationVoyage->getId()]);
            }

            // Génération du libellé automatiquement
            $libelle = $lieuDepart . ' - ' . $lieuArrivee;

            // Vérifier si une destination avec le même libellé existe déjà (exclure l'actuelle)
            $existingDestination = $entityManager->getRepository(DestinationVoyage::class)
                ->findOneBy(['libelle' => $libelle, 'region' => $region]);

            if ($existingDestination && $existingDestination->getId() !== $destinationVoyage->getId()) {
                $this->addFlash('error', 'Une destination avec ce libellé existe déjà dans cette région.');
                return $this->redirectToRoute('app_destination_voyage_edit', ['id' => $destinationVoyage->getId()]);
            }

            // Mise à jour de l'objet
            $destinationVoyage->setLieuDepart($lieuDepart);
            $destinationVoyage->setLieuArrivee($lieuArrivee);
            $destinationVoyage->setDistance((int)$distance);
            $destinationVoyage->setRegion($region);

            // Validation de l'entité
            $validationErrors = $validator->validate($destinationVoyage);
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->redirectToRoute('app_destination_voyage_edit', ['id' => $destinationVoyage->getId()]);
            }

            try {
                // Persistence
                $entityManager->flush();

                // Log de l'action de modification
                $this->auditLogger->log(
                    'update',
                    DestinationVoyage::class,
                    $destinationVoyage->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getDestinationVoyageData($destinationVoyage)
                    ]
                );

                $this->addFlash('success', 'Destination de voyage modifiée avec succès.');
                return $this->redirectToRoute('app_destination_voyage_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    DestinationVoyage::class,
                    $destinationVoyage->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );

                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la destination de voyage.');
                return $this->redirectToRoute('app_destination_voyage_edit', ['id' => $destinationVoyage->getId()]);
            }
        }

        $regions = $regionRepository->findAll();

        return $this->render('destination_voyage/edit.html.twig', [
            'destination_voyage' => $destinationVoyage,
            'regions' => $regions,
        ]);
    }

    #[Route('/{id}', name: 'app_destination_voyage_delete', methods: ['POST'])]
    public function delete(Request $request, DestinationVoyage $destinationVoyage, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('destination_voyage.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer cette destination de voyage.');
            return $this->redirectToRoute('app_destination_voyage_index');
        }

        if ($this->isCsrfTokenValid('delete' . $destinationVoyage->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des voyageVehicules associés
            if ($destinationVoyage->getVoyageVehicules()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette destination car elle est utilisée dans des voyages.');
                return $this->redirectToRoute('app_destination_voyage_index');
            }

            try {
                // Sauvegarder les données avant suppression pour le log
                $oldData = $this->getDestinationVoyageData($destinationVoyage);
                
                $entityManager->remove($destinationVoyage);
                $entityManager->flush();

                // Log de l'action de suppression
                $this->auditLogger->log(
                    'delete',
                    DestinationVoyage::class,
                    $destinationVoyage->getId(),
                    ['old' => $oldData]
                );

                $this->addFlash('success', 'Destination de voyage supprimée avec succès.');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'delete',
                    DestinationVoyage::class,
                    $destinationVoyage->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );

                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la destination de voyage.');
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_destination_voyage_index');
    }
}