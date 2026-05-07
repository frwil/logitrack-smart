<?php
// src/Controller/VidangeVehiculeController.php

namespace App\Controller;

use App\Entity\VidangeVehicule;
use App\Entity\ReleveKmsVehicule;
use App\Repository\VidangeVehiculeRepository;
use App\Repository\AffectationVehiculeRepository;
use App\Repository\PrestataireInterventionRepository;
use App\Repository\VehiculeRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vidange')]
class VidangeVehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_vidange_vehicule_index', methods: ['GET'])]
    public function index(
        Request $request, 
        VidangeVehiculeRepository $vidangeVehiculeRepository, 
        AffectationVehiculeRepository $affectationRepo,
        VehiculeRepository $vehiculeRepository,
        PaginatorInterface $paginator
    ): Response {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vidange_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des vidanges.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer la région depuis la session
        $session = $request->getSession();
        $regionId = $session->get('region');
        
        // Récupérer le filtre véhicule depuis la requête
        $vehiculeId = $request->query->get('vehicule');
        
        // Récupérer toutes les vidanges ou filtrer par véhicule
        if ($vehiculeId) {
            $vidangesQuery = $vidangeVehiculeRepository->findByVehiculeQuery($vehiculeId);
        } else {
            // Utiliser la nouvelle méthode qui prend en compte la région
            $vidangesQuery = $vidangeVehiculeRepository->findByRegionOrAllQuery($regionId);
        }

        // Pagination
        $pagination = $paginator->paginate(
            $vidangesQuery,
            $request->query->getInt('page', 1),
            10 // Nombre d'éléments par page
        );

        // Calcul des insights avec filtre région
        $totalVidanges = $vidangeVehiculeRepository->countByRegion($regionId);
        $totalCout = $vidangeVehiculeRepository->getTotalCost($regionId);
        $prochaineVidange = $vidangeVehiculeRepository->findNextScheduledVidange($regionId);

        // Récupérer tous les véhicules pour le filtre (filtrés par région si nécessaire)
        $vehicules = $vehiculeRepository->findActiveVehicules($regionId);

        return $this->render('vidange_vehicule/index.html.twig', [
            'pagination' => $pagination,
            'vehicules' => $vehicules,
            'selectedVehicule' => $vehiculeId,
            'totalVidanges' => $totalVidanges,
            'totalCout' => $totalCout,
            'prochaineVidange' => $prochaineVidange,
            'canCreate' => $this->isGranted('vidange_vehicule.create'),
            'canExport' => $this->isGranted('vidange_vehicule.export'),
            'canPrint' => $this->isGranted('vidange_vehicule.print'),
        ]);
    }

    #[Route('/new', name: 'app_vidange_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AffectationVehiculeRepository $affectationRepo, PrestataireInterventionRepository $prestataireRepo): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vidange_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer une vidange.');
            return $this->redirectToRoute('app_vidange_vehicule_index');
        }

        $vidange = new VidangeVehicule();

        // Récupérer la région depuis la session
        $session = $request->getSession();
        $regionId = $session->get('region');

        if ($request->isMethod('POST')) {
            // Récupération et validation des données
            $data = $request->request->all();

            // Validation des données
            $errors = $this->validateVidangeData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_vidange_vehicule_new');
            }

            try {
                // Récupération de l'affectation
                $affectation = $affectationRepo->find($data['affectation']);
                if (!$affectation) {
                    $this->addFlash('error', 'Affectation non trouvée.');
                    return $this->redirectToRoute('app_vidange_vehicule_new');
                }
                
                $vidange->setAffectation($affectation);

                // Assignation des données
                $vidange->setDateVidange(new \DateTime($data['dateVidange']));
                $vidange->setKilometrageVidange((int)$data['kilometrageVidange']);
                $vidange->setTypeHuile($data['typeHuile'] ?? null);
                $vidange->setReferenceFiltre($data['referenceFiltre'] ?? null);
                $vidange->setQuantiteHuile(!empty($data['quantiteHuile']) ? (float)$data['quantiteHuile'] : null);
                $vidange->setCout(!empty($data['cout']) ? (float)$data['cout'] : null);
                $vidange->setObservations($data['observations'] ?? null);
                $vidange->setEffectuee(isset($data['effectuee']));

                // Gestion du prestataire
                if (!empty($data['prestataire'])) {
                    $prestataire = $prestataireRepo->find($data['prestataire']);
                    if ($prestataire) {
                        $vidange->setPrestataire($prestataire);
                    }
                }

                // Gestion de la date de validation et validateur
                if (isset($data['effectuee']) && $data['effectuee'] && empty($vidange->getDateValidation())) {
                    $vidange->setDateValidation(new \DateTime());
                    $vidange->setValidePar($this->getUser());
                }

                // Prochaine vidange prévue
                if (!empty($data['prochaineVidangePrevue'])) {
                    $vidange->setProchaineVidangePrevue(new \DateTime($data['prochaineVidangePrevue']));
                }
                if (!empty($data['kilometrageProchaineVidange'])) {
                    $vidange->setKilometrageProchaineVidange((int)$data['kilometrageProchaineVidange']);
                }

                $entityManager->persist($vidange);

                // Création du relevé kilométrique
                $releveKm = new ReleveKmsVehicule();
                $releveKm->setDateReleve($vidange->getDateVidange());
                $releveKm->setKilometrage($vidange->getKilometrageVidange());
                $releveKm->setAffectation($vidange->getAffectation());

                $entityManager->persist($releveKm);
                $entityManager->flush();

                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    VidangeVehicule::class,
                    $vidange->getId(),
                    ['new' => [
                        'affectation_id' => $affectation->getId(),
                        'date_vidange' => $data['dateVidange'],
                        'kilometrage_vidange' => (int)$data['kilometrageVidange'],
                        'type_huile' => $data['typeHuile'] ?? null,
                        'reference_filtre' => $data['referenceFiltre'] ?? null,
                        'quantite_huile' => !empty($data['quantiteHuile']) ? (float)$data['quantiteHuile'] : null,
                        'cout' => !empty($data['cout']) ? (float)$data['cout'] : null,
                        'observations' => $data['observations'] ?? null,
                        'effectuee' => isset($data['effectuee']),
                        'prestataire_id' => !empty($data['prestataire']) ? $data['prestataire'] : null,
                        'prochaine_vidange_prevue' => !empty($data['prochaineVidangePrevue']) ? $data['prochaineVidangePrevue'] : null,
                        'kilometrage_prochaine_vidange' => !empty($data['kilometrageProchaineVidange']) ? (int)$data['kilometrageProchaineVidange'] : null
                    ]],
                    'success'
                );

                $this->addFlash('success', 'Vidange ajoutée avec succès.');
                return $this->redirectToRoute('app_vidange_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'échec de création
                $this->auditLogger->log(
                    'create',
                    VidangeVehicule::class,
                    0,
                    ['attempted_data' => $data, 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la vidange: ' . $e->getMessage());
            }
        }

        // Récupération des affectations non fermées (filtrées par région si nécessaire) et des prestataires pour le formulaire
        $affectations = $affectationRepo->findByRegionOrAll(['is_ferme' => false], $regionId);
        $prestataires = $prestataireRepo->findAll();

        return $this->render('vidange_vehicule/new.html.twig', [
            'affectations' => $affectations,
            'prestataires' => $prestataires,
        ]);
    }

    #[Route('/{id}', name: 'app_vidange_vehicule_show', methods: ['GET'])]
    public function show(VidangeVehicule $vidangeVehicule): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vidange_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de cette vidange.');
            return $this->redirectToRoute('app_vidange_vehicule_index');
        }

        return $this->render('vidange_vehicule/show.html.twig', [
            'vidange' => $vidangeVehicule,
            'canEdit' => $this->isGranted('vidange_vehicule.update'),
            'canDelete' => $this->isGranted('vidange_vehicule.delete'),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vidange_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, VidangeVehicule $vidange, EntityManagerInterface $entityManager, AffectationVehiculeRepository $affectationRepo, PrestataireInterventionRepository $prestataireRepo): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vidange_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier cette vidange.');
            return $this->redirectToRoute('app_vidange_vehicule_index');
        }

        // Sauvegarde des anciennes valeurs pour le log
        $oldData = [
            'affectation_id' => $vidange->getAffectation()->getId(),
            'date_vidange' => $vidange->getDateVidange()->format('Y-m-d'),
            'kilometrage_vidange' => $vidange->getKilometrageVidange(),
            'type_huile' => $vidange->getTypeHuile(),
            'reference_filtre' => $vidange->getReferenceFiltre(),
            'quantite_huile' => $vidange->getQuantiteHuile(),
            'cout' => $vidange->getCout(),
            'observations' => $vidange->getObservations(),
            'effectuee' => $vidange->isEffectuee(),
            'prestataire_id' => $vidange->getPrestataire() ? $vidange->getPrestataire()->getId() : null,
            'prochaine_vidange_prevue' => $vidange->getProchaineVidangePrevue() ? $vidange->getProchaineVidangePrevue()->format('Y-m-d') : null,
            'kilometrage_prochaine_vidange' => $vidange->getKilometrageProchaineVidange(),
            'date_validation' => $vidange->getDateValidation() ? $vidange->getDateValidation()->format('Y-m-d H:i:s') : null,
            'valide_par' => $vidange->getValidePar() ? $vidange->getValidePar()->getId() : null
        ];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            // Validation des données
            $errors = $this->validateVidangeData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_vidange_vehicule_edit', ['id' => $vidange->getId()]);
            }

            try {
                $affectation = $affectationRepo->find($data['affectation']);
                if (!$affectation) {
                    $this->addFlash('error', 'Affectation non trouvée.');
                    return $this->redirectToRoute('app_vidange_vehicule_edit', ['id' => $vidange->getId()]);
                }
                
                $vidange->setAffectation($affectation);
                $vidange->setDateVidange(new \DateTime($data['dateVidange']));
                $vidange->setKilometrageVidange((int)$data['kilometrageVidange']);
                $vidange->setTypeHuile($data['typeHuile'] ?? null);
                $vidange->setReferenceFiltre($data['referenceFiltre'] ?? null);
                $vidange->setQuantiteHuile(!empty($data['quantiteHuile']) ? (float)$data['quantiteHuile'] : null);
                $vidange->setCout(!empty($data['cout']) ? (float)$data['cout'] : null);
                $vidange->setObservations($data['observations'] ?? null);
                $vidange->setEffectuee(isset($data['effectuee']));

                if (!empty($data['prestataire'])) {
                    $prestataire = $prestataireRepo->find($data['prestataire']);
                    if ($prestataire) {
                        $vidange->setPrestataire($prestataire);
                    }
                } else {
                    $vidange->setPrestataire(null);
                }

                // Gestion de la date de validation et validateur
                if (isset($data['effectuee']) && $data['effectuee'] && !$vidange->getDateValidation()) {
                    $vidange->setDateValidation(new \DateTime());
                    $vidange->setValidePar($this->getUser());
                } elseif (isset($data['effectuee']) && !$data['effectuee']) {
                    $vidange->setDateValidation(null);
                    $vidange->setValidePar(null);
                }

                if (!empty($data['prochaineVidangePrevue'])) {
                    $vidange->setProchaineVidangePrevue(new \DateTime($data['prochaineVidangePrevue']));
                } else {
                    $vidange->setProchaineVidangePrevue(null);
                }
                if (!empty($data['kilometrageProchaineVidange'])) {
                    $vidange->setKilometrageProchaineVidange((int)$data['kilometrageProchaineVidange']);
                } else {
                    $vidange->setKilometrageProchaineVidange(null);
                }

                // Mise à jour du relevé kilométrique existant ou création d'un nouveau
                $releveKm = $entityManager->getRepository(ReleveKmsVehicule::class)->findOneBy([
                    'affectation' => $vidange->getAffectation(),
                    'dateReleve' => $vidange->getDateVidange()
                ]);

                if (!$releveKm) {
                    $releveKm = new ReleveKmsVehicule();
                    $releveKm->setAffectation($vidange->getAffectation());
                    $releveKm->setDateReleve($vidange->getDateVidange());
                }

                $releveKm->setKilometrage($vidange->getKilometrageVidange());
                $entityManager->persist($releveKm);

                $entityManager->flush();

                // Log de l'action de modification avec anciennes et nouvelles valeurs
                $newData = [
                    'affectation_id' => $affectation->getId(),
                    'date_vidange' => $data['dateVidange'],
                    'kilometrage_vidange' => (int)$data['kilometrageVidange'],
                    'type_huile' => $data['typeHuile'] ?? null,
                    'reference_filtre' => $data['referenceFiltre'] ?? null,
                    'quantite_huile' => !empty($data['quantiteHuile']) ? (float)$data['quantiteHuile'] : null,
                    'cout' => !empty($data['cout']) ? (float)$data['cout'] : null,
                    'observations' => $data['observations'] ?? null,
                    'effectuee' => isset($data['effectuee']),
                    'prestataire_id' => !empty($data['prestataire']) ? $data['prestataire'] : null,
                    'prochaine_vidange_prevue' => !empty($data['prochaineVidangePrevue']) ? $data['prochaineVidangePrevue'] : null,
                    'kilometrage_prochaine_vidange' => !empty($data['kilometrageProchaineVidange']) ? (int)$data['kilometrageProchaineVidange'] : null,
                    'date_validation' => $vidange->getDateValidation() ? $vidange->getDateValidation()->format('Y-m-d H:i:s') : null,
                    'valide_par' => $vidange->getValidePar() ? $vidange->getValidePar()->getId() : null
                ];

                $changes = [];
                foreach ($oldData as $key => $oldValue) {
                    if ($oldValue != $newData[$key]) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newData[$key]
                        ];
                    }
                }

                if (!empty($changes)) {
                    $this->auditLogger->log(
                        'update',
                        VidangeVehicule::class,
                        $vidange->getId(),
                        $changes,
                        'success'
                    );
                }

                $this->addFlash('success', 'Vidange modifiée avec succès.');
                return $this->redirectToRoute('app_vidange_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'échec de modification
                $this->auditLogger->log(
                    'update',
                    VidangeVehicule::class,
                    $vidange->getId(),
                    ['attempted_data' => $data, 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la vidange: ' . $e->getMessage());
            }
        }

        $affectations = $affectationRepo->findBy(['is_ferme' => false]);
        $prestataires = $prestataireRepo->findAll();

        return $this->render('vidange_vehicule/edit.html.twig', [
            'vidange' => $vidange,
            'affectations' => $affectations,
            'prestataires' => $prestataires,
        ]);
    }

    #[Route('/{id}', name: 'app_vidange_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, VidangeVehicule $vidangeVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vidange_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer cette vidange.');
            return $this->redirectToRoute('app_vidange_vehicule_index');
        }

        if ($this->isCsrfTokenValid('delete' . $vidangeVehicule->getId(), $request->request->get('_token'))) {
            try {
                // Log de l'action de suppression avec les données de l'entité
                $this->auditLogger->log(
                    'delete',
                    VidangeVehicule::class,
                    $vidangeVehicule->getId(),
                    ['old' => [
                        'affectation_id' => $vidangeVehicule->getAffectation()->getId(),
                        'date_vidange' => $vidangeVehicule->getDateVidange()->format('Y-m-d'),
                        'kilometrage_vidange' => $vidangeVehicule->getKilometrageVidange(),
                        'type_huile' => $vidangeVehicule->getTypeHuile(),
                        'reference_filtre' => $vidangeVehicule->getReferenceFiltre(),
                        'quantite_huile' => $vidangeVehicule->getQuantiteHuile(),
                        'cout' => $vidangeVehicule->getCout(),
                        'observations' => $vidangeVehicule->getObservations(),
                        'effectuee' => $vidangeVehicule->isEffectuee(),
                        'prestataire_id' => $vidangeVehicule->getPrestataire() ? $vidangeVehicule->getPrestataire()->getId() : null,
                        'prochaine_vidange_prevue' => $vidangeVehicule->getProchaineVidangePrevue() ? $vidangeVehicule->getProchaineVidangePrevue()->format('Y-m-d') : null,
                        'kilometrage_prochaine_vidange' => $vidangeVehicule->getKilometrageProchaineVidange(),
                        'date_validation' => $vidangeVehicule->getDateValidation() ? $vidangeVehicule->getDateValidation()->format('Y-m-d H:i:s') : null,
                        'valide_par' => $vidangeVehicule->getValidePar() ? $vidangeVehicule->getValidePar()->getId() : null
                    ]],
                    'success'
                );

                $entityManager->remove($vidangeVehicule);
                $entityManager->flush();
                $this->addFlash('success', 'Vidange supprimée avec succès.');
            } catch (\Exception $e) {
                // Log de l'échec de suppression
                $this->auditLogger->log(
                    'delete',
                    VidangeVehicule::class,
                    $vidangeVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la vidange: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_vidange_vehicule_index');
    }

    #[Route('/historique/{vehiculeId}', name: 'app_vidange_vehicule_historique', methods: ['GET'])]
    public function historique(Request $request, int $vehiculeId, VidangeVehiculeRepository $vidangeVehiculeRepository, VehiculeRepository $vehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vidange_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à l\'historique des vidanges.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $vehicule = $vehiculeRepository->find($vehiculeId);

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé');
            return $this->redirectToRoute('app_vidange_vehicule_index');
        }

        // Récupérer toutes les vidanges pour ce véhicule
        $vidanges = $vidangeVehiculeRepository->findByVehicule($vehiculeId);

        // Préparer les données pour le graphique
        $dates = [];
        $kilometrages = [];

        foreach ($vidanges as $vidange) {
            $dates[] = $vidange->getDateVidange()->format('d/m/Y');
            $kilometrages[] = $vidange->getKilometrageVidange();
        }

        return $this->render('vidange_vehicule/historique.html.twig', [
            'vehicule' => $vehicule,
            'vidanges' => $vidanges,
            'dates' => $dates,
            'kilometrages' => $kilometrages,
            'canPrint' => $this->isGranted('vidange_vehicule.print'),
            'canExport' => $this->isGranted('vidange_vehicule.export'),
        ]);
    }

    /**
     * Valider les données du formulaire de vidange
     */
    private function validateVidangeData(array $data): array
    {
        $errors = [];

        if (empty($data['affectation'])) {
            $errors[] = 'L\'affectation est obligatoire.';
        }

        if (empty($data['dateVidange'])) {
            $errors[] = 'La date de vidange est obligatoire.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $data['dateVidange'])) {
            $errors[] = 'Le format de la date de vidange est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        if (empty($data['kilometrageVidange']) || !is_numeric($data['kilometrageVidange'])) {
            $errors[] = 'Le kilométrage est obligatoire et doit être un nombre.';
        }

        if (!empty($data['quantiteHuile']) && !is_numeric($data['quantiteHuile'])) {
            $errors[] = 'La quantité d\'huile doit être un nombre.';
        }

        if (!empty($data['cout']) && !is_numeric($data['cout'])) {
            $errors[] = 'Le coût doit être un nombre.';
        }

        if (!empty($data['prochaineVidangePrevue']) && !\DateTime::createFromFormat('Y-m-d', $data['prochaineVidangePrevue'])) {
            $errors[] = 'Le format de la date de prochaine vidange prévue est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        if (!empty($data['kilometrageProchaineVidange']) && !is_numeric($data['kilometrageProchaineVidange'])) {
            $errors[] = 'Le kilométrage de la prochaine vidange doit être un nombre.';
        }

        return $errors;
    }
}