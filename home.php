<?php
global $partial;
$renderPartial = !empty($partial);
/* POST /switchRegion handled by AuthController — see controllers/router.php */
if(!isset($user_rights)) $user_rights = [];

// Initialize rights arrays — must be outside the !$renderPartial block
// because partial AJAX reloads skip the navbar where these are also set.
$rights_vehicule = array();
if (isRightObjectAllowed('vehicules', $user_rights) != false) $rights_vehicule = explode(',', isRightObjectAllowed('vehicules', $user_rights));
$rights_voyage = array();
if (isRightObjectAllowed('voyages', $user_rights) != false) $rights_voyage = explode(',', isRightObjectAllowed('voyages', $user_rights));
$rights_affectation = array();
if (isRightObjectAllowed('affectationVehicules', $user_rights) != false) $rights_affectation = explode(',', isRightObjectAllowed('affectationVehicules', $user_rights));
$rights_maintenance = array();
if (isRightObjectAllowed('maintenances', $user_rights) != false) $rights_maintenance = explode(',', isRightObjectAllowed('maintenances', $user_rights));
$rights_user = array();
if (isRightObjectAllowed('users', $user_rights) != false) $rights_user = explode(',', isRightObjectAllowed('users', $user_rights));
$rights_config = array();
if (isRightObjectAllowed('config', $user_rights) != false) $rights_config = explode(',', isRightObjectAllowed('config', $user_rights));
$rights_report = array();
if (isRightObjectAllowed('report', $user_rights) != false) $rights_report = explode(',', isRightObjectAllowed('report', $user_rights));

if (!$renderPartial):
?>
<script>
    function exportTableToExcel(tableId, filename = '') {
        const table = document.getElementById(tableId);

        const worksheet = XLSX.utils.table_to_sheet(table);

        const workbook = XLSX.utils.book_new();

        XLSX.utils.book_append_sheet(workbook, worksheet, 'Feuille1');

        const excelBuffer = XLSX.write(workbook, {
            bookType: 'xlsx',
            type: 'array'
        });

        const blob = new Blob([excelBuffer], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });

        if (!filename) {
            const date = new Date();
            filename = `export_${date.toISOString().split('T')[0]}_${date.getHours()}${date.getMinutes()}.xlsx`;
        }

        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();

        URL.revokeObjectURL(link.href);
    }
    
</script>
<div class="lt-navbar">
    <div class="d-flex align-items-center flex-grow-1">
        <a class="lt-navbar-brand" href="?page=vehicules">
            <i class="fa fa-truck"></i> LogiTrack
        </a>
        <button class="lt-navbar-toggle" type="button" aria-label="Menu" onclick="document.querySelector('.lt-navbar-links').classList.toggle('show'); this.classList.toggle('open');">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="lt-navbar-links">
        <?php $rights_vehicule = array();
        if (isRightObjectAllowed('vehicules', $user_rights) != false): $rights_vehicule = explode(',', isRightObjectAllowed('vehicules', $user_rights)); ?>
            <a class="nav-link <?php if (!isset($_GET['page']) || $_GET['page'] == 'vehicules') echo "active"; ?>" href="?page=vehicules">Véhicules</a>
        <?php endif; ?>
        <?php $rights_affectation = array();
        if (isRightObjectAllowed('voyages', $user_rights) != false): $rights_voyage = explode(',', isRightObjectAllowed('voyages', $user_rights)); ?>
            <a class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'voyages') echo "active"; ?>" href="?page=voyages">Voyages</a>
        <?php endif; ?>
        <?php if (isRightObjectAllowed('affectationVehicules', $user_rights) != false): $rights_affectation = explode(',', isRightObjectAllowed('affectationVehicules', $user_rights)); ?>
            <a class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'affectationVehicules') echo "active"; ?>" href="?page=affectationVehicules">Affectation</a>
        <?php endif; ?>
        <?php $rights_maintenance = array();
        if (isRightObjectAllowed('maintenances', $user_rights) != false): $rights_maintenance = explode(',', isRightObjectAllowed('maintenances', $user_rights)); ?>
            <a class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'maintenances') echo "active"; ?>" href="?page=maintenances">Maintenance</a>
        <?php endif; ?>
        <?php $rights_user=array();
        if (isRightObjectAllowed('users', $user_rights) != false): $rights_user = explode(',', isRightObjectAllowed('users', $user_rights)); ?>
            <a class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'users') echo "active"; ?>" href="?page=users">Utilisateurs</a>
        <?php endif; ?>
        <?php $rights_config=array();
        if (isRightObjectAllowed('config', $user_rights) != false): $rights_config = explode(',', isRightObjectAllowed('config', $user_rights)); ?>
            <a class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'config') echo "active"; ?>" href="?page=configuration">Configuration</a>
        <?php endif; ?>
        <?php $rights_report=array();
        if (isRightObjectAllowed('report', $user_rights) != false): $rights_report = explode(',', isRightObjectAllowed('report', $user_rights)); ?>
            <a class="nav-link <?php if (isset($_GET['page']) && $_GET['page'] == 'reports') echo "active"; ?>" href="?page=reports">Rapports</a>
        <?php endif; ?>
        </div><!-- .lt-navbar-links -->
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="lt-navbar-user">
            <?php echo h($_SESSION['usr-con']['name_user'] != "" ? strtoupper($_SESSION['usr-con']['name_user']) : ""); ?>
        </span>
        <?php
        $regionRepo = new RegionRepository($con);
        $regionIds = explode(',', $_SESSION['usr-con']['users_region']);
        $selectedRegions = $_SESSION['usr-con']['region-sel'] ?? [];
        $entiteRepo = new EntiteRepository($con);
        $userEntityIds = $_SESSION['usr-con']['users-entite'] ?? [];
        $selectedEntities = $_SESSION['usr-con']['entite-sel'] ?? [];
        ?>
        <div class="dropdown context-dropdown">
            <button class="btn context-dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Filtrer par région">
                <i class="fa fa-map-marker-alt"></i>
                Régions <span class="badge context-badge" id="ctx-region-count"><?php echo count($selectedRegions); ?></span>
            </button>
            <div class="dropdown-menu context-dropdown-menu">
                <div class="context-dropdown-actions">
                    <a href="#" class="select-all-link" data-context="region">Tout sélectionner</a>
                    <span>&middot;</span>
                    <a href="#" class="deselect-all-link" data-context="region">Tout désélectionner</a>
                </div>
                <div class="dropdown-divider"></div>
                <?php foreach ($regionIds as $rid):
                    $r = $regionRepo->findById((int)$rid);
                    if ($r): ?>
                <label class="dropdown-item context-check-item">
                    <input class="form-check-input context-cb" type="checkbox" value="<?php echo $r['id_region']; ?>" data-context="region"
                        <?php echo in_array((int)$r['id_region'], $selectedRegions) ? 'checked' : ''; ?>>
                    <?php echo h($r['nom_region']); ?>
                </label>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <div class="dropdown context-dropdown">
            <button class="btn context-dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="Filtrer par entité">
                <i class="fa fa-building"></i>
                Entités <span class="badge context-badge" id="ctx-entite-count"><?php echo count($selectedEntities); ?></span>
            </button>
            <div class="dropdown-menu context-dropdown-menu">
                <div class="context-dropdown-actions">
                    <a href="#" class="select-all-link" data-context="entite">Tout sélectionner</a>
                    <span>&middot;</span>
                    <a href="#" class="deselect-all-link" data-context="entite">Tout désélectionner</a>
                </div>
                <div class="dropdown-divider"></div>
                <?php foreach ($userEntityIds as $eid):
                    $e = $entiteRepo->findById((int)$eid);
                    if ($e): ?>
                <label class="dropdown-item context-check-item">
                    <input class="form-check-input context-cb" type="checkbox" value="<?php echo $e['id_entite']; ?>" data-context="entite"
                        <?php echo in_array((int)$e['id_entite'], $selectedEntities) ? 'checked' : ''; ?>>
                    <?php echo h($e['nom_entite']); ?>
                </label>
                <?php endif; endforeach; ?>
            </div>
        </div>
        <a class="btn-logout" href="?logout" title="Déconnexion"><i class="fa fa-power-off"></i></a>
    </div>
</div>
<div class="lt-layout">
    <?php if (!isset($_GET['page']) || ($_GET['page'] !== 'configuration' && $_GET['page'] !== 'reports' && $_GET['page'] !== 'reporting')): ?>
    <div class="lt-sidebar">
        <div class="lt-sidebar-title">Navigation</div>
        <?php if ((isset($_GET['page']) && $_GET['page'] == 'vehicules') || !isset($_GET['page']) && in_array('view', $rights_vehicule)) : ?>
            <?php if (in_array('save', $rights_vehicule)): ?>
                <a class="lt-sidebar-link new-item" href="#" onclick="openModalVehicule()"><i class="fa fa-plus-circle"></i> Nouveau véhicule</a>
            <?php endif; ?>
            <?php if (isset($_GET['subpage'])) : ?>
                <a class="lt-sidebar-link" href="?page=<?php echo h($_GET['page'] != '' ? $_GET['page'] : 'vehicules'); ?>"><i class="fa fa-list"></i> Liste des véhicules</a>
            <?php endif; ?>
            <?php if (in_array('save', $rights_vehicule)): ?>
                <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeMarquesVehicules') echo 'active'; ?>" href="?page=<?php echo h(isset($_GET['page']) && $_GET['page'] != '' ? $_GET['page'] : 'vehicules'); ?>&subpage=listeMarquesVehicules"><i class="fa fa-tag"></i> Marques de véhicule</a>
                <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeModelesVehicules') echo 'active'; ?>" href="?page=<?php echo h(isset($_GET['page']) && $_GET['page'] != '' ? $_GET['page'] : 'vehicules'); ?>&subpage=listeModelesVehicules"><i class="fa fa-cubes"></i> Modèles de véhicule</a>
                <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeMarquesVehicules') : ?>
                    <div class="lt-sidebar-divider"></div>
                    <a class="lt-sidebar-link new-item" href="#" onclick="openModalMarque()"><i class="fa fa-plus-circle"></i> Nouvelle marque</a>
                <?php elseif (isset($_GET['subpage']) &&  $_GET['subpage'] == 'listeModelesVehicules') : ?>
                    <div class="lt-sidebar-divider"></div>
                    <a class="lt-sidebar-link new-item" href="#" onclick="openModalModele()"><i class="fa fa-plus-circle"></i> Nouveau modèle</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php elseif (isset($_GET['page']) && $_GET['page'] == 'affectationVehicules' && in_array('view', $rights_affectation)): ?>
            <a class="lt-sidebar-link <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeAffectationsVehicules') echo 'active'; ?>" href="?page=affectationVehicules"><i class="fa fa-list"></i> Liste des affectations</a>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeChauffeurs') echo 'active'; ?>" href="?page=affectationVehicules&subpage=listeChauffeurs"><i class="fa fa-users"></i> Chauffeurs</a>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeConvoyeurs') echo 'active'; ?>" href="?page=affectationVehicules&subpage=listeConvoyeurs"><i class="fa fa-truck-loading"></i> Convoyeurs</a>
            <?php if (in_array('save', $rights_affectation)): ?>
                <div class="lt-sidebar-divider"></div>
                <a class="lt-sidebar-link new-item" href="?page=affectationVehicules&subpage=listeChauffeurs&action=new"><i class="fa fa-user-plus"></i> Nouveau chauffeur</a>
                <a class="lt-sidebar-link new-item" href="?page=affectationVehicules&action=new"><i class="fa fa-plus-circle"></i> Nouvelle affectation</a>
            <?php endif; ?>
        <?php elseif (isset($_GET['page']) && $_GET['page'] == 'voyages' && in_array('view', $rights_voyage)): ?>
            <div class="lt-sidebar-title">Listes</div>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeTrajets') echo 'active'; ?>" href="?page=voyages&subpage=listeTrajets"><i class="fa fa-map-marker-alt"></i> Trajets</a>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeTypesChargement') echo 'active'; ?>" href="?page=voyages&subpage=listeTypesChargement"><i class="fa fa-boxes"></i> Types de chargement</a>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeObjectifsVoyages') echo 'active'; ?>" href="?page=voyages&subpage=listeObjectifsVoyages"><i class="fa fa-bullseye"></i> Objectifs</a>
            <?php if (in_array('save', $rights_voyage)): ?>
                <?php if (in_array('savetrajet', $rights_voyage)): ?>
                    <a class="lt-sidebar-link new-item" href="?page=voyages&subpage=listeTrajets&action=new"><i class="fa fa-plus-circle"></i> Nouveau trajet</a>
                <?php endif; ?>
                <a class="lt-sidebar-link new-item" href="?page=voyages&action=new"><i class="fa fa-plus-circle"></i> Nouveau voyage</a>
                <a class="lt-sidebar-link new-item" href="?page=voyages&subpage=listeTypesChargement&action=new"><i class="fa fa-boxes"></i> Nouveau type chargement</a>
                <a class="lt-sidebar-link new-item" href="?page=voyages&subpage=listeObjectifsVoyages&action=new"><i class="fa fa-bullseye"></i> Nouvel objectif</a>
            <?php endif; ?>
            <?php if (in_array('report', $rights_voyage)): ?>
                <div class="lt-sidebar-divider"></div>
                <div class="lt-sidebar-title">Rapports</div>
                <a class="lt-sidebar-link <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeVoyages') echo 'active'; ?>" href="?page=voyages"><i class="fa fa-list"></i> Historique des voyages</a>
                <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'dashboard') echo 'active'; ?>" href="?page=voyages&subpage=dashboard"><i class="fa fa-chart-bar"></i> Tableau de bord</a>
                <a class="lt-sidebar-link" href="?page=voyages&subpage=evaluationVoyages"><i class="fa fa-chart-line"></i> Evaluation des voyages</a>
                <a class="lt-sidebar-link" href="?page=voyages&subpage=listeVoyagesVehicules"><i class="fa fa-truck-moving"></i> Voyages / Véhicules</a>
                <a class="lt-sidebar-link" href="?page=voyages&subpage=listeVoyagesPeriodes"><i class="fa fa-calendar-alt"></i> Voyages / Périodes</a>
            <?php endif; ?>
        <?php elseif (isset($_GET['page']) && $_GET['page'] == 'maintenances' && in_array('view', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'dashboard') echo 'active'; ?>" href="?page=maintenances&subpage=dashboard"><i class="fa fa-chart-bar"></i> Tableau de bord</a>
            <?php if (in_array('viewReleveKms', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'releveKms') echo 'active'; ?>" href="?page=maintenances&subpage=releveKms"><i class="fa fa-tachometer-alt"></i> Relevés kilométrage</a>
            <?php endif; ?>
            <?php if (in_array('viewVidange', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'suiviVidanges') echo 'active'; ?>" href="?page=maintenances&subpage=suiviVidanges"><i class="fa fa-oil-can"></i> Suivi vidanges</a>
            <?php endif; ?>
            <?php if (in_array('viewPrestataire', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'prestataire') echo 'active'; ?>" href="?page=maintenances&subpage=prestataire"><i class="fa fa-store"></i> Prestataires</a>
            <?php endif; ?>
            <?php if (in_array('viewCentreCout', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'centreCouts') echo 'active'; ?>" href="?page=maintenances&subpage=centreCouts"><i class="fa fa-euro-sign"></i> Centre de coûts</a>
            <?php endif; ?>
            <?php if (in_array('viewBonsReparation', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'suiviBonsReparation') echo 'active'; ?>" href="?page=maintenances&subpage=suiviBonsReparation"><i class="fa fa-tools"></i> Bons de réparation</a>
            <?php endif; ?>
        <?php elseif (isset($_GET['page']) && $_GET['page'] == 'users' && in_array('view', $rights_user)): ?>
            <?php if (in_array('save', $rights_user)): ?>
                <a class="lt-sidebar-link new-item" href="#" onclick="openModalUser()"><i class="fa fa-plus-circle"></i> Nouvel utilisateur</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php endif; ?>
    <div class="lt-content">
            <?php if (!isset($_GET['page']) || ($_GET['page'] == 'vehicules' || $_GET['page'] == '')) : ?>
                <?php include("vehicule.php"); ?>
                <?php if (!isset($_GET['subpage']) || ($_GET['subpage'] == 'listeVehicules' || $_GET['subpage'] == '')) : ?>
                    <div class="lt-page-title">Liste des véhicules</div>
                    <hr>
                    <?php echo getTableauVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeMarquesVehicules') : ?>
                    <div class="lt-page-title">Liste des marques de véhicules</div>
                    <hr>
                    <?php include("marqueVehicule.php");
                    echo getTableauMarqueVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeModelesVehicules') : ?>
                    <div class="lt-page-title">Liste des modèles de véhicules</div>
                    <hr>
                    <?php include("modeleVehicule.php");
                    echo getTableauModeleVehicules(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'affectationVehicules'): ?>
                <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeChauffeurs'): ?>
                    <div class="lt-page-title">Liste des chauffeurs de véhicules</div>
                    <hr>
                    <?php include("chauffeur.php");
                    echo getTableauChauffeurs(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeConvoyeurs'): ?>
                    <div class="lt-page-title">Liste des convoyeurs de véhicules</div>
                    <hr>
                    <?php include("convoyeur.php");
                    echo getTableauConvoyeurs(); ?>
                <?php elseif (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeAffectationsVehicules'): ?>
                    <div class="lt-page-title">Liste des affectations de véhicules</div>
                    <hr>
                    <?php include("affectationVehicule.php");
                    echo getTableauAffectations(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'voyages') : ?>
                <?php set_time_limit(120);
                include("voyage.php"); echo getDashboardCardsVoyages(); ?>
                <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'dashboard'): ?>
                    <div class="lt-page-title">Tableau de bord voyages</div>
                    <hr>
                    <?php echo getDashboardChartsVoyages(); ?>
                    <?php echo getAnomaliesVoyages(); ?>
                    <?php echo getScoreActivite(); ?>
                    <?php echo getProjectionMois(); ?>
                <?php elseif (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeVoyages'): ?>
                    <div class="lt-page-title">Historique des voyages</div>
                    <hr>
                    <?php echo getTableauVoyages(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeTrajets'): ?>
                    <div class="lt-page-title">Liste des trajets de voyage</div>
                    <hr>
                    <?php include("trajet.php");
                    echo getTableauTrajets(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeTypesChargement'): ?>
                    <div class="lt-page-title">Types de chargement</div>
                    <hr>
                    <?php include("type_chargement.php");
                    echo getTableauTypesChargement(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeObjectifsVoyages'): ?>
                    <div class="lt-page-title">Liste des Objectifs de voyage</div>
                    <hr>
                    <?php include("objectif.php");
                    echo getTableauObjectifs(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'evaluationVoyages'): ?>
                    <div class="lt-page-title">Evaluation des voyages</div>
                    <hr>
                    <?php echo getTableauEvaluationVoyages(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesVehicules'): ?>
                    <div class="lt-page-title">Liste des Voyages/Véhicules/Trajets</div>
                    <hr>
                    <?php echo getTableauVoyagesVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesPeriodes'): ?>
                    <div class="lt-page-title">Liste des Voyages/Périodes/Trajets</div>
                    <hr>
                    <?php echo getTableauVoyagesPeriodes(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'maintenances') :  ?>
                <?php include("maintenance.php"); ?>
                <?php echo getDashboardCards(); ?>
                <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'dashboard'): ?>
                    <div class="lt-page-title">Tableau de bord maintenance</div>
                    <hr>
                    <?php echo getDashboardCharts(); ?>
                    <div id="health-scores-section"><div class="text-center p-4"><i class="fa fa-spinner fa-spin"></i> Chargement scores de santé...</div></div>
                    <div id="upcoming-vidanges-section"><div class="text-center p-4"><i class="fa fa-spinner fa-spin"></i> Chargement planification...</div></div>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'releveKms'): ?>
                    <div class="lt-page-title">Relevés de kilométrages</div>
                    <hr>
                    <?php echo getTableauReleveKMS(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'suiviVidanges'):  ?>
                    <div class="lt-page-title">Suivi des vidanges</div>
                    <hr>
                    <?php echo getTableauVidange(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'prestataire'):  ?>
                    <div class="lt-page-title">Liste des prestataires</div>
                    <hr>
                    <?php echo getTableauPrestataire(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'centreCouts'):  ?>
                    <div class="lt-page-title">Liste des Centres de coûts</div>
                    <hr>
                    <?php echo getTableauCentreCout(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'suiviBonsReparation'):  ?>
                    <div class="lt-page-title">Suivi des bons de réparation</div>
                    <hr>
                    <?php echo getTableauBonsReparation(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'configuration') :  ?>
                <?php if (!in_array('view', $rights_config)): ?>
                    <div class="lt-page-title">Accès non autorisé</div>
                    <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
                <?php else: ?>
                <?php include("config.php"); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'users') :  ?>
                <?php
                include("_users.php");
                if (in_array('view', $rights_user)): ?>
                    <div class="lt-page-title">Gestion des utilisateurs</div>
                    <hr>
                    <?php echo getTableauUsers(); ?>
                <?php else: ?>
                    <div class="lt-page-title">Accès non autorisé</div>
                    <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'reports') :  ?>
                <?php if (!in_array('view', $rights_report)): ?>
                    <div class="lt-page-title">Accès non autorisé</div>
                    <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
                <?php else: ?>
                <?php include("reporting.php"); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'reporting') :  ?>
                <?php if (!in_array('view', $rights_report)): ?>
                    <div class="lt-page-title">Accès non autorisé</div>
                    <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
                <?php else: ?>
                <?php include("reporting.php"); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'import') :  ?>
                <?php include("import.php"); ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'userRegistration') :  ?>
                <?php include("userRegistration.php"); ?>
            <?php endif; ?>
    </div>
</div>
<?php if (!$renderPartial): ?>
<script>
    function cbDropdown(column) {
        return $('<ul>', {
            'class': 'cb-dropdown'
        }).appendTo($('<div>', {
            'class': 'cb-dropdown-wrap'
        }).appendTo(column));
    }
    function initDataTable() {
        window.table = $('table:not(".no-datatable")').DataTable({
        columnDefs: [{ targets: 0, orderable: false }],
        initComplete: function() {
            this.api().columns().every(function() {
                var column = this;
                // Skip # column — no filter, no sort
                if ($(column.header()).text().trim() === '#') return;
                var select = $('<select class="mymsel" multiple="multiple"><option value=""></option></select>')
                    .appendTo($(column.header()));
                select[0].dtColumn = column;
                if ($(column.header()).children('.dt-column-title').html() == '') select.remove()

                column.data().unique().sort().each(function(d, j) {
                    select.append('<option value="' + $("<div>" + d + "</div>").text() + '">' + $("<div>" + d + "</div>").text() + '</option>')
                });
            });
            initTomSelect('.mymsel', {
                maxItems: null,
                placeholder: 'Filtrer...',
                dropdownParent: 'body',
                onChange: function(values) {
                    var col = this.input && this.input.dtColumn;
                    if (!col) return;
                    var escaped = values.map(function(v) {
                        return $.fn.dataTable.util.escapeRegex(v);
                    }).join('|');
                    col.search(escaped ? '^(' + escaped + ')$' : '', true, false).draw();
                }
            });
            this.api().columns.adjust();

        },
        <?php if (!isset($_GET['subpage'])) :
            echo "layout: {
        topStart: {
            buttons: [{
            extend: 'excelHtml5',
            className:'btn btn-primary',
            text:'Exporter'
        }]}},";
        endif;
        if ((!isset($_GET['subpage']) && (isset($_GET['page']) && $_GET['page'] == 'voyages')) || (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyages')):
            echo "order: [
                [2, 'asc']
            ],scrollCollapse: true,scrollY: 300,fixedColumns: {
        start: 1,
        end: 0
    },paging: false
            ";
        elseif ((isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesVehicules') || (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesPeriodes')) :
            echo "scrollX:true, pageLength:-1,layout: {
        topStart: {
            buttons: [{
            extend: 'excelHtml5',
            className:'btn btn-primary',
            text:'Exporter',
            customize: function (xlsx) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        var freezePanes ='<sheetViews><sheetView tabSelected=\"1\" workbookViewId=\"0\"><pane xSplit=\"4\" ySplit=\"2\" topLeftCell=\"E3\"  activePane=\"topRight\" state=\"frozen\"/></sheetView></sheetViews>';
                        var current = sheet.children[0].innerHTML;
                        current = freezePanes + current;
                        sheet.children[0].innerHTML = current;
                        $('row', sheet).each(function () {
                        col=1
                        $(this).children().each((e,el)=>{
                        if(col>4
                        ) if($(el).children().html()=='1') $(el).attr('s', '40'); else $(el).attr('s','25')
                        col++
            })
            })
            }
            }]
        }
    },fixedColumns: {
        start: 4,
        end: 0
    },paging: false,
    scrollCollapse: true,scrollY: 300";
        elseif ((isset($_GET['subpage']) && $_GET['subpage'] == 'evaluationVoyages')):
            echo "order: [
                [0, 'asc']
            ],layout: {
        topStart: {
            buttons: [{
            extend: 'excelHtml5',
            className:'btn btn-primary',
            text:'Exporter'
            }]
    }},paging: false,
        scrollCollapse: true,
        scrollY: 300";
        elseif ((isset($_GET['subpage']) && $_GET['subpage'] == 'suiviVidanges')):
            echo "order: [
                [0, 'asc']
            ],layout: {
        topStart: {
            buttons: [{
            extend: 'excelHtml5',
            className:'btn btn-primary',
            text:'Exporter',
            title:'Mon_fichier.xlsx'
            }]
        }
        },paging: false,
        scrollCollapse: true,
        scrollY: 300";
        endif; ?>
        });
    }
    initDataTable();

    function createCellPos(n) {
        var ordA = 'A'.charCodeAt(0);
        var ordZ = 'Z'.charCodeAt(0);
        var len = ordZ - ordA + 1;
        var s = "";

        while (n >= 0) {
            s = String.fromCharCode(n % len + ordA) + s;
            n = Math.floor(n / len) - 1;
        }

        return s;
    }

    // Context checkbox dropdowns — read / update / sync
    function updateContextBadge(type) {
        var count = $('.context-cb[data-context="' + type + '"]:checked').length;
        var $badge = $('#ctx-' + type + '-count');
        $badge.text(count);
        // Dim the dropdown toggle when nothing is selected
        var $toggle = $badge.closest('.context-dropdown').find('.context-dropdown-toggle');
        $toggle.toggleClass('context-empty', count === 0);
    }

    function getCheckedContext(type) {
        return $('.context-cb[data-context="' + type + '"]:checked').map(function() {
            return $(this).val();
        }).get();
    }

    // Prevent dropdown from closing when clicking inside it
    $(document).on('click', '.context-dropdown-menu .context-check-item, .context-dropdown-menu .select-all-link, .context-dropdown-menu .deselect-all-link', function(e) {
        e.stopPropagation();
    });

    // Select all / deselect all links
    $(document).on('click', '.context-dropdown-menu .select-all-link', function(e) {
        e.preventDefault();
        var type = $(this).data('context');
        $('.context-cb[data-context="' + type + '"]').prop('checked', true);
        updateContextBadge(type);
        debouncedSwitchContext();
    });

    $(document).on('click', '.context-dropdown-menu .deselect-all-link', function(e) {
        e.preventDefault();
        var type = $(this).data('context');
        var $cbs = $('.context-cb[data-context="' + type + '"]');
        // Leave at least one checked — uncheck all except the first
        $cbs.prop('checked', false);
        if ($cbs.length > 0) {
            $cbs.first().prop('checked', true);
        }
        updateContextBadge(type);
        debouncedSwitchContext();
    });

    // Checkbox change — prevent deselecting the last item
    $(document).on('change', '.context-cb', function() {
        var type = $(this).data('context');
        var $checked = $('.context-cb[data-context="' + type + '"]:checked');
        if ($checked.length === 0) {
            // Re-check this one — at least one must stay selected
            $(this).prop('checked', true);
            showWarning('Au moins une ' + (type === 'region' ? 'région' : 'entité') + ' doit être sélectionnée');
            return;
        }
        updateContextBadge(type);
        debouncedSwitchContext();
    });

    var switchTimer;
    function debouncedSwitchContext() {
        clearTimeout(switchTimer);
        switchTimer = setTimeout(function() {
            var regionIds = getCheckedContext('region');
            var entiteIds = getCheckedContext('entite');
            if (regionIds.length === 0 || entiteIds.length === 0) {
                showWarning('Veuillez sélectionner au moins une région et une entité');
                return;
            }
            $.ajax({
                type: 'post',
                contentType: 'application/json',
                data: JSON.stringify({
                    nContext: 1,
                    regionIds: regionIds,
                    entiteIds: entiteIds,
                    csrf_token: window.CSRF_TOKEN
                }),
                dataType: 'json'
            }).done(function(e) {
                if (e.success) {
                    reloadPageContent();
                } else {
                    showWarning(e.error || "Erreur lors du changement de contexte");
                }
            }).fail(function() {
                showWarning('Erreur réseau lors du changement de contexte');
            });
        }, 500);
    }

    function reloadPageContent() {
        var url = window.location.href.split('?')[0];
        var params = new URLSearchParams(window.location.search);
        params.set('_partial', '1');
        $.ajax({
            type: 'get',
            url: url + '?' + params.toString(),
            dataType: 'html'
        }).done(function(html) {
            if (window.table) {
                window.table.destroy();
                window.table = null;
            }
            destroyTomSelect('.mymsel');
            $('.lt-content').html(html);
            initDataTable();
        }).fail(function() {
            location.reload();
        });
    }

    // Initialize badge counts on page load
    updateContextBadge('region');
    updateContextBadge('entite');
    <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'evaluationVoyages'): ?>
    table.destroy()
    destroyTomSelect('.mymsel')
    $('.mymsel').remove()
    $('<button class="btn btn-primary mb-3" onclick="exportTableToExcel(\'table-evaluation\')">Exporter</button>').insertBefore('#table-evaluation')
    <?php endif; ?>

    $(document).on('shown.bs.modal', '.modal', function() {
        initTomSelect($(this).find('select:not(.no-tom-select)'), {
            render: { no_results: function() { return '<div class="no-results">Aucun résultat</div>'; } }
        });
    });
</script>
<style>
    .cb-dropdown-wrap {
        max-height: 80px;
        /* At most, around 3/4 visible items. */
        position: relative;
        height: 19px;
    }

    .cb-dropdown,
    .cb-dropdown li {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .cb-dropdown {
        position: absolute;
        z-index: 9999;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background: #fff;
        border: 1px solid #888;
    }

    .active .cb-dropdown {
        background: pink;
    }

    .cb-dropdown-wrap:hover .cb-dropdown {
        height: 80px;
        overflow: auto;
        transition: 0.2s height ease-in-out;
        z-index: 99999;
    }

    .cb-dropdown li.active {
        background: #ff0;
    }

    .cb-dropdown li label {
        display: block;
        position: relative;
        cursor: pointer;
        line-height: 19px;
        /* Match height of .cb-dropdown-wrap */
    }

    .cb-dropdown li label>input {
        position: absolute;
        right: 0;
        top: 0;
        width: 16px;
    }

    .cb-dropdown li label>span {
        display: block;
        margin-left: 3px;
        margin-right: 20px;
        font-size: 0.75rem;
        font-weight: normal;
        text-align: left;
    }

    /* This fixes the vertical aligning of the sorting icon. */
    table.dataTable thead .sorting,
    table.dataTable thead .sorting_asc,
    table.dataTable thead .sorting_desc,
    table.dataTable thead .sorting_asc_disabled,
    table.dataTable thead .sorting_desc_disabled {
        background-position: 100% 10px;
    }

    .ts-wrapper {
        display: block;
    }

    /* Context checkbox dropdowns */
    .context-dropdown-toggle {
        background: transparent;
        border: 1px solid rgba(255,255,255,.15);
        color: rgba(255,255,255,.85);
        font-size: 0.78rem;
        padding: 0.25rem 0.6rem;
        border-radius: 0.35rem;
    }
    .context-dropdown-toggle:hover,
    .context-dropdown-toggle:focus,
    .context-dropdown-toggle.show {
        background: rgba(255,255,255,.1);
        color: #fff;
        border-color: rgba(255,255,255,.3);
    }
    .context-dropdown-toggle .fa {
        margin-right: 0.2rem;
        font-size: 0.7rem;
    }
    .context-badge {
        background: rgba(255,255,255,.2);
        color: #fff;
        font-size: 0.65rem;
        padding: 0.15em 0.4em;
        margin-left: 0.2rem;
    }
    .context-dropdown-toggle.context-empty {
        border-color: rgba(255,100,100,.4);
        color: rgba(255,255,255,.55);
    }
    .context-dropdown-toggle.context-empty .context-badge {
        background: rgba(255,100,100,.35);
    }

    .context-dropdown-menu {
        max-height: 260px;
        overflow-y: auto;
        min-width: 220px;
        padding: 0.25rem 0;
    }
    .context-dropdown-actions {
        padding: 0.3rem 0.75rem;
        font-size: 0.75rem;
    }
    .context-dropdown-actions a {
        color: #5D54A4;
        text-decoration: none;
    }
    .context-dropdown-actions a:hover {
        text-decoration: underline;
    }

    .context-check-item {
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .context-check-item:hover {
        background: #f5f3ff;
    }
    .context-check-item .form-check-input {
        margin: 0;
        flex-shrink: 0;
    }
</style>
<?php endif; ?>
