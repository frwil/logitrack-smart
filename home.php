<?php /* POST /switchRegion handled by AuthController — see controllers/router.php */
if(!isset($user_rights)) $user_rights = [];
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
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="lt-navbar-user">
            <?php echo h($_SESSION['usr-con']['name_user'] != "" ? strtoupper($_SESSION['usr-con']['name_user']) : ""); ?>
        </span>
        <select id="context-region" multiple class="context-select" style="width:180px">
            <?php
            $regionRepo = new RegionRepository($con);
            $regionIds = explode(',', $_SESSION['usr-con']['users_region']);
            $selectedRegions = $_SESSION['usr-con']['region-sel'] ?? [];
            foreach ($regionIds as $rid):
                $r = $regionRepo->findById((int)$rid);
                if ($r):
            ?>
                <option value="<?php echo $r['id_region']; ?>" <?php echo in_array((int)$r['id_region'], $selectedRegions) ? 'selected' : ''; ?>>
                    <?php echo h($r['nom_region']); ?>
                </option>
            <?php endif; endforeach; ?>
        </select>
        <select id="context-entite" multiple class="context-select" style="width:180px">
            <?php
            $entiteRepo = new EntiteRepository($con);
            $userEntityIds = $_SESSION['usr-con']['users-entite'] ?? [];
            $selectedEntities = $_SESSION['usr-con']['entite-sel'] ?? [];
            foreach ($userEntityIds as $eid):
                $e = $entiteRepo->findById((int)$eid);
                if ($e):
            ?>
                <option value="<?php echo $e['id_entite']; ?>" <?php echo in_array((int)$e['id_entite'], $selectedEntities) ? 'selected' : ''; ?>>
                    <?php echo h($e['nom_entite']); ?>
                </option>
            <?php endif; endforeach; ?>
        </select>
        <a class="btn-logout" href="?logout" title="Déconnexion"><i class="fa fa-power-off"></i></a>
    </div>
</div>
<div class="lt-layout">
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
            <?php if (in_array('save', $rights_affectation)): ?>
                <a class="lt-sidebar-link new-item" href="?page=affectationVehicules&subpage=listeChauffeurs&action=new"><i class="fa fa-user-plus"></i> Nouveau chauffeur</a>
                <a class="lt-sidebar-link new-item" href="?page=affectationVehicules&action=new"><i class="fa fa-plus-circle"></i> Nouvelle affectation</a>
            <?php endif; ?>
        <?php elseif (isset($_GET['page']) && $_GET['page'] == 'voyages' && in_array('view', $rights_voyage)): ?>
            <?php if (in_array('save', $rights_voyage)): ?>
                <?php if (in_array('savetrajet', $rights_voyage)): ?>
                    <a class="lt-sidebar-link new-item" href="?page=voyages&subpage=listeTrajets&action=new"><i class="fa fa-map-marker-alt"></i> Nouveau trajet</a>
                <?php endif; ?>
                <a class="lt-sidebar-link new-item" href="?page=voyages&action=new"><i class="fa fa-plus-circle"></i> Nouveau voyage</a>
                <a class="lt-sidebar-link new-item" href="?page=voyages&subpage=listeObjectifsVoyages&action=new"><i class="fa fa-bullseye"></i> Objectifs de voyages</a>
            <?php endif; ?>
            <?php if (in_array('report', $rights_voyage)): ?>
                <div class="lt-sidebar-divider"></div>
                <div class="lt-sidebar-title">Rapports</div>
                <a class="lt-sidebar-link" href="?page=voyages&subpage=evaluationVoyages"><i class="fa fa-chart-bar"></i> Evaluation des voyages</a>
                <a class="lt-sidebar-link" href="?page=voyages&subpage=listeVoyagesVehicules"><i class="fa fa-truck-moving"></i> Voyages / Véhicules</a>
                <a class="lt-sidebar-link" href="?page=voyages&subpage=listeVoyagesPeriodes"><i class="fa fa-calendar-alt"></i> Voyages / Périodes</a>
            <?php endif; ?>
        <?php elseif (isset($_GET['page']) && $_GET['page'] == 'maintenances' && in_array('view', $rights_maintenance)): ?>
            <a class="lt-sidebar-link <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'releveKms') echo 'active'; ?>" href="?page=maintenances&subpage=releveKms"><i class="fa fa-tachometer-alt"></i> Relevés kilométrage</a>
            <a class="lt-sidebar-link <?php if ($_GET['subpage'] == 'suiviVidanges') echo 'active'; ?>" href="?page=maintenances&subpage=suiviVidanges"><i class="fa fa-oil-can"></i> Suivi vidanges</a>
            <a class="lt-sidebar-link <?php if ($_GET['subpage'] == 'centreCouts') echo 'active'; ?>" href="?page=maintenances&subpage=centreCouts"><i class="fa fa-euro-sign"></i> Centre de coûts</a>
            <a class="lt-sidebar-link <?php if ($_GET['subpage'] == 'suiviBonsReparation') echo 'active'; ?>" href="?page=maintenances&subpage=suiviBonsReparation"><i class="fa fa-tools"></i> Bons de réparation</a>
        <?php endif; ?>
    </div>
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
                <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeVoyages'): ?>
                    <div class="lt-page-title">Liste des voyages de véhicules</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauVoyages(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeTrajets'): ?>
                    <div class="lt-page-title">Liste des trajets de voyage</div>
                    <hr>
                    <?php include("trajet.php");
                    echo getTableauTrajets(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeObjectifsVoyages'): ?>
                    <div class="lt-page-title">Liste des Objectifs de voyage</div>
                    <hr>
                    <?php include("objectif.php");
                    echo getTableauObjectifs(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'evaluationVoyages'): ?>
                    <div class="lt-page-title">Evaluation des voyages</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauEvaluationVoyages(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesVehicules'): ?>
                    <div class="lt-page-title">Liste des Voyages/Véhicules/Trajets</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauVoyagesVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesPeriodes'): ?>
                    <div class="lt-page-title">Liste des Voyages/Périodes/Trajets</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauVoyagesPeriodes(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'maintenances') :  ?>
                <?php include("maintenance.php"); ?>
                <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'releveKms'): ?>
                    <div class="lt-page-title">Relevés de kilométrages</div>
                    <hr>
                    <?php echo getTableauReleveKMS(); ?>
                <?php elseif ($_GET['subpage'] == 'suiviVidanges'):  ?>
                    <div class="lt-page-title">Suivi des vidanges</div>
                    <hr>
                    <?php echo getTableauVidange(); ?>
                <?php elseif ($_GET['subpage'] == 'prestataire'):  ?>
                    <div class="lt-page-title">Liste des prestataires</div>
                    <hr>
                    <?php echo getTableauPrestataire(); ?>
                <?php elseif ($_GET['subpage'] == 'centreCouts'):  ?>
                    <div class="lt-page-title">Liste des Centres de coûts</div>
                    <hr>
                    <?php echo getTableauCentreCout(); ?>
                <?php elseif ($_GET['subpage'] == 'suiviBonsReparation'):  ?>
                    <div class="lt-page-title">Suivi des bons de réparation</div>
                    <hr>
                    <?php echo getTableauBonsReparation(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'configuration') :  ?>
                <?php include("config.php"); ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'reports') :  ?>
                <?php include("reporting.php"); ?>
            <?php endif; ?>
    </div>
</div>
<script>
    function cbDropdown(column) {
        return $('<ul>', {
            'class': 'cb-dropdown'
        }).appendTo($('<div>', {
            'class': 'cb-dropdown-wrap'
        }).appendTo(column));
    }
    table = $('table:not(".no-datatable")').DataTable({
        columnDefs: [{ targets: 0, searchable: false, orderable: false }],
        initComplete: function() {
            this.api().columns().every(function() {
                var column = this;
                // Skip # column — no filter, no sort
                if (column.index() === 0 || $(column.header()).text().trim() === '#') return;
                var select = $('<select class="mymsel" multiple="multiple"><option value=""></option></select>')
                    .appendTo($(column.header()))
                    .on('change', function() {
                        var vals = $('option:selected', this).map(function(index, element) {
                            return $.fn.dataTable.util.escapeRegex($(element).val());
                        }).toArray().join('|');

                        column
                            .search(vals.length > 0 ? '^(' + vals + ')$' : '', true, false)
                            .draw();
                    });
                if ($(column.header()).children('.dt-column-title').html() == '') select.remove()

                column.data().unique().sort().each(function(d, j) {
                    select.append('<option value="' + $("<div>" + d + "</div>").text() + '">' + $("<div>" + d + "</div>").text() + '</option>')
                });
            });
            initTomSelect('.mymsel', { maxItems: null, placeholder: 'Filtrer...' });

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
    })

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

    var contextRegionSelect, contextEntiteSelect;
    function makeContextSelect(selector, onChange) {
        if (!$(selector).length || $(selector)[0].tomselect) return;
        var ts = new TomSelect(selector, {
            maxItems: null,
            plugins: ['remove_button'],
            render: {
                no_results: function() { return '<div class="no-results">Aucune</div>'; },
                dropdown: function() {
                    return '<div class="ts-dropdown-content"><div class="ts-select-all"><a href="#" class="select-all-link">Tout sélectionner</a> &middot; <a href="#" class="deselect-all-link">Tout désélectionner</a></div></div>';
                }
            },
            onChange: onChange
        });
        ts.on('dropdown_open', function() {
            var $dd = $(ts.dropdown_content);
            $dd.find('.select-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                ts.setValue(Object.keys(ts.options).map(function(k) { return ts.options[k].value; }));
            });
            $dd.find('.deselect-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                ts.clear();
            });
        });
        return ts;
    }
    function initContextSelects() {
        contextRegionSelect = makeContextSelect('#context-region', debouncedSwitchContext);
        contextEntiteSelect = makeContextSelect('#context-entite', debouncedSwitchContext);
    }
    var switchTimer;
    function debouncedSwitchContext() {
        clearTimeout(switchTimer);
        switchTimer = setTimeout(function() {
            var regionIds = contextRegionSelect.getValue();
            var entiteIds = contextEntiteSelect.getValue();
            if (regionIds.length === 0 || entiteIds.length === 0) return;
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
                if (e.success) { location.reload(); }
                else { showWarning(e.error||"Erreur lors du changement de contexte"); }
            });
        }, 500);
    }
    initContextSelects();
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
</style>