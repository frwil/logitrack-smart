<?php
if (!in_array('view', $rights_maintenance)) : echo "<script>location='index.php'</script>'";
endif;
function getTableauReleveKMS()
{
    global $con;
    global $rights_maintenance;
    if (in_array('viewReleveKms', $rights_maintenance)):
        $sqlRel = "select * from releve_kms_vehicule,affectation_vehicule,vehicule,chauffeur,region where affectation_vehicule.id_vehicule=vehicule.id_vehicule and affectation_vehicule.id_affectation=releve_kms_vehicule.id_affectation_vehicule and chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur and affectation_vehicule.id_region=region.id_region ";
        $paramsRel = [];
        if (isset($_POST['date-f'])) {
            $sqlRel .= "and semaine_annee >= weekofyear(?) and semaine_annee <=weekofyear(?) and date_releve>=?";
            $paramsRel[] = date('Y-m-01', strtotime($_POST['date-f']));
            $paramsRel[] = date('Y-m-t', strtotime($_POST['date-t']));
            $paramsRel[] = date('Y-m-d', strtotime($_POST['date-f']));
        } else {
            $sqlRel .= "and semaine_annee >= weekofyear(?) and date_releve>=?";
            $paramsRel[] = date('Y-m-01');
            $paramsRel[] = date('Y-m-01');
        }
        $sqlRel .= " order by vehicule.id_vehicule,date_releve,semaine_annee";
        $q = db_select($con, $sqlRel, $paramsRel);
        $table = "<table id='table-releve-kms' class='no-datatable' style='display:none'><thead><tr><th>Véhicule</th><th>Région</th><th>Date Relevé</th><th>Kms</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_array($q)):
            $table .= "<tr><td>{$r['immatriculation_vehicule']} - {$r['nom_chauffeur']}</td><td>{$r['nom_region']}</td><td>{$r['periode_releve']} (" . date('d M Y', strtotime($r['date_debut_periode_releve'])) . "-" . date('d M Y', strtotime($r['date_fin_periode_releve'])) . ")</td><td>{$r['km_releve']}</td></tr>";
        endwhile;
        $table .= "</tbody></table><div id='output' style='margin: 30px;'></div>";
        include("modalNewReleveKMS.php");
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=releveKms&action=new'>Nouveau Relevé</a>&nbsp;<button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-upd-relevekms'>Modifier Relevé</button>&nbsp;<div style='display:inline-block;padding:15px'><form class='row' style='width:700px' method='post' action='#'><div class='col-5 form-floating' style='padding-left:5px'><input type='month' id='date-f' name='date-f' value='" . (isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m')) . "' class='form-control'><label for='date-f'>Date début</label></div><div class='col-5 form-floating' style='padding-left:5px'><input type='month' id='date-t' name='date-t' value='" . (isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m')) . "' class='form-control'><label for='date-t'>Date fin</label></div><div class='col-2' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form></div><hr>$table";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauVidange()
{
    global $con;
    global $rights_maintenance;
    if (in_array('viewVidange', $rights_maintenance)):
        $q = db_select($con, "select *,(select km_releve from releve_kms_vehicule where id_affectation_vehicule=vidange_vehicule.id_affectation_vehicule and date_fin_periode_releve=(select max(date_fin_periode_releve) from releve_kms_vehicule where id_affectation_vehicule=vidange_vehicule.id_affectation_vehicule)) as kms_actuel from vidange_vehicule,affectation_vehicule,vehicule,chauffeur,region where affectation_vehicule.id_vehicule=vehicule.id_vehicule and affectation_vehicule.id_affectation=vidange_vehicule.id_affectation_vehicule and chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur and affectation_vehicule.id_region=region.id_region and date_vidange=(select max(date_vidange) from vidange_vehicule v where v.id_affectation_vehicule=affectation_vehicule.id_affectation) and region.id_region=? order by vehicule.id_vehicule", [(int)$_SESSION['usr-con']['region-sel']]);
        $table = "<table id='table-suivi-vidanges' class='table table-striped'><thead><tr><th>Véhicule</th><th>Date dernière vidange</th><th>Kms (avant vidange)</th><th>Kms (prochaine vidange)</th><th>Kms actuel (dernier relevé)</th><th>Statut</th><th></th></tr></thead><tbody>";
        $danger = 0;
        $success = 0;
        while ($r = mysqli_fetch_array($q)):
            if ($r['kms_actuel'] > $r['km_prochaine_vidange'] - 1500):
                $danger++;
            else :
                $success++;
            endif;
            $table .= "<tr><td class='text-bg-" . ($r['kms_actuel'] > $r['km_prochaine_vidange'] - 1500 ? "danger" : "success") . "'>{$r['immatriculation_vehicule']} - {$r['nom_chauffeur']}</td><td>" . date('d M Y', strtotime($r['date_vidange'])) . "</td><td>{$r['km_vidange']}</td><td>{$r['km_prochaine_vidange']}</td><td>{$r['kms_actuel']}</td><td>" . ($r['kms_actuel'] > $r['km_prochaine_vidange'] - 1500 ? "<i class='fa fa-times text-danger'></i>&nbsp;Alerte" : "<i class='fa fa-check text-success'></i>&nbsp;Ok") . "</td><td><div class='btn-group'>" . (in_array("updVidange", $rights_maintenance) ? "<button class='btn btn-light btn-sm' data-bs-toggle='modal' data-bs-target='#modal-upd-vidange' data-bs-id-vd='{$r['code_vidange']}'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("historyVidange", $rights_maintenance) ? "<button class='btn btn-light btn-sm' title='historique des vidanges' onclick='showHistory(\"{$r['code_vidange']}\")'><i class='fa fa-file'></i></button>" : "") . (in_array("delVidange", $rights_maintenance) ? "<button class='btn btn-danger btn-sm' onclick='delVidange(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        endwhile;
        $table .= "</tbody></table><div id='output' style='margin: 30px;'></div>";
        $stats = "<table class='no-datatable'><tbody><tr><td><span class='badge text-bg-danger' style='width:100px'><i class='fa fa-times'></i>Alerte</span></td><td>$danger</td></tr><tr><td><span class='badge text-bg-success' style='width:100px'><i class='fa fa-check'></i>Ok</span></td><td>$success</td></tr></tbody></table>";
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=prestataire&action=new'>Nouveau Prestataire</a>&nbsp;<a class='btn btn-primary' href='?page=maintenances&subpage=suiviVidanges&action=new'>Nouvelle vidange</a><hr>$table<hr>$stats";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauPrestataire()
{
    global $con;
    global $rights_maintenance;
    if (in_array("viewPrestataire", $rights_maintenance)):
        $q = db_select($con, "select * from prestataire_intervention", []);
        $table = "<table id='table-prestataire' class='table table-striped'><thead><tr><th>Prestataire</th><th>Contact</th><th>Localisation</th><th></th></tr></thead><tbody>";
        while ($r = mysqli_fetch_array($q)):
            $table .= "<tr><td>{$r['nom_prestataire']}</td><td>{$r['contact_prestataire']}</td><td>{$r['localisation_prestataire']}</td><td><div class='btn-group'>" . (in_array("updPrestataire", $rights_maintenance) ? "<button class='btn btn-light' title='Modifier le prestataire' data-bs-toggle='modal' data-bs-target='#modal-upd-prestataire' data-bs-id-pt='" . sha1($r[0] . $r[1]) . "'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("delPrestataire", $rights_maintenance) ? "<button class='btn btn-danger' title='Supprimer' onclick='delPrestataire(\"" . sha1($r[0] . $r[1]) . "\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        endwhile;
        return $table . "</tbody></table>";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauCentreCout()
{
    global $con;
    global $rights_maintenance;
    if (in_array("viewCentreCout", $rights_maintenance)):
        $q = db_select($con, "select * from centre_couts", []);
        $table = "<table id='table-centrecouts' class='table table-striped'><thead><tr><th>Centre de coûts</th><th></th></tr></thead><tbody>";
        while ($r = mysqli_fetch_array($q)):
            $table .= "<tr><td>{$r['lib_centre_cout']}</td><td><div class='btn-group'>" . (in_array("updCentreCout", $rights_maintenance) ? "<button class='btn btn-light' title='Modifier le centre de coûts' data-bs-toggle='modal' data-bs-target='#modal-upd-centrecout' data-bs-id-cc='" . sha1($r[0] . $r[1]) . "'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("delCentreCout", $rights_maintenance) ? "<button class='btn btn-danger' title='Supprimer' onclick='delCentreCout(\"" . sha1($r[0] . $r[1]) . "\")'><i class='fa fa-times'></i></button>" : "") . "</div>";
        endwhile;
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=centreCouts&action=new'>Nouveau Centre de coûts</a><hr>" . $table . "</tbody></table>";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauBonsReparation()
{
    global $con;
    global $rights_maintenance;
    if (in_array("viewBonsReparation", $rights_maintenance)):
        $q = db_select($con, "select * from bons_reparation left join affectation_vehicule on id_affectation_vehicule=id_affectation left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join prestataire_intervention on prestataire_intervention.id_prestataire=bons_reparation.id_prestataire left join plus_ou_moins_value on plus_ou_moins_value.id_plus_ou_moins_value=bons_reparation.id_plus_ou_moins_value left join centre_couts on centre_couts.id_centre_cout=bons_reparation.id_centre_cout", []);
        $table = "<table id='table-centrecouts' class='table table-striped responsive'><thead><tr><th>N°</th><th>Véhicule</th><th>Date d'entrée</th><th>Diagnostic</th><th>Type d'exécution</th><th>Prestataire</th><th>Montant</th><th>Opération additionnelle</th><th>Montant opération</th><th>Montant réel</th><th>Destination</th><th>Durée réparation</th><th>Date de justification</th><th>Centre de coûts</th><th>Date prévue de sortie</th><th>Date effective de fin des travaux</th><th>Observations</th><th></th></tr></thead><tbody>";
        while ($r = mysqli_fetch_array($q)):
            $table .= "<tr><td>{$r['num_bon_reparation']}</td><td>{$r['immatriculation_vehicule']} - {$r['nom_chauffeur']}</td><td>" . date('d-m-Y', strtotime($r['date_entree'])) . "</td><td>{$r['diagnostic']}</td><td>" . ($r['type_execution'] == '0' ? "Interne" : "Externe") . "</td><td>{$r['nom_prestataire']}</td><td>{$r['montant_reparation']}</td><td>{$r['lib_plus_ou_moins_value']}</td><td>{$r['plus_ou_moins_value_valeur']}</td><td>" . ($r['montant_reparation'] + $r['plus_ou_moins_value_valeur'] * ($r['type_plus_ou_moins_value'] == 0 ? 1 : -1)) . "</td><td>{$r['destination_bon']}</td><td>{$r['duree_reparation']}</td><td>" . ($r['date_justification'] == '' ? "" : date('d-m-Y', strtotime($r['date_justification']))) . "</td><td>{$r['lib_centre_cout']}</td><td>" . ($r['date_prevue_sortie'] == "" ? "" : date('d-m-Y', strtotime($r['date_prevue_sortie']))) . "</td><td>" . ($r['date_fin_reparation'] == "" ? "" : date('d-m-Y', strtotime($r['date_fin_reparation']))) . "</td><td>{$r['observations']}</td><td><div class='btn-group'>" . (in_array("updBonsReparation", $rights_maintenance) ? "<button class='btn btn-light' title='Modifier' data-bs-toggle='modal' data-bs-target='#modal-upd-bonsReparation' data-bs-id-cc='" . sha1($r[0] . $r[1]) . "'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("delBonsReparation", $rights_maintenance) ? "<button class='btn btn-danger' title='Supprimer' onclick='delBonsReparation(\"" . sha1($r[0] . $r[1]) . "\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        endwhile;
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=suiviBonsReparation&action=new'>Nouveau Bon de réparation</a><hr>" . $table . "<tfoot></tfoot></tbody></table>";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}

if (isset($_POST['c-vd-s'])):
    $q = db_select($con, "select * from vidange_vehicule where code_vidange=?", [$_POST['c-vd-s']]);
    $liste = array();
    while ($r = mysqli_fetch_array($q)):
        $liste = $r;
    endwhile;
    unset($liste[0]);
    unset($liste['id_vidange']);
    die("LISTEVD%%%%%%" . json_encode($liste));
endif;
if (isset($_POST['c-pt-s'])):
    $q = db_select($con, "select *,sha1(concat(id_prestataire,nom_prestataire)) as id_pt from prestataire_intervention where sha1(concat(id_prestataire,nom_prestataire))=?", [$_POST['c-pt-s']]);
    $liste = array();
    while ($r = mysqli_fetch_array($q)):
        $liste = $r;
    endwhile;
    unset($liste[0]);
    unset($liste['id_prestataire']);
    die("LISTEPT%%%%%%" . json_encode($liste));
endif;
if (isset($_POST['c-upd-vd'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update vidange_vehicule set id_affectation_vehicule=(select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?),date_vidange=?,km_vidange=?,km_prochaine_vidange=?,id_prestataire=(select id_prestataire from prestataire_intervention where sha1(concat(id_prestataire,nom_prestataire))=?),commentaire_vidange=? where code_vidange=?", [$_POST['vh-upd-vd'], $_POST['date-upd-vd'], $_POST['km-upd-av-vd'], $_POST['km-upd-next-vd'], $_POST['id-upd-pt-vd'], $_POST['comment-upd-vd'] === '' ? null : $_POST['comment-upd-vd'], $_POST['c-upd-vd']]);
        mysqli_commit($con);
        die("VIDANGEMOD%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("VIDANGEMOD%%%%%%0");
    }
endif;
if (isset($_POST['id-upd-pt'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update prestataire_intervention set nom_prestataire=?, contact_prestataire=?, localisation_prestataire=? where sha1(concat(id_prestataire,nom_prestataire))=?", [$_POST['nom-upd-pt'], $_POST['contact-upd-pt'] === '' ? null : $_POST['contact-upd-pt'], $_POST['localisation-upd-pt'] === '' ? null : $_POST['localisation-upd-pt'], $_POST['id-upd-pt']]);
        mysqli_commit($con);
        die("PTMOD%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("PTMOD%%%%%%0");
    }
endif;
if (isset($_POST['cd-vd-hist'])):
    $q = db_select($con, "select *,(select km_releve from releve_kms_vehicule where id_affectation_vehicule=vidange_vehicule.id_affectation_vehicule and date_fin_periode_releve=(select max(date_fin_periode_releve) from releve_kms_vehicule where id_affectation_vehicule=vidange_vehicule.id_affectation_vehicule)) as kms_actuel from vidange_vehicule,affectation_vehicule,vehicule,chauffeur,region,prestataire_intervention where prestataire_intervention.id_prestataire=vidange_vehicule.id_prestataire and affectation_vehicule.id_vehicule=vehicule.id_vehicule and affectation_vehicule.id_affectation=vidange_vehicule.id_affectation_vehicule and chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur and affectation_vehicule.id_region=region.id_region and id_affectation_vehicule=(select id_affectation_vehicule from vidange_vehicule vv where vv.code_vidange=?) and region.id_region=? order by vehicule.id_vehicule", [$_POST['cd-vd-hist'], (int)$_SESSION['usr-con']['region-sel']]);
    $table_content = "";
    $i = 0;
    while ($r = mysqli_fetch_array($q)):
        if ($i == 0) :
            $table_content .= "<tr><td colspan='6' class='text-center'>HISTORIQUE VIDANGE VEHICULE</td></tr>";
            $table_content .= "<tr><td>Véhicule</td><td colspan='3'>{$r['immatriculation_vehicule']} - {$r['nom_chauffeur']}</td><td>Km Actuel</td><td>{$r['kms_actuel']}</td></tr>";
            $table_content .= "<tr><td colspan='6'></td></tr>";
            $table_content .= "<tr><td>Date vidange</td><td>Date</td><td>KM (avant vidange)</td><td>KM (prochaine vidange)</td><td>Prestataire</td><td>Commentaire</td></tr>";
        endif;
        $table_content .= "<tr><td>" . date('d M Y', strtotime($r['date_vidange'])) . "</td><td>{$r['date_vidange']}</td><td>{$r['km_vidange']}</td><td>{$r['km_prochaine_vidange']}</td><td>{$r['nom_prestataire']}</td><td>{$r['commentaire_vidange']}</td></tr>";
        $i++;
    endwhile;
    die("HISTVD%%%%%%$table_content");
endif;
if (isset($_POST['semPer'])):
    $_POST['semPer'] = date('Y-m-01', strtotime($_POST['semPer']));
    $psem = getPremiereSemaineDuMois($_POST['semPer']);
    $q = db_select($con, "select distinct periode_releve from releve_kms_vehicule where date_debut_periode_releve>=? and date_fin_periode_releve<=? order by periode_releve", [$psem[0], date('Y-m-t', strtotime($_POST['semPer']))]);
    $options = "";
    while ($r = mysqli_fetch_array($q)):
        $options .= "<option value='" . sha1($r[0]) . "'>{$r[0]}</option>";
    endwhile;
    die("SEMPER%%%%%%$options");
endif;
if (isset($_POST['vhPer'])):
    $q = db_select($con, "select km_releve from releve_kms_vehicule where sha1(periode_releve)=? and id_affectation_vehicule=(select id_affectation from affectation_vehicule where sha1(concat(id_affectation, id_vehicule))=?)", [$_POST['perSem'], $_POST['vhPer']]);
    $kms = 0;
    while ($r = mysqli_fetch_array($q)):
        $kms = $r[0];
    endwhile;
    die("VHPER%%%%%%$kms");
endif;
if (isset($_POST['updRel'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update releve_kms_vehicule set km_releve=? where sha1(periode_releve)=? and id_affectation_vehicule=(select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?)", [$_POST['kmsRel'], $_POST['updRel'], $_POST['vhRel']]);
        mysqli_commit($con);
        die("UPDREL%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UPDREL%%%%%%0");
    }
endif;
function getPremiereSemaineDuMois($date)
{
    $premierJourDuMois = date('Y-m-01', strtotime($date));
    $jourDeLaSemaine = date('w', strtotime($premierJourDuMois)); // 0 pour dimanche, 1 pour lundi, etc.


    $premiereSemaine = [];

    // Ajouter les jours de la semaine précédente si le premier jour du mois n'est pas un lundi
    for ($i = $jourDeLaSemaine - 1; $i >= 0; $i--) {
        $premiereSemaine[] = date('Y-m-d', strtotime($premierJourDuMois . ' -' . ($i + 1) . ' days'));
    }
    // Ajouter les jours de la première semaine du mois
    for ($i = 0; count($premiereSemaine) < 7; $i++) {
        $premiereSemaine[] = date('Y-m-d', strtotime($premierJourDuMois . ' +' . $i . ' days'));
    }

    // Ajuster l'ordre des jours (du lundi au dimanche)
    usort($premiereSemaine, function ($a, $b) {
        return strtotime($a) - strtotime($b);
    });

    return $premiereSemaine;
}

if (isset($_POST['del-pt-id'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "delete from prestataire_intervention where sha1(concat(id_prestataire,nom_prestataire))=?", [$_POST['del-pt-id']]);
        mysqli_commit($con);
        die("DELPT%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("DELPT%%%%%%0");
    }
endif;
if (isset($_POST['c-cc-s'])):
    $q = db_select($con, "select *,sha1(concat(id_centre_cout,lib_centre_cout)) as id_cc from centre_couts where sha1(concat(id_centre_cout,lib_centre_cout))=?", [$_POST['c-cc-s']]);
    $liste = array();
    while ($r = mysqli_fetch_array($q)):
        $liste = $r;
    endwhile;
    die("LISTECC%%%%%%" . json_encode($liste));
endif;
if (isset($_POST['del-cc-id'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "delete from centre_couts where sha1(concat(id_centre_cout,lib_centre_cout))=?", [$_POST['del-cc-id']]);
        mysqli_commit($con);
        die("DELCC%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("DELCC%%%%%%0");
    }
endif;
if(isset($_POST['del-vd-id'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "delete from vidange_vehicule where sha1(concat(id_vidange_vehicule,code_vidange))=?", [$_POST['del-vd-id']]);
        mysqli_commit($con);
        die("DELVD%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("DELVD%%%%%%0");
    }
endif;
?>
<?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'releveKms' || !isset($_GET['subpage'])) : ?>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("saveReleveKms", $rights_maintenance)): ?>
        <script>
            setTimeout(() => {
                openModalReleve()
            }, 2000)
        </script>
    <?php endif; ?>
    <div class="modal fade" id="modal-upd-relevekms" tabindex="-1" aria-labelledby="modal-upd-relevekmsLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-relevekmsLabel">Relevé de kilométrage <span id='relevekmsdisplay'></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="#" id="form-upd-relevekms">
                        <div class="form-floating mb-3">
                            <input type="month" required id="date-upd-releve-kms" name="date-upd-releve-kms" value="<?php if (isset($_GET['dch'])) echo date('Y-m', strtotime($_GET['dch']));
                                                                                                                    else echo date('Y-m'); ?>" class="form-control" onchange="getSemainesToUpd(this.value)" <?php if (isset($_GET['dch'])) echo "readonly"; ?>>
                            <label for="date-upd-releve-kms">Période</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select required name="per-upd-releve-kms" id="per-upd-releve-kms" class="form-select" onchange="$('#vh-upd-releve-kms').change()">

                            </select>
                            <label for="per-upd-releve-kms">Semaine</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select required id="vh-upd-releve-kms" name="vh-upd-releve-kms" class="form-select" onchange="getKmsPeriode(this.value,$('#per-upd-releve-kms').val())">
                                <?php $q = db_select($con, "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0 and affectation_vehicule.id_region=?", [(int)$_SESSION['usr-con']['region-sel']]);
                                while ($r = mysqli_fetch_array($q)):
                                    echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r[0] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                                endwhile;
                                ?>
                            </select>
                            <label for="vh-upd-releve-kms">Véhicule</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" min="0" value="0" required id="val-upd-releve-kms" name="val-upd-releve-kms" class="form-control">
                            <label for="val-upd-releve-kms">Km (valeur)</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updateReleve()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const modalUpdRel = document.getElementById('modal-upd-relevekms')
        if (modalUpdRel) {
            modalUpdRel.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                getSemainesToUpd($('#date-upd-releve-kms').val())
            })
        }
        $(function() {
            google.load("visualization", "1", {
                packages: ["corechart", "charteditor"]
            });
            var tpl = $.pivotUtilities.aggregatorTemplates;
            var derivers = $.pivotUtilities.derivers;
            var renderers = $.extend($.pivotUtilities.renderers,
                $.pivotUtilities.gchart_renderers);
            $("#output").pivotUI($("#table-releve-kms"), {
                rows: ["Véhicule", "Région"],
                cols: ["Date Relevé"],
                aggregators: {
                    "Kms": function() {
                        return tpl.sum()(["Kms"])
                    }
                },
                renderers: renderers,
                rendererName: "Table Barchart",
                filter: (e) => {
                    return e["Région"].toLowerCase() == (<?php echo j($_SESSION['usr-con']['region-sel-name']); ?>).toLowerCase()
                }
            });
        });

        function getSemainesToUpd(p) {
            $.ajax({
                type: 'post',
                data: 'semPer=' + p + '-01'
            }).done((e) => {
                let v = e.split('SEMPER%%%%%%')[1]
                $('#per-upd-releve-kms').html(v)
            })
        }

        function getKmsPeriode(v, p) {
            $.ajax({
                type: 'post',
                data: 'vhPer=' + v + '&perSem=' + p
            }).done((e) => {
                let v = e.split('VHPER%%%%%%')[1]
                $('#val-upd-releve-kms').val(v)
            })
        }

        function updateReleve() {
            var valid = true
            $('#modal-upd-relevekms *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#form-upd-relevekms').notify("Tous les champs en rouge sont obligatoires!", {
                    postion: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: 'updRel=' + $('#per-upd-releve-kms').val() + '&vhRel=' + $('#vh-upd-releve-kms').val() + '&kmsRel=' + $('#val-upd-releve-kms').val()
            }).done((e) => {
                let v = e.split('UPDREL%%%%%%')[1]
                if (v == '1') {
                    alert('Enregistrement effectué!')
                    location.reload()
                }
                $('#form-upd-relevekms').notify("Erreur lors de l'enregistrement", {
                    position: 'top'
                })
            })
        }
    </script>
    <?php elseif ($_GET['subpage'] == 'prestataire') :
    if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("savePrestataire", $rights_maintenance)):
        include("modalNewPrestataire.php");
    ?>
        <script>
            setTimeout(() => {
                openModalPrestataire()
            }, 2000)
        </script>
    <?php endif; ?>
    <div class="modal fade" id="modal-upd-prestataire" tabindex="-1" aria-labelledby="modal-upd-prestataireLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-prestataireLabel">Prestataire : <span id='id-prestataire'></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-upd-pt">
                        <div class="form-floating mb-3">
                            <input type="text" id="nom-upd-pt" name="nom-upd-pt" required class="form-control">
                            <input type="hidden" id="id-upd-pt" name="id-upd-pt">
                            <label for="nom-upd-pt">Nom Prestataire</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" id="contact-upd-pt" name="contact-upd-pt" class="form-control">
                            <label for="contact-upd-pt">Contact</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="localisation-upd-pt" name="localisation-upd-pt">
                            <label for="localisation-upd-pt">Localisation</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updatePT()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const modalUpdPT = document.getElementById('modal-upd-prestataire')
        if (modalUpdPT) {
            modalUpdPT.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const id = event.relatedTarget.getAttribute('data-bs-id-pt')
                $.ajax({
                    type: 'post',
                    data: 'c-pt-s=' + id
                }).done((e) => {
                    let v = JSON.parse(e.split('LISTEPT%%%%%%')[1])
                    $('#nom-upd-pt').val(v.nom_prestataire)
                    $('#contact-upd-pt').val(v.contact_prestataire)
                    $('#localisation-upd-pt').val(v.localisation_prestataire)
                    $('#id-prestataire').html(v.nom_prestataire)
                    $('#id-upd-pt').val(v.id_pt)
                })
            })
        }

        function updatePT() {
            var valid = true
            $('#form-upd-pt *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#form-upd-pt').notify("Tous les champs en rouge sont obligatoires!", {
                    position: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: $('#form-upd-pt').serialize()
            }).done((e) => {
                let v = e.split('PTMOD%%%%%%')[1]
                if (v == '1') {
                    alert('Enregistrement effectué')
                    location.reload()
                } else {
                    $('#modal-upd-prestataire .modal-body').notify("Erreur lors de l'enregistrement!", {
                        position: 'top'
                    })
                }
            })
        }

        function delPrestataire(id) {
            if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                $.ajax({
                    type: 'post',
                    data: 'del-pt-id=' + id
                }).done((e) => {
                    let v = e.split('DELPT%%%%%%')[1]
                    if (v == '1') {
                        alert('Suppression effectuée!')
                        location.reload()
                    }
                    $.notify("Erreur lors de la suppression!", {
                        position: 'top'
                    })
                })
            }
        }
    </script>
    <?php elseif ($_GET['subpage'] == 'suiviVidanges') : include("modalNewVidange.php");
    if (isset($_GET['action']) && $_GET['action'] == 'new'):
    ?>
        <script>
            setTimeout(() => {
                openModalVidange()
            }, 2000)
        </script>
    <?php endif;
    ?>
    <div id="table-history-div">
        <table class="table table-striped no-datatable" id="table-history" style="display:none">
            <thead>
                <tr>
                    <th colspan='6'></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <div class="modal fade" id="modal-upd-vidange" tabindex="-1" aria-labelledby="modal-upd-vidangeLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-vidangeLabel">Vidange <span id="code-vidange"></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-upd-vidange">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-upd-vd" name="date-upd-vd" required class="form-control">
                            <label for="date-vd">Date Vidange</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select id="vh-upd-vd" name="vh-upd-vd" class="form-select">
                                <?php $q = db_select($con, "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0 and affectation_vehicule.id_region=?", [(int)$_SESSION['usr-con']['region-sel']]);
                                while ($r = mysqli_fetch_array($q)):
                                    echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r[0] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                                endwhile;
                                ?>
                            </select>
                            <label for="vh-upd-vd">Véhicule</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="km-upd-av-vd" name="km-upd-av-vd" required min="0" value="0">
                            <label for="km-upd-av-vd">Km (avant vidange)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="km-upd-next-vd" name="km-upd-next-vd" required min="0" value="0">
                            <label for="km-upd-av-vd">Km (prochaine vidange)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-select" id="id-upd-pt-vd" name="id-upd-pt-vd">
                                <?php $q = db_select($con, "select * from prestataire_intervention", []);
                                while ($r = mysqli_fetch_array($q)):
                                    echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                endwhile;
                                ?>
                            </select>
                            <label for="id-upd-pt-vd">Prestataire</label>
                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="comment-upd-vd" name="comment-upd-vd"></textarea>
                            <label for="comment-upd-vd">Commentaire</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updateVidange()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        const modalUpdVidange = document.getElementById('modal-upd-vidange')
        if (modalUpdVidange) {
            modalUpdVidange.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const id = event.relatedTarget.getAttribute('data-bs-id-vd')
                $('#code-vidange').html(id)
                $.ajax({
                    type: 'post',
                    data: 'c-vd-s=' + id
                }).done((e) => {
                    let v = JSON.parse(e.split('LISTEVD%%%%%%')[1])
                    $('#date-upd-vd').val(v.date_vidange)
                    $('#vh-upd-vd option[value="' + v.id_affectation_vehicule + '"]').prop('selected', true)
                    $('#km-upd-av-vd').val(v.km_vidange)
                    $('#km-upd-next-vd').val(v.km_prochaine_vidange)
                    $('#id-upd-pt-vd option[value="' + v.id_prestataire + '"]').prop('selected', true)
                    $('#comment-upd-vd').html(v.commentaire_vidange)
                })

            })
        }

        function updateVidange() {
            var valid = true
            $('#form-upd-vidange *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#modal-upd-vidange .modal-body').notify("Tous les champs en rouge sont obligatoires", {
                    position: 'top'
                })
                return false
            }
            if (parseFloat($('#km-upd-av-vd').val()) >= parseFloat($('#km-upd-next-vd').val())) {
                $('#form-upd-vidange').notify("Le km de prochaine vidange doit être supérieur au km avant vidange", {
                    position: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: $('#form-upd-vidange').serialize() + '&c-upd-vd=' + $('#code-vidange').html()
            }).done((e) => {
                let v = e.split('VIDANGEMOD%%%%%%')[1]
                if (v == '1') {
                    alert('Enregistrement effectué')
                    location.reload()
                } else {
                    $('#modal-upd-vidange').notify("Erreur lors de l'enregistrement!", {
                        position: 'top'
                    })
                }
            })
        }

        function showHistory(code) {
            $.ajax({
                type: 'post',
                data: 'cd-vd-hist=' + code
            }).done((e) => {
                let v = e.split('HISTVD%%%%%%')[1]
                $('#table-history tbody').html(v)
                exportToExcel('table-history', 'historique_vidange_' + code.replace(/-/g, '_'))
                //exportToExcel()
            })
        }
        function delVidange(id){
            if(confirm("Etes-vous sûr de vouloir supprimer ?")) {
                $.ajax({
                    type: 'post',
                    data: 'del-vd-id=' + id
                }).done((e) => {
                    let v = e.split('DELVD%%%%%%')[1]
                    if (v == '1') {
                        alert('Suppression effectuée!')
                        location.reload()
                    }
                    $.notify("Erreur lors de la suppression!", {
                        position: 'top'
                    })
                })
            }
        }

        function tableToXLSX(tableId) {
            const table = document.getElementById(tableId);
            const data = [];

            // Récupérer les données du tableau
            for (let i = 0; i < table.rows.length; i++) {
                const row = table.rows[i];
                const rowData = [];
                for (let j = 0; j < row.cells.length; j++) {
                    rowData.push(row.cells[j].innerText);
                }
                data.push(rowData);
            }

            // Créer un objet workbook et worksheet
            const workbook = XLSX.utils.book_new();
            const worksheet = XLSX.utils.aoa_to_sheet(data);

            // Ajouter le worksheet au workbook
            XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

            // Générer le fichier XLSX
            const excelBuffer = XLSX.write(workbook, {
                bookType: "xlsx",
                type: "array"
            });

            return excelBuffer;
        }

        function downloadXLSX(excelBuffer, filename = "export.xlsx") {
            const blob = new Blob([excelBuffer], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        }

        function exportToExcel(tableId, fn = '') {
            const excelBuffer = tableToXLSX(tableId);
            downloadXLSX(excelBuffer, fn);
        }
    </script>
    <?php elseif ($_GET['subpage'] == 'centreCouts') :
    include("modalNewCentreCout.php");
    if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("saveCentreCout", $rights_maintenance)):
    ?>
        <script>
            setTimeout(() => {
                openModalCentreCout()
            }, 2000)
        </script>
    <?php endif;
    ?>
    <div class="modal fade" id="modal-upd-centrecout" tabindex="-1" aria-labelledby="modal-upd-centrecoutLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-centrecoutLabel">Centre de coût : <span id='id-centrecout'></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-upd-pt">
                        <div class="form-floating mb-3">
                            <input type="text" id="nom-upd-cc" name="nom-upd-cc" required class="form-control">
                            <input type="hidden" id="id-upd-cc" name="id-upd-cc">
                            <label for="nom-upd-cc">Désignation Centre de coût</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updateCC()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateCC() {
            var valid = true
            $('#form-upd-cc *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#form-upd-cc').notify("Tous les champs en rouge sont obligatoires!", {
                    position: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: $('#form-upd-cc').serialize()
            }).done((e) => {
                let v = e.split('CCMOD%%%%%%')[1]
                if (v == '1') {
                    alert('Enregistrement effectué')
                    location.reload()
                } else {
                    $('#modal-upd-centrecout .modal-body').notify("Erreur lors de l'enregistrement!", {
                        position: 'top'
                    })
                }
            })
        }
        const modalUpdCC = document.getElementById('modal-upd-centrecout')
        if (modalUpdCC) {
            modalUpdCC.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const id = event.relatedTarget.getAttribute('data-bs-id-cc')
                $.ajax({
                    type: 'post',
                    data: 'c-cc-s=' + id
                }).done((e) => {
                    let v = JSON.parse(e.split('LISTECC%%%%%%')[1])
                    $('#nom-upd-cc').val(v.lib_centre_cout)
                    $('#id-prestataire').html(v.lib_centre_cout)
                    $('#id-upd-cc').val(v.id_cc)
                })
            })
        }

        function delCentreCout(id) {
            if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                $.ajax({
                    type: 'post',
                    data: 'del-cc-id=' + id
                }).done((e) => {
                    let v = e.split('DELCC%%%%%%')[1]
                    if (v == '1') {
                        alert('Suppression effectuée!')
                        location.reload()
                    }
                    $.notify("Erreur lors de la suppression!", {
                        position: 'top'
                    })
                })
            }
        }
    </script>
    <?php elseif ($_GET['subpage'] == 'suiviBonsReparation') : include("modalNewSuiviBonsReparation.php");
    if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("saveBonsReparation", $rights_maintenance)):
    ?>
        <script>
            setTimeout(() => {
                openModalSuiviBonsReparation()
            }, 2000)
        </script>
    <?php endif;
    ?>
<?php endif; ?>
<style>
    .modal.show .modal-dialog {
        color: inherit !important;
        padding: inherit !important;
        border-radius: 0.5rem;
        position: relative
    }

    th span.select2 {
        display: block
    }
</style>