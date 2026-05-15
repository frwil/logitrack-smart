<?php
/**
 * POST request router — maps incoming POST keys to controller methods.
 * Controllers return JSON and die(). If no route matches, execution continues to the view.
 *
 * To migrate a handler:
 * 1. Add its POST key => [ControllerClass, 'method'] to the $routes map below.
 * 2. Remove the old die("%%%%%%X") block from the PHP file.
 * 3. Update the JS $.ajax().done() to use the JSON response instead of .split('%%%%%%').
 */

// Only route if $con is available (i.e., authenticated or on login page).
if (!isset($con)) return;

$userRepo = new UserRepository($con);
$regionRepo = new RegionRepository($con);
$entiteRepo = new EntiteRepository($con);
$vehiculeRepo = new VehiculeRepository($con);
$marqueRepo = new MarqueRepository($con);
$modeleRepo = new ModeleRepository($con);
$chauffeurRepo = new ChauffeurRepository($con);
$convoyeurRepo = new ConvoyeurRepository($con);
$affectationRepo = new AffectationRepository($con);
$voyageRepo = new VoyageRepository($con);
$maintenanceRepo = new MaintenanceRepository($con);
$configRepo = new ConfigRepository($con);
$trajetRepo = new TrajetRepository($con);
$objectifRepo = new ObjectifRepository($con);

$authCtrl = new AuthController($userRepo, $regionRepo, $entiteRepo);
$vehiculeCtrl = new VehiculeController($vehiculeRepo);
$chauffeurCtrl = new ChauffeurController($chauffeurRepo);
$convoyeurCtrl = new ConvoyeurController($convoyeurRepo);
$affectationCtrl = new AffectationController($affectationRepo);
$voyageCtrl = new VoyageController($voyageRepo);
$maintenanceCtrl = new MaintenanceController($maintenanceRepo);
$configCtrl = new ConfigController($configRepo);
$marqueCtrl = new MarqueController($marqueRepo);
$modeleCtrl = new ModeleController($modeleRepo);
$typeChargementRepo = new TypeChargementRepository($con);
$trajetCtrl = new TrajetController($trajetRepo);
$objectifCtrl = new ObjectifController($objectifRepo);
$typeChargementCtrl = new TypeChargementController();
$userCtrl = new UserController($userRepo, $regionRepo, $entiteRepo);

$routes = [
    // Auth
    'login'               => [$authCtrl, 'login'],
    'nContext'            => [$authCtrl, 'switchContext'],

    // Vehicule
    'im-vh-upd'           => [$vehiculeCtrl, 'fetchByHash'],
    'vh-del-id'           => [$vehiculeCtrl, 'delete'],
    'immat-vh-upd'        => [$vehiculeCtrl, 'update'],
    'immat-vh'            => [$vehiculeCtrl, 'create'],

    // Chauffeur
    'id-chauffeur-forModal' => [$chauffeurCtrl, 'fetchByHash'],
    'id-chauffeur'        => [$chauffeurCtrl, 'update'],
    'id-chauffeur-forDel' => [$chauffeurCtrl, 'delete'],
    'nom-chauffeur'       => [$chauffeurCtrl, 'create'],
    'refresh-chauffeur'   => [$chauffeurCtrl, 'refreshOptions'],

    // Convoyeur
    'id-convoyeur-forModal' => [$convoyeurCtrl, 'fetchByHash'],
    'id-convoyeur'        => [$convoyeurCtrl, 'update'],
    'id-convoyeur-forDel' => [$convoyeurCtrl, 'delete'],
    'nom-convoyeur'       => [$convoyeurCtrl, 'create'],
    'refresh-convoyeur'   => [$convoyeurCtrl, 'refreshOptions'],

    // Affectation
    'id-affectation-forModal' => [$affectationCtrl, 'fetchById'],
    'id-affectation'      => [$affectationCtrl, 'update'],
    'id-affectation-forDel' => [$affectationCtrl, 'delete'],
    'id-aff-toClose'      => [$affectationCtrl, 'close'],
    'id-vehicule-aff'     => [$affectationCtrl, 'create'],

    // Voyage
    'id-voyage-forModal'  => [$voyageCtrl, 'fetchByHash'],
    'id-voyage'           => [$voyageCtrl, 'update'],
    'id-voyage-forDel'    => [$voyageCtrl, 'delete'],
    'titre-vg'            => [$voyageCtrl, 'create'],

    // Voyage — Dashboard
    'load-voyages-vs-obj'     => [$voyageCtrl, 'voyagesVsObjectives'],
    'load-top-destinations'   => [$voyageCtrl, 'topDestinations'],
    'load-conso-per-vehicle'  => [$voyageCtrl, 'consoPerVehicle'],
    'load-vehicules-inactifs' => [$voyageCtrl, 'vehiculesInactifs'],

    // Maintenance — Vidange
    'c-vd-s'              => [$maintenanceCtrl, 'fetchVidange'],
    'c-upd-vd'            => [$maintenanceCtrl, 'updateVidange'],
    'del-vd-id'           => [$maintenanceCtrl, 'deleteVidange'],
    'cd-vd-hist'          => [$maintenanceCtrl, 'historiqueVidange'],
    'date-vd'             => [$maintenanceCtrl, 'createVidange'],

    // Maintenance — Prestataire
    'c-pt-s'              => [$maintenanceCtrl, 'fetchPrestataire'],
    'id-upd-pt'           => [$maintenanceCtrl, 'updatePrestataire'],
    'del-pt-id'           => [$maintenanceCtrl, 'deletePrestataire'],
    'nom-pt'              => [$maintenanceCtrl, 'createPrestataire'],

    // Maintenance — Centre coûts
    'nom-cc'              => [$maintenanceCtrl, 'createCentreCout'],
    'id-upd-cc'           => [$maintenanceCtrl, 'updateCentreCout'],
    'c-cc-s'              => [$maintenanceCtrl, 'fetchCentreCout'],
    'del-cc-id'           => [$maintenanceCtrl, 'deleteCentreCout'],

    // Maintenance — Relevé KMS
    'semPer'              => [$maintenanceCtrl, 'fetchPeriodes'],
    'vhPer'               => [$maintenanceCtrl, 'fetchKmReleve'],
    'updRel'              => [$maintenanceCtrl, 'updateReleve'],
    'vh-releve-kms'       => [$maintenanceCtrl, 'createReleveKms'],
    'idvhlkms'            => [$maintenanceCtrl, 'fetchLastKm'],
    'per-releve'          => [$maintenanceCtrl, 'fetchSemaines'],

    // Maintenance — Bon de réparation
    'num-br'              => [$maintenanceCtrl, 'createBonReparation'],
    'c-br-s'              => [$maintenanceCtrl, 'fetchBonReparation'],
    'id-upd-br'           => [$maintenanceCtrl, 'updateBonReparation'],
    'del-br-id'           => [$maintenanceCtrl, 'deleteBonReparation'],
    'load-cc-br'          => [$maintenanceCtrl, 'fetchAllCentresCouts'],

    // Maintenance — Dashboard analytics
    'load-budget-projection'   => [$maintenanceCtrl, 'budgetProjection'],
    'load-provider-comparison' => [$maintenanceCtrl, 'providerComparison'],
    'load-cost-per-km'         => [$maintenanceCtrl, 'costPerKm'],
    'load-cost-by-centre'      => [$maintenanceCtrl, 'costByCentre'],
    'load-recurrence'          => [$maintenanceCtrl, 'recurrence'],
    'load-duree-diagnostic'    => [$maintenanceCtrl, 'dureeByDiagnostic'],
    'load-cout-type'           => [$maintenanceCtrl, 'coutByType'],
    'load-docs-expiration'     => [$maintenanceCtrl, 'docsExpiration'],
    'load-chauffeur-impact'    => [$maintenanceCtrl, 'chauffeurImpact'],
    'load-repair-conflicts'    => [$maintenanceCtrl, 'repairConflicts'],
    'load-dashboard-all'      => [$maintenanceCtrl, 'dashboardAll'],
    'load-health-scores'      => [$maintenanceCtrl, 'healthScoresHtml'],
    'load-upcoming-vidanges'  => [$maintenanceCtrl, 'upcomingVidangesHtml'],

    // Config — Type permis
    'c-dl-s'              => [$configCtrl, 'fetchTypePermis'],
    'lib-type-upd'        => [$configCtrl, 'updateTypePermis'],
    'dl-id'               => [$configCtrl, 'deleteTypePermis'],
    'lib-type'            => [$configCtrl, 'createTypePermis'],

    // Config — Document
    'nom-doc-upd'         => [$configCtrl, 'updateDocument'],
    'nom-doc'             => [$configCtrl, 'createDocument'],
    'id-doc-del'          => [$configCtrl, 'deleteDocument'],

    // Config — Folder
    'vh-folder-upd'       => [$configCtrl, 'updateFolder'],
    'vh-folder'           => [$configCtrl, 'createFolder'],
    'ref-folder-del'      => [$configCtrl, 'deleteFolder'],

    // Config — Paramètres
    'update-devise'       => [$configCtrl, 'updateDevise'],
    'backup-db'           => [$configCtrl, 'backupDatabase'],

    // Trajet
    'id-destination-forModal' => [$trajetCtrl, 'fetchByHash'],
    'id-destination'      => [$trajetCtrl, 'update'],
    'id-destination-forDel' => [$trajetCtrl, 'delete'],
    'nom-destination'     => [$trajetCtrl, 'create'],
    'refresh-destination' => [$trajetCtrl, 'refreshOptions'],

    // Marque
    'id-marque-forModal'  => [$marqueCtrl, 'fetchByHash'],
    'id-marque'           => [$marqueCtrl, 'update'],
    'id-marque-forDel'    => [$marqueCtrl, 'delete'],
    'nom-marque'          => [$marqueCtrl, 'create'],
    'refresh-marque'      => [$marqueCtrl, 'refresh'],

    // Modele
    'id-modele-forModal'  => [$modeleCtrl, 'fetchByHash'],
    'id-modele'           => [$modeleCtrl, 'update'],
    'id-modele-forDel'    => [$modeleCtrl, 'delete'],
    'nom-modele'          => [$modeleCtrl, 'create'],
    'refresh-modele'      => [$modeleCtrl, 'refresh'],

    // Type chargement
    'id-typechargement-forModal' => [$typeChargementCtrl, 'fetchByHash'],
    'id-typechargement'      => [$typeChargementCtrl, 'update'],
    'id-typechargement-forDel' => [$typeChargementCtrl, 'delete'],
    'lib-typechargement'     => [$typeChargementCtrl, 'create'],

    // Objectif
    'id-objectif-forModal' => [$objectifCtrl, 'fetchByHash'],
    'id-objectif'         => [$objectifCtrl, 'update'],
    'id-objectif-forDel'  => [$objectifCtrl, 'delete'],
    'date-objectif'       => [$objectifCtrl, 'create'],

    // Users
    'new-user'             => [$userCtrl, 'create'],
    'id-user-forModal'     => [$userCtrl, 'fetchByHash'],
    'id-user-upd'          => [$userCtrl, 'update'],
    'id-user-forDel'       => [$userCtrl, 'delete'],
    'id-user-active'       => [$userCtrl, 'toggleActive'],
];

// $jsonPost is already decoded in index.php (CSRF check reads php://input first).
// php://input is single-read, so we reuse the saved value.
if (!isset($jsonPost)) $jsonPost = [];

// Dispatch: check if any POST key (form-encoded or JSON) matches a route.
foreach ($routes as $key => [$controller, $method]) {
    if (isset($_POST[$key]) || isset($jsonPost[$key])) {
        $controller->$method();
    }
}
