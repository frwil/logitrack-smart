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
$trajetCtrl = new TrajetController($trajetRepo);
$objectifCtrl = new ObjectifController($objectifRepo);
$userCtrl = new UserController($userRepo, $regionRepo, $entiteRepo);

$routes = [
    // Auth
    'name-user'           => [$authCtrl, 'login'],
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

    // Convoyeur
    'id-convoyeur-forModal' => [$convoyeurCtrl, 'fetchByHash'],
    'id-convoyeur'        => [$convoyeurCtrl, 'update'],
    'id-convoyeur-forDel' => [$convoyeurCtrl, 'delete'],

    // Affectation
    'id-affectation-forModal' => [$affectationCtrl, 'fetchById'],
    'id-affectation'      => [$affectationCtrl, 'update'],
    'id-affectation-forDel' => [$affectationCtrl, 'delete'],
    'id-aff-toClose'      => [$affectationCtrl, 'close'],

    // Voyage
    'id-voyage-forModal'  => [$voyageCtrl, 'fetchByHash'],
    'id-voyage'           => [$voyageCtrl, 'update'],
    'id-voyage-forDel'    => [$voyageCtrl, 'delete'],

    // Maintenance — Vidange
    'c-vd-s'              => [$maintenanceCtrl, 'fetchVidange'],
    'c-upd-vd'            => [$maintenanceCtrl, 'updateVidange'],
    'del-vd-id'           => [$maintenanceCtrl, 'deleteVidange'],
    'cd-vd-hist'          => [$maintenanceCtrl, 'historiqueVidange'],

    // Maintenance — Prestataire
    'c-pt-s'              => [$maintenanceCtrl, 'fetchPrestataire'],
    'id-upd-pt'           => [$maintenanceCtrl, 'updatePrestataire'],
    'del-pt-id'           => [$maintenanceCtrl, 'deletePrestataire'],

    // Maintenance — Centre coûts
    'c-cc-s'              => [$maintenanceCtrl, 'fetchCentreCout'],
    'del-cc-id'           => [$maintenanceCtrl, 'deleteCentreCout'],

    // Maintenance — Relevé KMS
    'semPer'              => [$maintenanceCtrl, 'fetchPeriodes'],
    'vhPer'               => [$maintenanceCtrl, 'fetchKmReleve'],
    'updRel'              => [$maintenanceCtrl, 'updateReleve'],

    // Config — Type permis
    'c-dl-s'              => [$configCtrl, 'fetchTypePermis'],
    'lib-type-upd'        => [$configCtrl, 'updateTypePermis'],
    'dl-id'               => [$configCtrl, 'deleteTypePermis'],

    // Config — Document
    'nom-doc-upd'         => [$configCtrl, 'updateDocument'],

    // Config — Folder
    'vh-folder-upd'       => [$configCtrl, 'updateFolder'],

    // Trajet
    'id-destination-forModal' => [$trajetCtrl, 'fetchByHash'],
    'id-destination'      => [$trajetCtrl, 'update'],
    'id-destination-forDel' => [$trajetCtrl, 'delete'],

    // Marque
    'id-marque-forModal'  => [$marqueCtrl, 'fetchByHash'],
    'id-marque'           => [$marqueCtrl, 'update'],
    'id-marque-forDel'    => [$marqueCtrl, 'delete'],

    // Modele
    'id-modele-forModal'  => [$modeleCtrl, 'fetchByHash'],
    'id-modele'           => [$modeleCtrl, 'update'],
    'id-modele-forDel'    => [$modeleCtrl, 'delete'],

    // Objectif
    'id-objectif-forModal' => [$objectifCtrl, 'fetchByHash'],
    'id-objectif'         => [$objectifCtrl, 'update'],
    'id-objectif-forDel'  => [$objectifCtrl, 'delete'],

    // Users
    'new-user'             => [$userCtrl, 'create'],
    'id-user-forModal'     => [$userCtrl, 'fetchByHash'],
    'id-user-upd'          => [$userCtrl, 'update'],
    'id-user-forDel'       => [$userCtrl, 'delete'],
    'id-user-active'       => [$userCtrl, 'toggleActive'],
];

// For JSON requests, PHP does not populate $_POST — decode the raw body.
$jsonPost = [];
if (empty($_POST)) {
    $decoded = json_decode(file_get_contents('php://input'), true);
    if (is_array($decoded)) $jsonPost = $decoded;
}

// Dispatch: check if any POST key (form-encoded or JSON) matches a route.
foreach ($routes as $key => [$controller, $method]) {
    if (isset($_POST[$key]) || isset($jsonPost[$key])) {
        $controller->$method();
    }
}
