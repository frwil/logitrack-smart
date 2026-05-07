<?php

namespace App\Controller;

use App\Repository\VehiculeRepository;
use App\Repository\DocumentVehiculeRepository;
use App\Repository\BonReparationRepository;
use App\Repository\ReleveKmsVehiculeRepository;
use App\Repository\AffectationVehiculeRepository;
use App\Repository\TypeChargementVoyageRepository;
use App\Repository\VoyageRepository;
use App\Repository\ChauffeurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EntiteRepository;
use App\Repository\RegionRepository;
use Symfony\Component\HttpFoundation\Request;

class ReportController extends AbstractController
{
    #[Route('/report/vehicule-status', name: 'app_report_vehicule_status')]
    public function vehicleStatus(VehiculeRepository $vehiculeRepository, DocumentVehiculeRepository $documentVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports sur l\'état des véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $vehicules = $vehiculeRepository->findActiveVehicules();

        // Calcul des statistiques
        $vehiculesActifs = $vehiculeRepository->count(['statut' => true]);

        // Véhicules avec documents expirant (dans les 30 prochains jours)
        $dateExpirationLimite = new \DateTime('+30 days');
        $vehiculesAvecDocumentsExpirant = $documentVehiculeRepository->countDocumentsExpirant($dateExpirationLimite);

        return $this->render('report/vehicle_status.html.twig', [
            'vehicules' => $vehicules,
            'vehiculesActifs' => $vehiculesActifs,
            'vehiculesAvecDocumentsExpirant' => $vehiculesAvecDocumentsExpirant,
        ]);
    }

    #[Route('/report/documents-expiration', name: 'app_report_documents_expiration')]
    public function documentsExpiration(DocumentVehiculeRepository $documentRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports d\'expiration des documents.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $documents = $documentRepository->findExpiringDocuments();

        return $this->render('report/documents_expiration.html.twig', [
            'documents' => $documents,
        ]);
    }

    #[Route('/report/maintenance-costs', name: 'app_report_maintenance_costs')]
    public function maintenanceCosts(BonReparationRepository $bonReparationRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports sur les coûts de maintenance.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $maintenanceCosts = $bonReparationRepository->findMaintenanceCostsByPeriod();

        return $this->render('report/maintenance_costs.html.twig', [
            'maintenanceCosts' => $maintenanceCosts,
        ]);
    }

    #[Route('/report/mileage', name: 'app_report_mileage')]
    public function mileage(
        ReleveKmsVehiculeRepository $releveRepository,
        AffectationVehiculeRepository $affectationRepository,
        Request $request
    ): Response {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports de kilométrage.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $mileageData = $releveRepository->findMileageStatistics();

        // Récupérer toutes les affectations actives pour le filtre
        $affectations = $affectationRepository->findBy(['is_ferme' => false]);

        // Récupérer les paramètres de filtrage
        $selectedAffectationId = $request->query->get('affectation');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        $graphData = [];
        $selectedAffectation = null;

        // Si une affectation est sélectionnée, récupérer les données pour le graphique
        if ($selectedAffectationId) {
            $selectedAffectation = $affectationRepository->find($selectedAffectationId);

            // Définir les dates par défaut si non spécifiées
            if (!$startDate) {
                $startDate = (new \DateTime())->modify('-6 months')->format('Y-m-d');
            }
            if (!$endDate) {
                $endDate = (new \DateTime())->format('Y-m-d');
            }

            // Validation des dates
            try {
                $startDateTime = new \DateTime($startDate);
                $endDateTime = new \DateTime($endDate);
                
                if ($startDateTime > $endDateTime) {
                    $this->addFlash('error', 'La date de début ne peut pas être postérieure à la date de fin.');
                    $graphData = [];
                } else {
                    // Récupérer les données pour le graphique
                    $graphData = $releveRepository->findMileageEvolution(
                        $selectedAffectationId,
                        $startDateTime,
                        $endDateTime
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide.');
                $graphData = [];
            }
        }

        return $this->render('report/mileage.html.twig', [
            'mileageData' => $mileageData,
            'affectations' => $affectations,
            'selectedAffectation' => $selectedAffectationId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'graphData' => $graphData,
        ]);
    }

    #[Route('/report/vehicle-assignments', name: 'app_report_vehicle_assignments')]
    public function vehicleAssignments(Request $request, AffectationVehiculeRepository $affectationRepository, EntiteRepository $entiteRepository, RegionRepository $regionRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports d\'affectation des véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer les paramètres de filtrage
        $entityFilter = $request->query->get('entity');
        $regionFilter = $request->query->get('region');
        $statusFilter = $request->query->get('status');

        $entityId = !empty($entityFilter) ? (int)$entityFilter : null;
        $regionId = !empty($regionFilter) ? (int)$regionFilter : null;

        // Récupérer les affectations avec filtres
        $assignments = $affectationRepository->findWithFilters($entityId, $regionId, $statusFilter);

        // Récupérer toutes les entités et régions pour les filtres
        $entites = $entiteRepository->findBy(['statut' => true]);
        $regions = $regionRepository->findAll();

        return $this->render('report/vehicle_assignments.html.twig', [
            'assignments' => $assignments,
            'entites' => $entites,
            'regions' => $regions,
            'selectedEntity' => $entityFilter,
            'selectedRegion' => $regionFilter,
            'selectedStatus' => $statusFilter,
        ]);
    }

    #[Route('/report/trips', name: 'app_report_trips')]
    public function trips(Request $request, VoyageRepository $voyageRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports de voyages.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer les paramètres de filtrage
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');

        // Convertir les dates si elles sont fournies
        $startDate = null;
        $endDate = null;

        if (!empty($dateDebut)) {
            try {
                $startDate = new \DateTime($dateDebut);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date de début invalide.');
            }
        }

        if (!empty($dateFin)) {
            try {
                $endDate = new \DateTime($dateFin);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date de fin invalide.');
            }
        }

        // Vérifier la cohérence des dates
        if ($startDate && $endDate && $startDate > $endDate) {
            $this->addFlash('error', 'La date de début ne peut pas être postérieure à la date de fin.');
            $startDate = null;
            $endDate = null;
        }

        // Récupérer les voyages avec filtres
        $trips = $voyageRepository->findTripsByPeriod($startDate, $endDate);

        return $this->render('report/trips.html.twig', [
            'trips' => $trips,
            'selectedDateDebut' => $dateDebut,
            'selectedDateFin' => $dateFin,
        ]);
    }

    #[Route('/report/fuel-consumption', name: 'app_report_fuel_consumption')]
    public function fuelConsumption(
        Request $request,
        VoyageRepository $voyageRepository,
        AffectationVehiculeRepository $affectationRepository
    ): Response {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports de consommation de carburant.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Get filter parameters
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $affectationId = $request->query->get('affectation');

        // Set default dates (current month) if not provided
        if (!$dateDebut) {
            $dateDebutObj = new \DateTime('first day of this month');
        } else {
            // Parse date from d/m/Y format
            $dateDebutObj = \DateTime::createFromFormat('d/m/Y', $dateDebut);
            if (!$dateDebutObj) {
                // Fallback to standard format if custom format fails
                try {
                    $dateDebutObj = new \DateTime($dateDebut);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Format de date de début invalide.');
                    $dateDebutObj = new \DateTime('first day of this month');
                }
            }
        }

        if (!$dateFin) {
            $dateFinObj = new \DateTime('last day of this month');
        } else {
            // Parse date from d/m/Y format
            $dateFinObj = \DateTime::createFromFormat('d/m/Y', $dateFin);
            if (!$dateFinObj) {
                // Fallback to standard format if custom format fails
                try {
                    $dateFinObj = new \DateTime($dateFin);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Format de date de fin invalide.');
                    $dateFinObj = new \DateTime('last day of this month');
                }
            }
        }

        // Vérifier la cohérence des dates
        if ($dateDebutObj > $dateFinObj) {
            $this->addFlash('error', 'La date de début ne peut pas être postérieure à la date de fin.');
            // Réinitialiser aux valeurs par défaut
            $dateDebutObj = new \DateTime('first day of this month');
            $dateFinObj = new \DateTime('last day of this month');
        }

        $fuelConsumption = $voyageRepository->findFuelConsumptionStats(
            $dateDebutObj,
            $dateFinObj,
            $affectationId
        );

        // Get last fill-ups for each vehicle
        $lastFillUps = $this->getLastFillUps($dateDebutObj, $dateFinObj, $affectationId, $voyageRepository);

        // Get consumption evolution data for the chart
        $consumptionEvolution = $voyageRepository->findConsumptionEvolution(
            $dateDebutObj,
            $dateFinObj,
            $affectationId
        );

        // Get all active affectations for the filter dropdown
        $affectations = $affectationRepository->findBy(['is_ferme' => false]);

        return $this->render('report/fuel_consumption.html.twig', [
            'fuelConsumption' => $fuelConsumption,
            'lastFillUps' => $lastFillUps,
            'affectations' => $affectations,
            'selectedAffectation' => $affectationId,
            'dateDebut' => $dateDebutObj->format('d/m/Y'),
            'dateFin' => $dateFinObj->format('d/m/Y'),
            'consumptionEvolution' => $consumptionEvolution
        ]);
    }

    #[Route('/report/driver-activity', name: 'app_report_driver_activity')]
    public function driverActivity(Request $request, ChauffeurRepository $chauffeurRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports d\'activité des chauffeurs.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer les paramètres de filtrage
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $statutFilter = $request->query->get('statut');

        // Convertir les dates si elles sont fournies
        $startDate = null;
        $endDate = null;

        if (!empty($dateDebut)) {
            $startDate = \DateTime::createFromFormat('d/m/Y', $dateDebut);
            if (!$startDate) {
                $this->addFlash('error', 'Format de date de début invalide. Utilisez le format JJ/MM/AAAA.');
            }
        }

        if (!empty($dateFin)) {
            $endDate = \DateTime::createFromFormat('d/m/Y', $dateFin);
            if (!$endDate) {
                $this->addFlash('error', 'Format de date de fin invalide. Utilisez le format JJ/MM/AAAA.');
            }
        }

        // Vérifier la cohérence des dates
        if ($startDate && $endDate && $startDate > $endDate) {
            $this->addFlash('error', 'La date de début ne peut pas être postérieure à la date de fin.');
            $startDate = null;
            $endDate = null;
        }

        // Récupérer les statistiques d'activité des chauffeurs avec filtres
        $driverActivity = $chauffeurRepository->findDriverActivityStats($startDate, $endDate, $statutFilter);

        // Récupérer les statistiques récapitulatives
        $totalChauffeurs = count($driverActivity);
        $chauffeursActifs = 0;
        $chauffeursInactifs = 0;
        $totalKilometrage = 0;

        foreach ($driverActivity as $driver) {
            if ($driver['chauffeur']->getEstActif()) {
                $chauffeursActifs++;
            } else {
                $chauffeursInactifs++;
            }
            $totalKilometrage += $driver['kilometrageTotal'];
        }

        return $this->render('report/driver_activity.html.twig', [
            'driverActivity' => $driverActivity,
            'totalChauffeurs' => $totalChauffeurs,
            'chauffeursActifs' => $chauffeursActifs,
            'chauffeursInactifs' => $chauffeursInactifs,
            'totalKilometrage' => $totalKilometrage,
            'selectedDateDebut' => $dateDebut,
            'selectedDateFin' => $dateFin,
            'selectedStatut' => $statutFilter
        ]);
    }

    #[Route('/report/trips-statistics', name: 'app_report_trips_statistics')]
    public function tripsStatistics(
        Request $request,
        VoyageRepository $voyageRepository,
        VehiculeRepository $vehiculeRepository,
        TypeChargementVoyageRepository $typeChargementRepository
    ): Response {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux statistiques de voyages.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupération des paramètres de filtrage
        $selectedYear = $request->query->getInt('year', date('Y'));
        $selectedVehiculeId = $request->query->get('vehicule');

        // Validation de l'année
        $currentYear = date('Y');
        if ($selectedYear < 2000 || $selectedYear > $currentYear + 5) {
            $this->addFlash('error', 'L\'année sélectionnée est invalide.');
            $selectedYear = $currentYear;
        }

        // Récupérer les statistiques mensuelles
        $monthlyStats = $voyageRepository->getMonthlyStats($selectedYear, $selectedVehiculeId);

        // Calculer les totaux
        $totals = [
            'prevus' => 0,
            'realises' => 0,
            'respecte_delai' => 0,
            'en_retard' => 0,
            'en_avance' => 0,
            'total_chargement' => 0,
            'chargement_dans_delai' => 0,
            'chargement_retard' => 0,
            'chargement_avance' => 0
        ];

        foreach ($monthlyStats as $stats) {
            $totals['prevus'] += $stats['prevus'];
            $totals['realises'] += $stats['realises'];
            $totals['respecte_delai'] += $stats['respecte_delai'];
            $totals['en_retard'] += $stats['en_retard'];
            $totals['en_avance'] += $stats['en_avance'];
            $totals['total_chargement'] += $stats['total_chargement'];
            $totals['chargement_dans_delai'] += $stats['chargement_dans_delai'];
            $totals['chargement_retard'] += $stats['chargement_retard'];
            $totals['chargement_avance'] += $stats['chargement_avance'];
        }

        // Récupérer les statistiques par type de chargement
        $loadingTypeStats = $this->getLoadingTypeStats($selectedYear, $selectedVehiculeId, $voyageRepository, $typeChargementRepository);

        // Récupérer les années disponibles (basées sur les voyages existants)
        $years = $voyageRepository->getAvailableYears();
        if (empty($years)) {
            $years = [date('Y')];
        }

        // Récupérer tous les véhicules pour le filtre
        $vehicules = $vehiculeRepository->findAll();

        return $this->render('report/trips_statistics.html.twig', [
            'monthlyStats' => $monthlyStats,
            'totals' => $totals,
            'loadingTypeStats' => $loadingTypeStats,
            'years' => $years,
            'selectedYear' => $selectedYear,
            'vehicules' => $vehicules,
            'selectedVehicule' => $selectedVehiculeId
        ]);
    }

    #[Route('/report/maintenance-ongoing', name: 'app_maintenance_ongoing')]
    public function ongoingMaintenance(BonReparationRepository $bonReparationRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('report.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux rapports de maintenance en cours.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer uniquement les réparations en cours (non clôturées)
        $ongoingMaintenance = $bonReparationRepository->findBy(['cloture' => false]);

        return $this->render('report/maintenance_ongoing.html.twig', [
            'maintenanceCosts' => $ongoingMaintenance,
            'isOngoingView' => true,
        ]);
    }

    private function getLastFillUps($dateDebut, $dateFin, $affectationId, VoyageRepository $voyageRepository)
    {
        $qb = $voyageRepository->createQueryBuilder('v')
            ->select([
                'v.quantiteCarburant as volume',
                'v.dateVoyage as date',
                'a.id as affectation_id',
                'veh.immatriculation_vehicule',
                '(v.quantiteCarburant * 1.5) as cout'
            ])
            ->leftJoin('v.affectation', 'a')
            ->leftJoin('a.id_vehicule', 'veh')
            ->where('v.quantiteCarburant > 0')
            ->orderBy('v.dateVoyage', 'DESC');

        if ($dateDebut && $dateFin) {
            $qb->andWhere('v.dateVoyage BETWEEN :debut AND :fin')
                ->setParameter('debut', $dateDebut)
                ->setParameter('fin', $dateFin);
        }

        if ($affectationId) {
            $qb->andWhere('v.affectation = :affectationId')
                ->setParameter('affectationId', $affectationId);
        }

        $results = $qb->getQuery()->getResult();

        $lastFillUps = [];
        foreach ($results as $result) {
            $vehicleId = $result['affectation_id'];
            if (!isset($lastFillUps[$vehicleId])) {
                $lastFillUps[$vehicleId] = [
                    'volume' => $result['volume'],
                    'date' => $result['date'],
                    'cout' => $result['cout'],
                    'vehicule' => ['immatriculation_vehicule' => $result['immatriculation']]
                ];
            }
        }

        return $lastFillUps;
    }

    private function getLoadingTypeStats(
        int $year,
        ?int $vehiculeId,
        VoyageRepository $voyageRepository,
        TypeChargementVoyageRepository $typeChargementRepository
    ): array {
        // Récupérer tous les types de chargement
        $typesChargement = $typeChargementRepository->findAll();
        $stats = [];

        foreach ($typesChargement as $type) {
            // Utiliser une méthode du repository pour récupérer les statistiques par type
            $typeStats = $voyageRepository->getStatsByLoadingType($year, $vehiculeId, $type->getId());

            // Ne inclure que les types avec un total > 0
            if ($typeStats['total'] > 0) {
                $stats[$type->getLibelle()] = [
                    'total' => $typeStats['total'],
                    'dans_delai' => $typeStats['dans_delai'],
                    'en_avance' => $typeStats['en_avance'],
                    'en_retard' => $typeStats['en_retard']
                ];
            }
        }

        return $stats;
    }
}