<?php
// src/Controller/VoyageController.php

namespace App\Controller;

use App\Entity\Voyage;
use App\Entity\VoyageVehicule;
use App\Entity\Vehicule;
use App\Entity\AffectationVehicule;
use App\Entity\DestinationVoyage;
use App\Entity\TypeChargementVoyage;
use App\Repository\VoyageRepository;
use App\Repository\DestinationVoyageRepository;
use App\Repository\AffectationVehiculeRepository;
use App\Repository\TypeChargementVoyageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RegionRepository;
use App\Service\AuditLogger;

#[Route('/voyages')]
class VoyageController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_voyage_index')]
    public function index(Request $request, VoyageRepository $voyageRepository, AffectationVehiculeRepository $affectationRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage.view')) {
            $this->auditLogger->log(
                'tentative_access', 
                'Voyage', 
                0, 
                ['page' => 'index', 'reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des voyages.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer la région depuis la session
        $session = $request->getSession();
        $regionId = $session->get('region');
        
        // Récupération des paramètres de filtrage
        $affectationId = $request->query->get('affectation');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $page = $request->query->getInt('page', 1);

        // Définir les dates par défaut (mois en cours)
        if (!$dateDebut) {
            $dateDebut = new \DateTime('first day of this month');
        } else {
            $dateDebut = new \DateTime($dateDebut);
        }

        if (!$dateFin) {
            $dateFin = new \DateTime('last day of this month');
        } else {
            $dateFin = new \DateTime($dateFin);
        }

        // Récupérer les voyages filtrés (avec filtre région)
        if ($affectationId) {
            $voyages = $voyageRepository->findByAffectationAndPeriod($affectationId, $dateDebut, $dateFin, $regionId);
        } else {
            $voyages = $voyageRepository->findByPeriod($dateDebut, $dateFin, $regionId);
        }

        // Calcul des statistiques globales
        $stats = [
            'total_voyages' => count($voyages),
            'total_km' => 0,
            'total_carburant' => 0,
            'chargement_par_type' => [],
            'destinations_populaires' => [],
        ];

        foreach ($voyages as $voyage) {
            // Total carburant
            $stats['total_carburant'] += $voyage->getQuantiteCarburant();

            // Total km (pour chaque véhicule dans le voyage)
            foreach ($voyage->getVoyageVehicules() as $vehiculeVoyage) {
                $stats['total_km'] += $vehiculeVoyage->getDestination()->getDistance();

                // Destinations populaires
                $destName = $vehiculeVoyage->getDestination()->getLibelle();
                if (!isset($stats['destinations_populaires'][$destName])) {
                    $stats['destinations_populaires'][$destName] = 0;
                }
                $stats['destinations_populaires'][$destName]++;
            }

            // Chargement par type
            if ($voyage->getTypeChargement()) {
                $typeName = $voyage->getTypeChargement()->getLibelle();
                if (!isset($stats['chargement_par_type'][$typeName])) {
                    $stats['chargement_par_type'][$typeName] = 0;
                }
                $stats['chargement_par_type'][$typeName] += $voyage->getQuantiteChargement();
            }
        }

        // Trier les destinations par popularité
        arsort($stats['destinations_populaires']);
        $stats['top_destinations'] = array_slice($stats['destinations_populaires'], 0, 3, true);

        // Grouper les voyages par date
        $voyagesParDate = [];
        foreach ($voyages as $voyage) {
            $date = $voyage->getDateVoyage()->format('Y-m-d');
            $voyagesParDate[$date][] = $voyage;
        }

        // Pagination (par exemple, 7 jours par page)
        $joursParPage = 7;
        $totalJours = count($voyagesParDate);
        $totalPages = ceil($totalJours / $joursParPage);
        $voyagesParDate = array_slice($voyagesParDate, ($page - 1) * $joursParPage, $joursParPage, true);

        // Récupérer les affectations (filtrées par région si nécessaire)
        $affectations = $affectationRepository->findByRegionOrAll(['is_ferme' => false], $regionId);

        return $this->render('voyage/index.html.twig', [
            'voyagesParDate' => $voyagesParDate,
            'affectations' => $affectations,
            'affectationId' => $affectationId,
            'dateDebut' => $dateDebut->format('Y-m-d'),
            'dateFin' => $dateFin->format('Y-m-d'),
            'page' => $page,
            'totalPages' => $totalPages,
            'stats' => $stats,
            'canCreate' => $this->isGranted('voyage.create'),
            'canExport' => $this->isGranted('voyage.export'),
            'canPrint' => $this->isGranted('voyage.print'),
        ]);
    }

    #[Route('/new', name: 'app_voyage_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, AffectationVehiculeRepository $affectationRepository, TypeChargementVoyageRepository $typeChargementRepository, VoyageRepository $voyageRepository, RegionRepository $regionRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'Voyage', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un voyage.');
            return $this->redirectToRoute('app_voyage_index');
        }

        // Récupérer la région depuis la session
        $session = $request->getSession();
        $regionId = $session->get('region');
        
        $voyage = new Voyage();

        if ($request->isMethod('POST')) {
            // Validation des données
            $data = $request->request->all();
            $errors = $this->validateVoyageData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                $this->auditLogger->log(
                    'tentative_create', 
                    'Voyage', 
                    0, 
                    ['errors' => $errors], 
                    'error'
                );
                return $this->redirectToRoute('app_voyage_new');
            }

            try {
                // Générer un titre unique pour le voyage
                $titreUnique = $this->generateUniqueTitre($voyageRepository);
                
                // Traitement des données du formulaire avec les nouvelles propriétés
                $voyage->setDateVoyage(new \DateTime($request->request->get('date_voyage')));
                $voyage->setTitre($titreUnique); // Utiliser le titre généré
                $voyage->setQuantiteCarburant((int)$request->request->get('qte_carburant'));
                $voyage->setConvoyeur($request->request->get('convoyeur'));
                $voyage->setQuantiteChargement((int)$request->request->get('qte_chargement'));
                $voyage->setCommentaire($request->request->get('commentaire_voyage'));

                // Nouveaux champs
                $voyage->setKmDepart((int)$request->request->get('km_depart', 0));
                $voyage->setKmArrivee((int)$request->request->get('km_arrivee', 0));

                $datePrevue = $request->request->get('date_prevue_voyage');
                if ($datePrevue) {
                    $voyage->setDatePrevueVoyage(new \DateTime($datePrevue));
                }

                $heureDepart = $request->request->get('heure_depart');
                if ($heureDepart) {
                    $voyage->setHeureDepart(new \DateTime($heureDepart));
                }

                $heureArrivee = $request->request->get('heure_arrivee');
                if ($heureArrivee) {
                    $voyage->setHeureArrivee(new \DateTime($heureArrivee));
                }

                // Récupération des entités liées
                $affectation = $entityManager->getRepository(AffectationVehicule::class)->find($request->request->get('affectation'));
                $typeChargement = $entityManager->getRepository(TypeChargementVoyage::class)->find($request->request->get('type_chargement'));

                if (!$affectation) {
                    $this->auditLogger->log(
                        'create', 
                        'Voyage', 
                        0, 
                        ['error' => 'Affectation non trouvée'], 
                        'error'
                    );
                    $this->addFlash('error', 'Affectation non trouvée.');
                    return $this->redirectToRoute('app_voyage_new');
                }
                $voyage->setAffectation($affectation);

                if ($typeChargement) {
                    $voyage->setTypeChargement($typeChargement);
                }

                $entityManager->persist($voyage);

                $trajets = $request->request->all()['trajets'] ?? [];
                if (empty($trajets)) {
                    $this->auditLogger->log(
                        'create', 
                        'Voyage', 
                        0, 
                        ['error' => 'Aucun trajet défini'], 
                        'error'
                    );
                    $this->addFlash('error', 'Au moins un trajet est requis.');
                    return $this->redirectToRoute('app_voyage_new');
                }

                foreach ($trajets as $trajetData) {
                    $voyageVehicule = new VoyageVehicule();
                    $voyageVehicule->setVoyage($voyage);

                    // Récupération de la destination uniquement
                    $destination = $entityManager->getRepository(DestinationVoyage::class)->find($trajetData['destination']);

                    if (!$destination) {
                        $this->auditLogger->log(
                            'create', 
                            'Voyage', 
                            0, 
                            ['error' => 'Destination non trouvée'], 
                            'error'
                        );
                        $this->addFlash('error', 'Destination non trouvée.');
                        return $this->redirectToRoute('app_voyage_new');
                    }
                    
                    $voyageVehicule->setDestination($destination);
                    $voyageVehicule->setCommentaire($trajetData['commentaire'] ?? '');

                    $entityManager->persist($voyageVehicule);
                }

                $entityManager->flush();

                // Log de création réussie
                $this->auditLogger->log(
                    'create', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['new_data' => $this->getVoyageDataForLog($voyage)]
                );

                $this->addFlash('success', 'Voyage créé avec succès.');
                return $this->redirectToRoute('app_voyage_index');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'create', 
                    'Voyage', 
                    0, 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la création du voyage: ' . $e->getMessage());
            }
        }

        // Récupération des affectations (uniquement celles non fermées, filtrées par région) et types de chargement pour le formulaire
        $affectations = $affectationRepository->findByRegionOrAll(['is_ferme' => false], $regionId);
        $typesChargement = $typeChargementRepository->findAll();

        // Récupération des véhicules (filtrés par région) et destinations pour les trajets
        $vehicules = $entityManager->getRepository(Vehicule::class)->findByRegionOrAll($regionId);
        $destinations = $entityManager->getRepository(DestinationVoyage::class)->findAll();
        $regions = $regionRepository->findAll();

        return $this->render('voyage/new.html.twig', [
            'affectations' => $affectations,
            'types_chargement' => $typesChargement,
            'vehicules' => $vehicules,
            'destinations' => $destinations,
            'regions'=> $regions
        ]);
    }

    // Méthode pour générer un titre unique
    private function generateUniqueTitre(VoyageRepository $voyageRepository): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $maxAttempts = 10;
        $attempt = 0;

        do {
            $randomString = '';
            for ($i = 0; $i < 8; $i++) {
                $randomString .= $characters[rand(0, strlen($characters) - 1)];
            }
            $titre = 'Voyage-' . $randomString;
            $attempt++;
            
            // Vérifier si le titre existe déjà
            $existingVoyage = $voyageRepository->findOneBy(['titre' => $titre]);
        } while ($existingVoyage !== null && $attempt < $maxAttempts);

        if ($attempt >= $maxAttempts) {
            // Si on n'a pas trouvé de titre unique après plusieurs tentatives,
            // on ajoute un timestamp pour garantir l'unicité
            $titre = 'Voyage-' . $randomString . '-' . time();
        }

        return $titre;
    }

    #[Route('/{id}/edit', name: 'app_voyage_edit')]
    public function edit(Request $request, Voyage $voyage, EntityManagerInterface $entityManager, AffectationVehiculeRepository $affectationRepository, TypeChargementVoyageRepository $typeChargementRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage.update')) {
            $this->auditLogger->log(
                'tentative_update', 
                'Voyage', 
                $voyage->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier un voyage.');
            return $this->redirectToRoute('app_voyage_index');
        }

        // Sauvegarder les anciennes valeurs pour le log
        $oldData = $this->getVoyageDataForLog($voyage);

        if ($request->isMethod('POST')) {
            // Validation des données
            $data = $request->request->all();
            $errors = $this->validateVoyageData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                $this->auditLogger->log(
                    'tentative_update', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['errors' => $errors], 
                    'error'
                );
                return $this->redirectToRoute('app_voyage_edit', ['id' => $voyage->getId()]);
            }

            try {
                // Traitement des données du formulaire avec les nouvelles propriétés
                $voyage->setDateVoyage(new \DateTime($request->request->get('date_voyage')));
                $voyage->setTitre($request->request->get('titre_voyage'));
                $voyage->setQuantiteCarburant((int)$request->request->get('qte_carburant'));
                $voyage->setConvoyeur($request->request->get('convoyeur'));
                $voyage->setQuantiteChargement((int)$request->request->get('qte_chargement'));
                $voyage->setCommentaire($request->request->get('commentaire_voyage'));

                // Récupération des entités liées
                $affectation = $entityManager->getRepository(AffectationVehicule::class)->find($request->request->get('affectation'));
                $typeChargement = $entityManager->getRepository(TypeChargementVoyage::class)->find($request->request->get('type_chargement'));

                if (!$affectation) {
                    $this->auditLogger->log(
                        'update', 
                        'Voyage', 
                        $voyage->getId(), 
                        ['error' => 'Affectation non trouvée'], 
                        'error'
                    );
                    $this->addFlash('error', 'Affectation non trouvée.');
                    return $this->redirectToRoute('app_voyage_edit', ['id' => $voyage->getId()]);
                }
                $voyage->setAffectation($affectation);

                if ($typeChargement) {
                    $voyage->setTypeChargement($typeChargement);
                }

                $entityManager->flush();

                // Log de modification réussie
                $newData = $this->getVoyageDataForLog($voyage);
                $changes = $this->getChanges($oldData, $newData);
                
                $this->auditLogger->log(
                    'update', 
                    'Voyage', 
                    $voyage->getId(), 
                    $changes
                );

                $this->addFlash('success', 'Voyage modifié avec succès.');
                return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'update', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du voyage: ' . $e->getMessage());
            }
        }

        // Récupération des affectations (uniquement celles non fermées) et types de chargement pour le formulaire
        $affectations = $affectationRepository->findBy(['is_ferme' => false]);
        $typesChargement = $typeChargementRepository->findAll();

        return $this->render('voyage/edit.html.twig', [
            'voyage' => $voyage,
            'affectations' => $affectations,
            'types_chargement' => $typesChargement
        ]);
    }

    #[Route('/{id}', name: 'app_voyage_show')]
    public function show(Voyage $voyage): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage.view')) {
            $this->auditLogger->log(
                'tentative_view', 
                'Voyage', 
                $voyage->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails d\'un voyage.');
            return $this->redirectToRoute('app_voyage_index');
        }

        return $this->render('voyage/show.html.twig', [
            'voyage' => $voyage,
            'canEdit' => $this->isGranted('voyage.update'),
            'canDelete' => $this->isGranted('voyage.delete'),
            'canAddVehicule' => $this->isGranted('voyage_vehicule.create'),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_voyage_delete')]
    public function delete(Request $request, Voyage $voyage, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage.delete')) {
            $this->auditLogger->log(
                'tentative_delete', 
                'Voyage', 
                $voyage->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer un voyage.');
            return $this->redirectToRoute('app_voyage_index');
        }

        if ($this->isCsrfTokenValid('delete' . $voyage->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour le log
                $voyageData = $this->getVoyageDataForLog($voyage);
                
                $entityManager->remove($voyage);
                $entityManager->flush();
                
                // Log de suppression réussie
                $this->auditLogger->log(
                    'delete', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['deleted_data' => $voyageData]
                );
                
                $this->addFlash('success', 'Voyage supprimé avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'delete', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du voyage: ' . $e->getMessage());
            }
        } else {
            $this->auditLogger->log(
                'tentative_delete', 
                'Voyage', 
                $voyage->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }

    #[Route('/{id}/add-vehicule', name: 'app_voyage_add_vehicule')]
    public function addVehicule(Request $request, Voyage $voyage, EntityManagerInterface $entityManager, DestinationVoyageRepository $destinationRepo): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage_vehicule.create')) {
            $this->auditLogger->log(
                'tentative_add_vehicule', 
                'Voyage', 
                $voyage->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour ajouter un véhicule à un voyage.');
            return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
        }

        if ($request->isMethod('POST')) {
            // Validation des données
            $vehiculeId = $request->request->get('vehicule');
            $destinationId = $request->request->get('destination');
            
            if (empty($vehiculeId) || empty($destinationId)) {
                $this->auditLogger->log(
                    'tentative_add_vehicule', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['error' => 'Véhicule ou destination manquant'], 
                    'error'
                );
                $this->addFlash('error', 'Véhicule et destination sont obligatoires.');
                return $this->redirectToRoute('app_voyage_add_vehicule', ['id' => $voyage->getId()]);
            }

            try {
                $voyageVehicule = new VoyageVehicule();
                $voyageVehicule->setVoyage($voyage);

                // Récupération des entités liées
                $vehicule = $entityManager->getRepository(Vehicule::class)->find($vehiculeId);
                $destination = $destinationRepo->find($destinationId);

                if (!$vehicule || !$destination) {
                    $this->auditLogger->log(
                        'add_vehicule', 
                        'Voyage', 
                        $voyage->getId(), 
                        ['error' => 'Véhicule ou destination introuvable'], 
                        'error'
                    );
                    $this->addFlash('error', 'Véhicule ou destination introuvable.');
                    return $this->redirectToRoute('app_voyage_add_vehicule', ['id' => $voyage->getId()]);
                }

                $voyageVehicule->setDestination($destination);
                $voyageVehicule->setCommentaire($request->request->get('commentaire'));

                $entityManager->persist($voyageVehicule);
                $entityManager->flush();

                // Log d'ajout de véhicule réussi
                $this->auditLogger->log(
                    'add_vehicule', 
                    'Voyage', 
                    $voyage->getId(), 
                    [
                        'vehicule_id' => $vehicule->getId(),
                        'destination_id' => $destination->getId(),
                        'commentaire' => $voyageVehicule->getCommentaire()
                    ]
                );

                $this->addFlash('success', 'Véhicule ajouté au voyage avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'add_vehicule', 
                    'Voyage', 
                    $voyage->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du véhicule: ' . $e->getMessage());
            }

            return $this->redirectToRoute('app_voyage_show', ['id' => $voyage->getId()]);
        }

        $vehicules = $entityManager->getRepository(Vehicule::class)->findAll();
        $destinations = $destinationRepo->findAll();

        return $this->render('voyage/add_vehicule.html.twig', [
            'voyage' => $voyage,
            'vehicules' => $vehicules,
            'destinations' => $destinations,
        ]);
    }

    #[Route('/type-chargement/modal', name: 'app_type_chargement_modal')]
    public function typeChargementModal(Request $request, EntityManagerInterface $entityManager, TypeChargementVoyageRepository $typeChargementRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('voyage.create')) {
            $this->auditLogger->log(
                'tentative_create_type_chargement', 
                'TypeChargementVoyage', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return $this->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un type de chargement.'
            ]);
        }

        $typeChargement = new TypeChargementVoyage();

        if ($request->isMethod('POST')) {
            $libelle = $request->request->get('libelle');
            
            if (empty($libelle)) {
                $this->auditLogger->log(
                    'tentative_create_type_chargement', 
                    'TypeChargementVoyage', 
                    0, 
                    ['error' => 'Libellé manquant'], 
                    'error'
                );
                return $this->json([
                    'success' => false,
                    'message' => 'Le libellé est obligatoire.'
                ]);
            }

            try {
                $typeChargement->setLibelle($libelle);

                $entityManager->persist($typeChargement);
                $entityManager->flush();

                // Log de création de type de chargement réussi
                $this->auditLogger->log(
                    'create', 
                    'TypeChargementVoyage', 
                    $typeChargement->getId(), 
                    ['libelle' => $libelle]
                );

                return $this->json([
                    'success' => true,
                    'id' => $typeChargement->getId(),
                    'libelle' => $typeChargement->getLibelle()
                ]);
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'create', 
                    'TypeChargementVoyage', 
                    0, 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création du type de chargement: ' . $e->getMessage()
                ]);
            }
        }

        return $this->render('voyage/_type_chargement_modal.html.twig');
    }

    /**
     * Valider les données du formulaire de voyage
     */
    private function validateVoyageData(array $data): array
    {
        $errors = [];

        if (empty($data['date_voyage'])) {
            $errors[] = 'La date du voyage est obligatoire.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $data['date_voyage'])) {
            $errors[] = 'Le format de la date du voyage est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        if (empty($data['affectation'])) {
            $errors[] = 'L\'affectation est obligatoire.';
        }

        if (empty($data['qte_carburant']) || !is_numeric($data['qte_carburant'])) {
            $errors[] = 'La quantité de carburant est obligatoire et doit être un nombre.';
        }

        if (empty($data['convoyeur'])) {
            $errors[] = 'Le convoyeur est obligatoire.';
        }

        if (!empty($data['qte_chargement']) && !is_numeric($data['qte_chargement'])) {
            $errors[] = 'La quantité de chargement doit être un nombre.';
        }

        if (!empty($data['km_depart']) && !is_numeric($data['km_depart'])) {
            $errors[] = 'Le kilométrage de départ doit être un nombre.';
        }

        if (!empty($data['km_arrivee']) && !is_numeric($data['km_arrivee'])) {
            $errors[] = 'Le kilométrage d\'arrivée doit être un nombre.';
        }

        if (!empty($data['date_prevue_voyage']) && !\DateTime::createFromFormat('Y-m-d', $data['date_prevue_voyage'])) {
            $errors[] = 'Le format de la date prévue est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        if (!empty($data['heure_depart']) && !\DateTime::createFromFormat('H:i', $data['heure_depart'])) {
            $errors[] = 'Le format de l\'heure de départ est invalide. Utilisez le format HH:MM.';
        }

        if (!empty($data['heure_arrivee']) && !\DateTime::createFromFormat('H:i', $data['heure_arrivee'])) {
            $errors[] = 'Le format de l\'heure d\'arrivée est invalide. Utilisez le format HH:MM.';
        }

        return $errors;
    }

    /**
     * Préparer les données du voyage pour le log
     */
    private function getVoyageDataForLog(Voyage $voyage): array
    {
        return [
            'titre' => $voyage->getTitre(),
            'date_voyage' => $voyage->getDateVoyage() ? $voyage->getDateVoyage()->format('Y-m-d') : null,
            'affectation_id' => $voyage->getAffectation() ? $voyage->getAffectation()->getId() : null,
            'quantite_carburant' => $voyage->getQuantiteCarburant(),
            'convoyeur' => $voyage->getConvoyeur(),
            'type_chargement_id' => $voyage->getTypeChargement() ? $voyage->getTypeChargement()->getId() : null,
            'quantite_chargement' => $voyage->getQuantiteChargement(),
            'km_depart' => $voyage->getKmDepart(),
            'km_arrivee' => $voyage->getKmArrivee(),
            'date_prevue_voyage' => $voyage->getDatePrevueVoyage() ? $voyage->getDatePrevueVoyage()->format('Y-m-d') : null,
            'heure_depart' => $voyage->getHeureDepart() ? $voyage->getHeureDepart()->format('H:i') : null,
            'heure_arrivee' => $voyage->getHeureArrivee() ? $voyage->getHeureArrivee()->format('H:i') : null,
            'commentaire' => $voyage->getCommentaire(),
            'vehicules_count' => count($voyage->getVoyageVehicules())
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