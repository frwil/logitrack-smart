<script>
    function getTotalLine(l, v, v2, v3) {
        $('#total_vg_ln_' + l).html(v)
        $('#total_kms_ln_' + l).html(v2)
        $('#total_cbt_ln_' + l).html(v3)
        $('#total_cbt_100_ln_' + l).html((v3 / (v2 > 0 ? v2 : 1) * 100).toFixed(2))
    }
</script>
<?php function getTableauVoyages()
{
    global $con;
    global $rights_voyage;
    $debut = date_create((isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-d', strtotime(date('Y-m-01')))));
    $fin = date_create((isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-d', strtotime(date('Y-m-t')))));
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($debut, $interval, $fin->add($interval));
    $vehiculeRepo = new VehiculeRepository($con);
    $voyageRepo = new VoyageRepository($con);
    $activeVehicles = $vehiculeRepo->findActiveByRegion((int)$_SESSION['usr-con']['region-sel']);
    $tableau = "<table class='table table-striped'><thead><tr><th>Immatriculation</th>";
    foreach ($date_range as $date) :
        $tableau .= "<th>{$date->format('d M Y')}</th>";
    endforeach;
    $tableau .= "<th># Voyages</th><th># Kms</th><th>Chargement</th><th>Carburant</th></tr></thead><tbody>";
    foreach ($activeVehicles as $r):
        $tableau .= "<tr><td class='text-bg-dark'>" . h($r['immatriculation_vehicule']) . "</td>";
        $voyage = "";
        $total_voyages = 0;
        $total_kms = 0;
        $total_chargement = 0;
        $total_carburant = 0;
        foreach ($date_range as $date) :
            $voyages = $voyageRepo->findByVehiculeAndDate((int)$r['id_vehicule'], $date->format('Y-m-d'));
            $voyage .= "<td><ul class='list-group'>";
            if (empty($voyages)) $voyage .= "</ul></td>";
            $cpte = 0;
            foreach ($voyages as $r1):
                $vgHash = $r1['id_voyage'];
                $affHash = $r1['id_affectation'];
                $voyage .= "<li class='list-group-item'>" . ($cpte > 0 ? "<span class='text-white' style='font-size:5px'>|==></span>" : "") . h($r1['lib_destination']) . " - " . h($r1['distance_destination']) . "km - " . h($r1['qte_carburant']) . "L - " . h($r1['qte_chargement']) . " (" . h($r1['lib_type_chargement']) . ") <div class='btn-group'>".(in_array('upd',$rights_voyage) ? "<button class='btn btn-light btn-sm' onclick='updvg(\"$vgHash\",\"$affHash\")'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array("del",$rights_voyage) ? "<button class='btn btn-danger btn-sm' onclick='delvg(\"$vgHash\")'><i class='fa fa-times'></i></button>" : "")."</div></li>";
                $total_voyages++;
                $total_kms += $r1['distance_destination'];
                $total_carburant += $r1['qte_carburant'];
                $total_chargement += $r1['qte_chargement'];
                $cpte++;
            endforeach;
            if (!empty($voyages)) $voyage .= "</ul></td>";
        endforeach;
        $tableau .= $voyage;
        $tableau .= "<td class='text-bg-dark'>$total_voyages</td><td class='text-bg-dark'>$total_kms</td><td class='text-bg-dark'>$total_chargement</td><td class='text-bg-dark'>$total_carburant</td></tr>";
    endforeach;
    $tableau .= "</tbody></table>";
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? h($_POST['date-f']) : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? h($_POST['date-t']) : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $form . "<hr>" . $tableau;
}
function getTableauVoyagesVehicules()
{
    global $con;
    $vehiculeRepo = new VehiculeRepository($con);
    $voyageRepo = new VoyageRepository($con);
    $trajetRepo = new TrajetRepository($con);
    $vehicleRows = $vehiculeRepo->findAllWithChauffeur();
    $nblignes = count($vehicleRows);
    $tableau = "<table class='table table-striped'><thead><tr><th>#</th><th>Immatriculation</th><th># Voyages</th><th># Kms</th><th>Carburant (en L)</th><th>Conso. 100km</th>";
    $destinations = $trajetRepo->findAll();
    $nbTrajets = count($destinations);
    $trajets = array_column($destinations, 'id_destination');
    foreach ($destinations as $r):
        $tableau .= "<th>" . h($r['lib_destination']) . "</th>";
    endforeach;
    $tableau .= "</tr></thead><tbody>";
    $i = 1;
    $total_voyages = 0;
    $total_kms = 0;
    $total_cbt = 0;
    $total_voyage_col = array();
    $total_kms_col = array();
    $voyages_array = array();
    $k = 0;
    $total_cbt_ligne = 0;
    $dateFrom = isset($_POST['date-f']) ? $_POST['date-f'] : null;
    $dateTo = isset($_POST['date-t']) ? $_POST['date-t'] : null;
    foreach ($vehicleRows as $r):
        $voyages_array = array();
        $v_arr = array();
        $total_distance = 0;
        $total_kms_ligne = 0;
        $total_cbt_ligne = 0;
        $vid = (int)$r['id_vehicule'];
        $tableau .= "<tr><td>$i</td><td>" . h($r['immatriculation_vehicule']) . " - " . h($r['n_chauffeur']) . "</td><td><span id='total_vg_ln_$vid'></span></td><td><span id='total_kms_ln_$vid'></span></td><td><span id='total_cbt_ln_$vid'></span></td><td><span id='total_cbt_100_ln_$vid'></span></td>";
        for ($j = 0; $j < count($trajets); $j++):
            $v_arr[$trajets[$j]] = array();
            $q1Results = $voyageRepo->findVoyageVehiculesByDestination((int)$trajets[$j], $vid, $dateFrom, $dateTo);
            $q1Count = count($q1Results);
            foreach ($q1Results as $r1):
                $total_distance += $r1['dist_trajet'];
                $total_cbt_ligne += $r1['qte_carburant'];
                if (!in_array($r1['id_voyage'], $v_arr[$trajets[$j]])) : array_push($v_arr[$trajets[$j]], $r1['id_voyage']);
                endif;
                if (!in_array($r1['id_voyage'], $voyages_array)) : array_push($voyages_array, $r1['id_voyage']);
                endif;
            endforeach;
            $tableau .= "<td " . ($q1Count > 0 ? "class='text-bg-success' style='background-color:#198754;color:white'>" . count($v_arr[$trajets[$j]]) : " class='text-bg-info' style='background-color:#0dcaf0;'>") . "</td>";

            if ($q1Count > 0):
                $distRow = $trajetRepo->findDistanceById((int)$trajets[$j]);
                $kms = $distRow ? (int)$distRow['distance_destination'] : 0;
                $total_kms_ligne = $total_distance;
                if (!isset($total_voyage_col[$i][$j])) $total_voyage_col[$i][$j] = 0;
                $total_voyage_col[$i][$j] += $q1Count;
                if (!isset($total_kms_col[$i][$j])) $total_kms_col[$i][$j] = 0;
                $total_kms_col[$i][$j] += $kms * $q1Count;
            else :
                $total_voyage_col[$i][$j] = 0;
                $total_kms_col[$j][$j] = 0;
            endif;

        endfor;
        $tableau .= "</tr>";
        $i++;
        $tableau .= "<script>getTotalLine($vid," . count($voyages_array) . ",$total_kms_ligne,$total_cbt_ligne)</script>";
        $total_voyages += count($voyages_array);
        $total_kms += $total_kms_ligne;
        $total_cbt += $total_cbt_ligne;
        $k++;
    endforeach;
    $tfoot = "";
    if ($nbTrajets > 0):
        $total_col = array();
        $total_k = array();
        for ($i = 0; $i < $nblignes; $i++) {
            if ($i == 0) {
                $total_col[$i] = 0;
                $total_k[$i] = 0;
            }
            for ($j = 0; $j < $nbTrajets; $j++) {
                if (!isset($total_voyage_col[$i][$j])) $total_voyage_col[$i][$j] = 0;
                if (!isset($total_col[$j])) $total_col[$j] = 0;
                $total_col[$j] += $total_voyage_col[$i][$j];
                if (!isset($total_kms_col[$i][$j])) $total_kms_col[$i][$j] = 0;
                if (!isset($total_k[$j])) $total_k[$j] = 0;
                $total_k[$j] += $total_kms_col[$i][$j];
            }
        }
        $tfoot = "<tr style='font-weight:bold'><td colspan=2  class='text-bg-dark'>Total</td><td  class='text-bg-dark'>{$total_voyages}</td><td class='text-bg-dark'>{$total_kms}</td><td class='text-bg-dark'>{$total_cbt}</td><td class='text-bg-dark'>" . round($total_cbt / ($total_kms > 0 ? $total_kms : 1) * 100, 2) . "</td>";
        for ($i = 0; $i < $nbTrajets; $i++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . (int)$total_col[$i] . "</td>";
        $tfoot .= "</tr>";
        $tfoot .= "<tr style='font-weight:bold'><td colspan=2  class='text-bg-dark'>Total</td><td  class='text-bg-dark'>{$total_voyages}</td><td class='text-bg-dark'>{$total_kms}</td><td></td><td></td>";
        for ($i = 0; $i < $nbTrajets; $i++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . (int)$total_k[$i] . "</td>";
        $tfoot .= "</tr>";
    endif;
    $tableau .= "</tbody><tfoot>$tfoot</tfoot></table>";
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? h($_POST['date-f']) : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? h($_POST['date-t']) : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $form . "<hr>" . $tableau;
}
function getTableauVoyagesPeriodes()
{
    global $con;
    $debut = date_create((isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-d', strtotime(date('Y-m-01')))));
    $fin = date_create((isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-d', strtotime(date('Y-m-t')))));
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($debut, $interval, $fin->add($interval));
    $nblignes = ((int)$fin->diff($debut)->format('%a')) + 1;
    $voyageRepo = new VoyageRepository($con);
    $trajetRepo = new TrajetRepository($con);
    $tableau = "<table class='table table-striped'><thead><tr><th>#</th><th>Date</th><th># Voyages</th><th># Kms</th><th>Carburant (en L)</th><th>Conso. 100km</th>";
    $destinations = $trajetRepo->findAll();
    $nbTrajets = count($destinations);
    $trajets = array_column($destinations, 'id_destination');
    foreach ($destinations as $r):
        $tableau .= "<th>" . h($r['lib_destination']) . "</th>";
    endforeach;
    $tableau .= "</tr></thead><tbody>";
    $i = 1;
    $total_voyages = 0;
    $total_kms = 0;
    $total_cbt = 0;
    $total_voyage_col = array();
    $total_kms_col = array();
    foreach ($date_range as $date) :
        $total_distance = 0;
        $voyages_array = array();
        $total_kms_ligne = 0;
        $total_cbt_ligne = 0;
        $dateStr = $date->format('Y-m-d');
        $tableau .= "<tr><td>$i</td><td>" . $date->format('d M Y') . "</td><td><span id='total_vg_ln_{$date->format('dmY')}'></span></td><td><span id='total_kms_ln_{$date->format('dmY')}'></span></td><td><span id='total_cbt_ln_{$date->format('dmY')}'></span></td><td><span id='total_cbt_100_ln_{$date->format('dmY')}'></span></td>";
        for ($j = 0; $j < count($trajets); $j++):
            $q1Results = $voyageRepo->findVoyageVehiculesByDateDestination((int)$trajets[$j], $dateStr);
            $q1Count = count($q1Results);
            foreach ($q1Results as $r1):
                $total_distance += $r1['dist_trajet'];
                $total_cbt_ligne += $r1['qte_carburant'];
                if (!in_array($r1['id_voyage'], $voyages_array)) : array_push($voyages_array, $r1['id_voyage']);
                endif;
            endforeach;
            $tableau .= "<td " . ($q1Count > 0 ? "class='text-bg-success' style='background-color:#198754;color:white'>" . $q1Count : " class='text-bg-info' style='background-color:#0dcaf0;'>") . "</td>";

            if ($q1Count > 0):
                $total_voyage_ligne++;
                $nbv = $q1Count;
                $distRow = $trajetRepo->findDistanceById((int)$trajets[$j]);
                $kms = $distRow ? (int)$distRow['distance_destination'] : 0;
                $total_kms_ligne += $kms;
                if (!isset($total_voyage_col[$i])) :
                    $total_voyage_col[$i] = array(0);
                    $total_kms_col[$i] = array(0);
                endif;
                if (!isset($total_voyage_col[$i][$j])) :
                    $total_voyage_col[$i][$j] = 0;
                    $total_kms_col[$i][$j] = 0;
                endif;
                $total_voyage_col[$i][$j] += $q1Count;
                $total_kms_col[$i][$j] += $kms * $q1Count;
            else :
                $total_voyage_col[$i][$j] = 0;
                $total_kms_col[$i][$j] = 0;
            endif;

        endfor;
        $tableau .= "</tr>";
        $i++;
        $tableau .= "<script>getTotalLine('{$date->format('dmY')}'," . count($voyages_array) . ",$total_distance,$total_cbt_ligne)</script>";
        $total_voyages += count($voyages_array);
        $total_kms += $total_kms_ligne;
        $total_cbt += $total_cbt_ligne;
    endforeach;
    $tfoot = "";
    if ($nbTrajets > 0):
        $total_col = array();
        $total_k = array();
        for ($i = 0; $i < $nblignes; $i++) {
            if ($i == 0) {
                $total_col[$i] = 0;
                $total_k[$i] = 0;
            }
            for ($j = 0; $j < $nbTrajets; $j++) {
                if (!isset($total_col[$j])) $total_col[$j] = 0;
                if (!isset($total_k[$j])) $total_k[$j] = 0;
                $total_col[$j] += (isset($total_voyage_col[$i][$j]) ? $total_voyage_col[$i][$j] : 0);
                $total_k[$j] += (isset($total_kms_col[$i][$j]) ? $total_kms_col[$i][$j] : 0);
            }
        }
        $tfoot = "<tr style='font-weight:bold'><td colspan=2  class='text-bg-dark'>Total</td><td  class='text-bg-dark'>{$total_voyages}</td><td class='text-bg-dark'>{$total_kms}</td><td class='text-bg-dark'>{$total_cbt}</td><td class='text-bg-dark'>" . round($total_cbt / ($total_kms > 0 ? $total_kms : 1) * 100, 2) . "</td>";
        for ($i = 0; $i < $nbTrajets; $i++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . (int)$total_col[$i] . "</td>";
        $tfoot .= "</tr>";
        $tfoot .= "<tr style='font-weight:bold'><td colspan=2  class='text-bg-dark'>Total</td><td  class='text-bg-dark'>{$total_voyages}</td><td class='text-bg-dark'>{$total_kms}</td><td></td><td></td>";
        for ($i = 0; $i < $nbTrajets; $i++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . (int)$total_k[$i] . "</td>";
        $tfoot .= "</tr>";
    endif;
    $tableau .= "</tbody><tfoot>$tfoot</tfoot></table>";
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? h($_POST['date-f']) : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? h($_POST['date-t']) : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $form . "<hr>" . $tableau;
}

function getTableauEvaluationVoyages()
{
    global $con;
    $debut = date_create((isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01')));
    $fin = date_create((isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t')));
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($debut, $interval, $fin->add($interval));
    $tableau = "<table class='table table-striped no-datatable' id='table-evaluation'><thead><tr><th rowspan=2>Date</th>";
    $regionIds = array_map('intval', explode(',', $_SESSION['usr-con']['users_region']));
    $regionRepo = new RegionRepository($con);
    $objectifRepo = new ObjectifRepository($con);
    $voyageRepo = new VoyageRepository($con);
    $reg = $regionRepo->findNonAdminByIds($regionIds);
    $nb_regions = count($reg);
    foreach ($reg as $r):
        $tableau .= "<th colspan='5' style='text-align:center'>" . h($r['nom_region']) . "</th>";
    endforeach;
    $tableau .= "<th colspan='5' style='text-align:center'>Total</th>";
    $tableau .= "</tr><tr>";
    for ($i = 0; $i < $nb_regions + 1; $i++):
        $tableau .= "<th>Planifié</th><th>Réalisé</th><th>Score</th><th>Gap</th><th class='border-end'>Kms</th>";
    endfor;
    $tableau .= "</tr></thead><tbody>";
    foreach ($date_range as $date) :
        $total_plan = 0;
        $total_real = 0;
        $total_distances = 0;
        $tableau .= "<tr><td>" . $date->format('d M Y') . "</td>";
        foreach ($reg as $r):
            $regionId = (int)$r['id_region'];
            $dateStr = $date->format('Y-m-d');
            $objRows = $objectifRepo->findByDateAndRegion($dateStr, $regionId);
            $plan = 0;
            if (!empty($objRows)):
                $obj = $objRows[0];
                $tableau .= "<td>" . h($obj['objectif']) . "</td>";
                $plan = $obj['objectif'];
            else:
                $tableau .= "<td>0</td>";
            endif;
            $total_plan += $plan;
            $voyageRows = $voyageRepo->countByDateAndRegion($dateStr, $regionId);
            $real = count($voyageRows);
            $tableau .= "<td>$real</td>";
            $total_real += $real;
            $total_distance = 0;
            foreach ($voyageRows as $vr) $total_distance += $vr['total_dest'];
            $score = round($plan > 0 ? $real / $plan * 100 : 0, 1);
            $tableau .= "<td " . ($score < 100 ? 'class="text-bg-danger"' : 'class="text-bg-success"') . ">$score%</td>";
            $tableau .= "<td>" . ($plan - $real) . "</td>";
            $tableau .= "<td class='border-end'>$total_distance</td>";
            $total_distances += $total_distance;
        endforeach;
        $total_score = round($total_plan == 0 ? 0 : $total_real / $total_plan * 100, 1);
        $total_gap = $total_plan - $total_real;
        $tableau .= "<td style='font-weight:bold'>$total_plan</td><td style='font-weight:bold'>$total_real</td><td style='font-weight:bold' class='text-bg-" . ($total_score >= 100 ? 'success' : 'danger') . "'>$total_score%</td><td style='font-weight:bold'>$total_gap</td><td style='font-weight:bold'>$total_distances</td>";
        $tableau .= "</tr>";
    endforeach;
    $tableau .= "</tbody></table>";
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? h($_POST['date-f']) : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? h($_POST['date-t']) : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";

    return $form . "<hr>" . $tableau;
}
?>
<?php include('modalNewVoyage.php'); ?>
<?php /* POST handled by VoyageController — see controllers/router.php */ ?>
<?php if (isset($_GET['action']) && $_GET['action'] == 'new'): ?>
    <script>
        setTimeout(() => {
            openModalVoyage()
        }, 3000)
    </script>
<?php endif; ?>
<script>
    <?php if(in_array("upd",$rights_voyage)): ?>
    function updvg(id, vh) {
        showModalUpdateVoyage(id, vh)
    }

    function showModalUpdateVoyage(id, vh) {
        $('#modal-upd-voyage').modal('show')
        $('#id-voyage').val(id)
        $.ajax({
            type: 'post',
            data: 'id-voyage-forModal=' + id + '&id-vh-forModal=' + vh,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                let v = e.data
                $('#nom-voyage-display').html(v.titre_voyage);
                $('#nom-upd-voyage').val(v.titre_voyage);
                $('#date-upd-voyage').val(v.date_voyage)
                $('#id-upd-vh-voyage option').each((e, el) => {
                    if ($(el).attr('value') != vh) $(el).remove()
                })
            $('#cv-upd-voyage').val(v.convoyeur)
            $('#cb-upd-voyage').val(v.qte_carburant)
            $('#tc-upd-voyage option[value="'+v.tc+'"]').prop('selected',true)
            $('#qtec-upd-voyage').val(v.qte_chargement)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function updateVoyage($id) {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-voyage').serialize(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    <?php if(isset($_POST['date-f'])):
                    echo "$('body').append('<form method=\"post\" action=\"#\" id=\"form-reload-after-upd\"><input type=\"hidden\" name=\"date-f\" value=" . j($_POST['date-f']) . "><input type=\"hidden\" name=\"date-t\" value=" . j($_POST['date-t']) . "></form>');$('#form-reload-after-upd').submit();";
                    else : ?>
                    location = "?page=voyages"
                    <?php endif; ?>
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }
    <?php endif; ?>
    <?php if(in_array("del",$rights_voyage)): ?>
    function delvg(id) {
        if (confirm("Etes-vous sûr de vouloir supprimer?")) {
            $.ajax({
                type: 'post',
                data: 'id-voyage-forDel=' + id,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Voyage supprimée!!')
                    location.reload()
                } else {
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }
    <?php endif; ?>
</script>
<?php if(in_array("upd",$rights_voyage)): ?>
<div class="modal fade" id="modal-upd-voyage" tabindex="-1" aria-labelledby="modal-upd-voyageLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-voyageLabel">Voyage de véhicule <span id='nom-voyage-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-voyage" class="row">
                    <div class="col-12">
                        <div class="form-floating mb-3">
                            <input type="hidden" id="id-voyage" name="id-voyage">
                            <input type="text" id="nom-upd-voyage" name="nom-upd-voyage" required class="form-control" readonly>
                            <label for="nom-upd-voyage">Titre voyage</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-upd-voyage" name="date-upd-voyage" required class="form-control">
                            <label for="date-upd-voyage">Date du voyage</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="id-upd-vh-voyage" name="id-upd-vh-voyage">
                                <?php $affRepo = new AffectationRepository($con);
                                foreach ($affRepo->findActiveByRegion((int)$_SESSION['usr-con']['region-sel']) as $r):
                                    echo "<option value='" . $r['id_affectation'] . "'>" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>
                            <label for="id-upd-vh-voyage">Véhicule</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="cv-upd-voyage" name="cv-upd-voyage" class="form-control">
                            <label for="cv-upd-voyage">Convoyeur</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="cb-upd-voyage" name="cb-upd-voyage" class="form-control" min="0" required>
                            <label for="cb-upd-voyage">Carburant consommé (en Litres)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="tc-upd-voyage" name="tc-upd-voyage" required>
                            <?php $voyageRepo = new VoyageRepository($con);
                                    foreach ($voyageRepo->findAllTypesChargement() as $r):
                                        echo "<option value='" . $r['id_type_chargement'] . "' val-min='" . h($r['valeur_min']) . "' val-max='" . h($r['valeur_max']) . "'>" . h($r['lib_type_chargement']) . "</option>";
                                    endforeach;
                                    ?>
                            </select>
                            <label for="tc-upd-voyage">Type de chargement</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="qtec-upd-voyage" name="qtec-upd-voyage" required min="0" class="form-control">
                            <label for="qtec-upd-voyage">Qté chargement</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateVoyage()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>