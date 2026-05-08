<?php if (isset($_POST['nSess'])):
    $q = db_select($con, "select * from region where sha1(concat(id_region,nom_region))=?", [$_POST['nSess']]);
    $region = explode(',', $_SESSION['usr-con']['users_region']);
    while ($r = mysqli_fetch_array($q)) $reg = $r;
    if (isset($reg)):
        if (in_array($reg[0], $region)):
            $_SESSION['usr-con']['region-sel'] = $reg[0];
            $_SESSION['usr-con']['region-sel-name'] = $reg[1];
            die("changeRegion%%%%%%1");
        else :
            die("changeRegion%%%%%%2");
        endif;
    else :
        die("changeRegion%%%%%%0");
    endif;
endif;
?>
<script>
    function exportTableToExcel(tableId, filename = '') {
        // Récupérer le tableau HTML
        const table = document.getElementById(tableId);

        // Créer une feuille de calcul à partir du tableau
        const worksheet = XLSX.utils.table_to_sheet(table);

        // Créer un nouveau classeur
        const workbook = XLSX.utils.book_new();

        // Ajouter la feuille de calcul au classeur
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Feuille1');

        // Générer le fichier Excel
        const excelBuffer = XLSX.write(workbook, {
            bookType: 'xlsx',
            type: 'array'
        });

        // Créer un Blob et déclencher le téléchargement
        const blob = new Blob([excelBuffer], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        });

        // Nom du fichier
        if (!filename) {
            const date = new Date();
            filename = `export_${date.toISOString().split('T')[0]}_${date.getHours()}${date.getMinutes()}.xlsx`;
        }

        // Créer un lien et simuler le clic
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();

        // Nettoyer
        URL.revokeObjectURL(link.href);
    }
    
</script>
<div class="container-fluid d-flex">
    <ul class="nav nav-underline bg-primary navbar-expand-lg" style="padding:15px;width:100%">
        <?php //echo password_hash('Fidele24',PASSWORD_DEFAULT); 
        $rights_vehicule = array();
        if (isRightObjectAllowed('vehicules', $user_rights) != false): $rights_vehicule = explode(',', isRightObjectAllowed('vehicules', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link <?php if (!isset($_GET['page']) || $_GET['page'] == 'vehicules') echo "active"; ?> text-white" aria-current="page" href="?page=vehicules">Véhicules</a>
            </li>
        <?php endif; ?>
        <?php $rights_affectation = array();
        if (isRightObjectAllowed('voyages', $user_rights) != false): $rights_voyage = explode(',', isRightObjectAllowed('voyages', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php if (isset($_GET['page']) && $_GET['page'] == 'voyages') echo "active"; ?>" href="?page=voyages">Voyages</a>
            </li>
        <?php endif; ?>
        <?php if (isRightObjectAllowed('affectationVehicules', $user_rights) != false): $rights_affectation = explode(',', isRightObjectAllowed('affectationVehicules', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php if (isset($_GET['page']) && $_GET['page'] == 'affectationVehicules') echo "active"; ?>" href="?page=affectationVehicules">Affectation de véhicules</a>
            </li>
        <?php endif; ?>
        <?php $rights_maintenance = array();
        if (isRightObjectAllowed('maintenances', $user_rights) != false): $rights_maintenance = explode(',', isRightObjectAllowed('maintenances', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php if (isset($_GET['page']) && $_GET['page'] == 'maintenances') echo "active"; ?>" href="?page=maintenances">Gestion Maintenances</a>
            </li>
        <?php endif; ?>
        <?php $rights_user=array();
        if (isRightObjectAllowed('users', $user_rights) != false): $rights_user = explode(',', isRightObjectAllowed('users', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php if (isset($_GET['page']) && $_GET['page'] == 'users') echo "active"; ?>" href="?page=users">Utilisateurs</a>
            </li>
        <?php endif; ?>
        <?php $rights_config=array();
        if (isRightObjectAllowed('config', $user_rights) != false): $rights_config = explode(',', isRightObjectAllowed('config', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php if (isset($_GET['page']) && $_GET['page'] == 'config') echo "active"; ?>" href="?page=configuration">Configuration</a>
            </li>
        <?php endif; ?>
        <?php $rights_report=array();
        if (isRightObjectAllowed('report', $user_rights) != false): $rights_report = explode(',', isRightObjectAllowed('report', $user_rights)); ?>
            <li class="nav-item">
                <a class="nav-link text-white <?php if (isset($_GET['page']) && $_GET['page'] == 'reports') echo "active"; ?>" href="?page=reports">Rapports</a>
            </li>
        <?php endif; ?>
    </ul>
    <div class="d-flex bg-primary" style="padding:10px">
        <ul class="nav nav-underline bg-primary navbar-expand-lg text-center" style="padding:5px;width:100%;font-size:1.1em">
            <li class="nav-item">
                <a class="nav-link text-white">
                    <?php echo h($_SESSION['usr-con']['name_user'] != "" ? strtoupper($_SESSION['usr-con']['name_user']) : ""); ?>
                </a>
            </li>
        </ul>
        <a class="dropdown-toggle text-white" style="padding:15px;font-weight:bold" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"><?php echo h($_SESSION['usr-con']['region-sel-name']); ?></a>
        <ul class="dropdown-menu">
            <?php $uright = explode(',', $_SESSION['usr-con']['users_region']);
            for ($i = 0; $i < count($uright); $i++):
                if ($uright[$i] != $_SESSION['usr-con']['region-sel']): ?>
                    <?php $q = db_select($con, "select * from region where id_region=?", [(int)$uright[$i]]);
                    while ($r = mysqli_fetch_array($q)): ?>
                        <li><a class="dropdown-item" href="#" onclick="changeSessionRegion('<?php echo sha1($r[0] . $r[1]); ?>')"><?php echo $r[1]; ?></a></li>
                    <?php endwhile; ?>
            <?php endif;
            endfor; ?>
        </ul>
        <a class="btn btn-link text-white" style="padding:15px" href="?logout"><i class="fa fa-power-off"></i></a>
    </div>
</div>
<div class="container-fluid" style="margin-top:30px">
    <div class="row">
        <div class="col-2">
            <ul class="nav nav-tabs flex-column">
                <?php if ((isset($_GET['page']) && $_GET['page'] == 'vehicules') || !isset($_GET['page']) && in_array('view', $rights_vehicule)) : ?>
                    <?php if (in_array('save', $rights_vehicule)): ?>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=vehicules" title="Ajouter un nouveau véhicule" data-bs-toggle="modal" data-bs-target="#modal-new-vehicule">Nouveau véhicule</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_GET['subpage'])) : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?page=<?php echo h($_GET['page'] != '' ? $_GET['page'] : 'vehicules'); ?>" title="Afficher la liste des véhicules">Liste des véhicules</a>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array('save', $rights_vehicule)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeMarquesVehicules') echo 'active'; ?>" href="?page=<?php echo h(isset($_GET['page']) && $_GET['page'] != '' ? $_GET['page'] : 'vehicules'); ?>&subpage=listeMarquesVehicules" title="Afficher la liste des marques de véhicules">Marques de véhicule</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeModelesVehicules') echo 'active'; ?>" href="?page=<?php echo h(isset($_GET['page']) && $_GET['page'] != '' ? $_GET['page'] : 'vehicules'); ?>&subpage=listeModelesVehicules" title="Afficher la liste des modèles de véhicules">Modèles de véhicule</a>
                        </li>
                        <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeMarquesVehicules') : ?>
                            <hr>
                            <li class="nav-item">
                                <a class="nav-link" href="#" onclick="openModalMarque()" title="Ajouter une marque de véhicule">Nouvelle marque de véhicule</a>
                            </li>
                        <?php elseif (isset($_GET['subpage']) &&  $_GET['subpage'] == 'listeModelesVehicules') : ?>
                            <hr>
                            <li class="nav-item">
                                <a class="nav-link" href="#" onclick="openModalModele()" title="Ajouter une marque de véhicule">Nouveau modèle de véhicule</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php elseif (isset($_GET['page']) && $_GET['page'] == 'affectationVehicules' && in_array('view', $rights_affectation)): ?>
                    <?php if (in_array('save', $rights_affectation)): ?>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=affectationVehicules&subpage=listeChauffeurs&action=new" title="Créer un chauffeur de véhicule">Nouveau chauffeur</a>
                        </li>
                        <!-- <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="?page=affectationVehicules&subpage=listeConvoyeurs&action=new" title="Créer un convoyeur de véhicule">Nouveau convoyeur</a>
                    </li> -->
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=affectationVehicules&action=new" title="Créer une affectation de véhicule">Nouvelle affectation</a>
                        </li>
                    <?php endif; ?>
                <?php elseif (isset($_GET['page']) && $_GET['page'] == 'voyages' && in_array('view', $rights_voyage)): ?>
                    <?php if (in_array('save', $rights_voyage)): ?>
                        <?php if (in_array('savetrajet', $rights_voyage)): ?>
                            <li class="nav-item">
                                <a class="nav-link" aria-current="page" href="?page=voyages&subpage=listeTrajets&action=new" title="Créer un trajet de voyage">Nouveau trajet</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=voyages&action=new" title="Créer un voyage">Nouveau voyage</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=voyages&subpage=listeObjectifsVoyages&action=new" title="Définir les objectifs des voyages">Objectifs de voyages</a>
                        </li>
                    <?php endif; ?>
                    <?php if (in_array('report', $rights_voyage)): ?>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=voyages&subpage=evaluationVoyages" title="Afficher l'évaluation des voyages">Evaluation des voyages</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=voyages&subpage=listeVoyagesVehicules" title="Afficher les voyages groupés par véhicule par trajet">Voyages/Véhicules/Trajets</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="?page=voyages&subpage=listeVoyagesPeriodes" title="Afficher les voyages groupés par période par trajet">Voyages/Périodes/Trajets</a>
                        </li>
                    <?php endif; ?>
                <?php elseif (isset($_GET['page']) && $_GET['page'] == 'maintenances' && in_array('view', $rights_maintenance)): ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="?page=maintenances&subpage=releveKms" title="Afficher les relevés de kms">Relevés de kilométrage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="?page=maintenances&subpage=suiviVidanges" title="Afficher les suivis de vidanges">Suivi de vidanges</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="?page=maintenances&subpage=centreCouts" title="Gestion des centres de coûts">Centre de coûts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="?page=maintenances&subpage=suiviBonsReparation" title="Suivi des bons de réparation">Bons de réparation</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="col-10">
            <?php if (!isset($_GET['page']) || ($_GET['page'] == 'vehicules' || $_GET['page'] == '')) : ?>
                <?php include("vehicule.php"); ?>
                <?php if (!isset($_GET['subpage']) || ($_GET['subpage'] == 'listeVehicules' || $_GET['subpage'] == '')) : ?>
                    <div class="alert alert-primary">Liste des véhicules</div>
                    <hr>
                    <?php echo getTableauVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeMarquesVehicules') : ?>
                    <div class="alert alert-primary">Liste des marques de véhicules</div>
                    <hr>
                    <?php include("marqueVehicule.php");
                    echo getTableauMarqueVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeModelesVehicules') : ?>
                    <div class="alert alert-primary">Liste des modèles de véhicules</div>
                    <hr>
                    <?php include("modeleVehicule.php");
                    echo getTableauModeleVehicules(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'affectationVehicules'): ?>
                <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'listeChauffeurs'): ?>
                    <div class="alert alert-primary">Liste des chauffeurs de véhicules</div>
                    <hr>
                    <?php include("chauffeur.php");
                    echo getTableauChauffeurs(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeConvoyeurs'): ?>
                    <div class="alert alert-primary">Liste des convoyeurs de véhicules</div>
                    <hr>
                    <?php include("convoyeur.php");
                    echo getTableauConvoyeurs(); ?>
                <?php elseif (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeAffectationsVehicules'): ?>
                    <div class="alert alert-primary">Liste des affectations de véhicules</div>
                    <hr>
                    <?php include("affectationVehicule.php");
                    echo getTableauAffectations(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'voyages') : ?>
                <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'listeVoyages'): ?>
                    <div class="alert alert-primary">Liste des voyages de véhicules</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauVoyages(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeTrajets'): ?>
                    <div class="alert alert-primary">Liste des trajets de voyage</div>
                    <hr>
                    <?php include("trajet.php");
                    echo getTableauTrajets(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeObjectifsVoyages'): ?>
                    <div class="alert alert-primary">Liste des Objectifs de voyage</div>
                    <hr>
                    <?php include("objectif.php");
                    echo getTableauObjectifs(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'evaluationVoyages'): ?>
                    <div class="alert alert-primary">Evaluation des voyages</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauEvaluationVoyages(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesVehicules'): ?>
                    <div class="alert alert-primary">Liste des Voyages/Véhicules/Trajets</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauVoyagesVehicules(); ?>
                <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'listeVoyagesPeriodes'): ?>
                    <div class="alert alert-primary">Liste des Voyages/Périodes/Trajets</div>
                    <hr>
                    <?php include("voyage.php");
                    echo getTableauVoyagesPeriodes(); ?>
                <?php endif; ?>
            <?php elseif (isset($_GET['page']) && $_GET['page'] == 'maintenances') :  ?>
                <?php include("maintenance.php"); ?>
                <?php if (!isset($_GET['subpage']) || $_GET['subpage'] == 'releveKms'): ?>
                    <div class="alert alert-primary">Relevés de kilométrages</div>
                    <hr>
                    <?php echo getTableauReleveKMS(); ?>
                <?php elseif ($_GET['subpage'] == 'suiviVidanges'):  ?>
                    <div class="alert alert-primary">Suivi des vidanges</div>
                    <hr>
                    <?php echo getTableauVidange(); ?>
                <?php elseif ($_GET['subpage'] == 'prestataire'):  ?>
                    <div class="alert alert-primary">Liste des prestataires</div>
                    <hr>
                    <?php echo getTableauPrestataire(); ?>
                <?php elseif ($_GET['subpage'] == 'centreCouts'):  ?>
                    <div class="alert alert-primary">Liste des Centres de coûts</div>
                    <hr>
                    <?php echo getTableauCentreCout(); ?>
                <?php elseif ($_GET['subpage'] == 'suiviBonsReparation'):  ?>
                    <div class="alert alert-primary">Suivi des bons de réparation</div>
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
        initComplete: function() {
            this.api().columns().every(function() {
                var column = this;
                //added class "mymsel"
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
            //select2 init for .mymsel class
            $(".mymsel").select2({

            });

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
            }
            ,{
//             text:'Export Image',
//             action:function(e,dt,node,config){
//             $('#table-evaluation').parent().parent().parent().parent().attr('id','table-evaluation-wrapper')
//             html2canvas(document.querySelector('#table-evaluation-wrapper')).then(canvas => {
//     const a = document.createElement('a');
//       a.href = canvas.toDataURL('image/jpeg');
//       a.download = 'evaluationlogistique.jpeg';
//       a.click();
// });
   // }
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

    function changeSessionRegion(id) {
        if (confirm("Etes-vous sûr de vouloir changer de région ?")) {
            $.ajax({
                type: 'post',
                data: 'nSess=' + id
            }).done((e) => {
                let v = e.split('changeRegion%%%%%%')[1]
                if (v == '1') {
                    location.reload();
                } else if (v == '2') {
                    $.notify("Vous n'avez pas les droits pour passer à cette région", {
                        className: 'warning'
                    })
                } else {
                    $.notify("Erreur lors du changement de région")
                }
            })
        }
    }
    <?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'evaluationVoyages'): ?>
    table.destroy()
    $('.mymsel').select2('destroy')
    $('.mymsel').remove()
    $('<button class="btn btn-primary mb-3" onclick="exportTableToExcel(\'table-evaluation\')">Exporter</button>').insertBefore('#table-evaluation')
    <?php endif; ?>
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

    /* For selected filter. */
    .active .cb-dropdown {
        background: pink;
    }

    .cb-dropdown-wrap:hover .cb-dropdown {
        height: 80px;
        overflow: auto;
        transition: 0.2s height ease-in-out;
        z-index: 99999;
    }

    /* For selected items. */
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
        /* At least, width of the checkbox. */
        font-family: sans-serif;
        font-size: 0.8em;
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

    ul.select2-results__options li,
    ul.select2-selection__rendered li {
        font-size: 0.8em;
    }

    span.select2.select2-container {
        max-width: 150px !important;
        display: block
    }
</style>