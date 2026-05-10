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
    set_time_limit(120);
    global $con;
    global $rights_voyage;
    $hasUpd = in_array('upd', $rights_voyage);
    $hasDel = in_array('del', $rights_voyage);

    try {
        $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
        $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

        // Build date list as plain arrays (avoid DatePeriod re-iteration issues)
        $dates = [];
        $dateCols = [];
        $d = date_create($dateFrom);
        $end = date_create($dateTo);
        $end->modify('+1 day');
        while ($d < $end) {
            $dates[] = $d->format('Y-m-d');
            $dateCols[] = $d->format('d M Y');
            $d->modify('+1 day');
        }
        $jourCount = count($dates);

        $vehiculeRepo = new VehiculeRepository($con);
        $voyageRepo = new VoyageRepository($con);

        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $activeVehicles = $vehiculeRepo->findActiveByContext($regionIds, $entiteIds);

        // Single batch query instead of N vehicles × M days queries
        $allVoyages = $voyageRepo->findBatchByDateRange($regionIds, $entiteIds, $dateFrom, $dateTo);

        // Index voyages by [vehicle_id][date] → list of rows
        $byVehicle = [];
        foreach ($allVoyages as $v) {
            $vid = (int)$v['id_vehicule'];
            $d = $v['date_voyage'];
            if (!isset($byVehicle[$vid])) $byVehicle[$vid] = [];
            if (!isset($byVehicle[$vid][$d])) $byVehicle[$vid][$d] = [];
            $byVehicle[$vid][$d][] = $v;
        }

        $debug = '<!-- DEBUG: ' . count($activeVehicles) . ' vehicules, ' . count($allVoyages) . ' voyages, ' . $jourCount . ' jours, 2 requetes -->';
    } catch (\Throwable $e) {
        return '<!-- ERREUR getTableauVoyages: ' . h($e->getMessage()) . ' -->';
    }

    $tableau = "<table class='table table-striped'><thead><tr><th>Immatriculation</th>";
    foreach ($dateCols as $col) {
        $tableau .= "<th>$col</th>";
    }
    $tableau .= "<th># Voyages</th><th># Kms</th><th>Chargement</th><th>Carburant</th></tr></thead><tbody>";

    foreach ($activeVehicles as $r):
        $vid = (int)$r['id_vehicule'];
        $tableau .= "<tr><td class='text-bg-dark'>" . h($r['immatriculation_vehicule']) . "</td>";
        $total_voyages = 0;
        $total_kms = 0;
        $total_chargement = 0;
        $total_carburant = 0;

        foreach ($dates as $d):
            $voyages = $byVehicle[$vid][$d] ?? [];
            $cell = "<td><ul class='list-group'>";
            if (empty($voyages)) {
                $cell .= "</ul></td>";
            }
            $cpte = 0;
            foreach ($voyages as $r1):
                $vgHash = $r1['id_voyage'];
                $affHash = $r1['id_affectation'];
                $sep = $cpte > 0 ? "<span class='text-white' style='font-size:5px'>|==></span>" : '';
                $btns = '';
                if ($hasUpd) $btns .= "<button class='btn btn-light btn-sm' onclick='updvg(\"$vgHash\",\"$affHash\")'><i class='fa fa-pencil-alt'></i></button>";
                if ($hasDel) $btns .= "<button class='btn btn-danger btn-sm' onclick='delvg(\"$vgHash\")'><i class='fa fa-times'></i></button>";
                $cell .= "<li class='list-group-item'>" . $sep
                    . h($r1['lib_destination']) . " - " . h($r1['distance_destination']) . "km - "
                    . h($r1['qte_carburant']) . "L - " . h($r1['qte_chargement'])
                    . " (" . h($r1['lib_type_chargement']) . ") "
                    . "<div class='btn-group'>$btns</div></li>";
                $total_voyages++;
                $total_kms += $r1['distance_destination'];
                $total_carburant += $r1['qte_carburant'];
                $total_chargement += $r1['qte_chargement'];
                $cpte++;
            endforeach;
            if (!empty($voyages)) $cell .= "</ul></td>";
            $tableau .= $cell;
        endforeach;

        $tableau .= "<td class='text-bg-dark'>$total_voyages</td><td class='text-bg-dark'>$total_kms</td><td class='text-bg-dark'>$total_chargement</td><td class='text-bg-dark'>$total_carburant</td></tr>";
    endforeach;
    $tableau .= "</tbody></table>";

    $fDateFrom = isset($_POST['date-f']) ? h($_POST['date-f']) : date('Y-m-01');
    $fDateTo   = isset($_POST['date-t']) ? h($_POST['date-t']) : date('Y-m-t');
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='$fDateFrom'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='$fDateTo'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $debug . $form . "<hr>" . $tableau;
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
                $total_kms_col[$i][$j] = 0;
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
        var valid = true
        $('#form-upd-voyage *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            showError('Tous les champs en rouge sont obligatoires!!!')
            return false
        }
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
                        <div class="mb-3">

                            <label for="id-upd-vh-voyage">Véhicule</label>

                            <select id="id-upd-vh-voyage" name="id-upd-vh-voyage" required>
                                <?php $affRepo = new AffectationRepository($con);
                                foreach ($affRepo->findActiveByContext(getContextRegions(), getContextEntities()) as $r):
                                    echo "<option value='" . $r['id_affectation'] . "'>" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>

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
                        <div class="mb-3">

                            <label for="tc-upd-voyage">Type de chargement</label>

                            <select id="tc-upd-voyage" name="tc-upd-voyage" required>
                            <?php $voyageRepo = new VoyageRepository($con);
                                    foreach ($voyageRepo->findAllTypesChargement() as $r):
                                        echo "<option value='" . $r['id_type_chargement'] . "' val-min='" . h($r['valeur_min']) . "' val-max='" . h($r['valeur_max']) . "'>" . h($r['lib_type_chargement']) . "</option>";
                                    endforeach;
                                    ?>
                            </select>

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

<?php
function getDashboardCardsVoyages()
{
    global $con;
    $repo = new VoyageRepository($con);
    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();

    $voyagesMois = $repo->countVoyagesThisMonth($regionIds, $entiteIds);
    $taux = $repo->tauxRealisation($regionIds, $entiteIds);
    $kmMois = $repo->sumKmThisMonth($regionIds, $entiteIds);
    $vehicules = $repo->countActiveVehicles($regionIds, $entiteIds);
    $conso = $repo->avgConsumption($regionIds, $entiteIds);

    $tauxClass = $taux >= 100 ? 'lt-stat-success' : ($taux >= 80 ? 'lt-stat-warning' : 'lt-stat-danger');
    $consoClass = $conso === null ? '' : ($conso <= 15 ? 'lt-stat-success' : ($conso <= 25 ? 'lt-stat-warning' : 'lt-stat-danger'));

    $html = '<div class="row g-3 mb-3">';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-road"></i></div>';
    $html .= '<div class="lt-stat-value">' . number_format($voyagesMois, 0, ',', ' ') . '</div>';
    $html .= '<div class="lt-stat-label">Voyages du mois</div>';
    $html .= '</div></div>';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card ' . $tauxClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-bullseye"></i></div>';
    $html .= '<div class="lt-stat-value">' . $taux . ' %</div>';
    $html .= '<div class="lt-stat-label">Taux réalisation objectifs</div>';
    $html .= '</div></div>';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-tachometer-alt"></i></div>';
    $html .= '<div class="lt-stat-value">' . number_format($kmMois, 0, ',', ' ') . ' km</div>';
    $html .= '<div class="lt-stat-label">Km parcourus du mois</div>';
    $html .= '</div></div>';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-truck"></i></div>';
    $html .= '<div class="lt-stat-value">' . $vehicules['actifs'] . ' / ' . $vehicules['total'] . '</div>';
    $html .= '<div class="lt-stat-label">Véhicules actifs</div>';
    $html .= '</div></div>';

    $consoDisplay = $conso !== null ? number_format($conso, 1, ',', '') . ' L/100km' : '—';
    $html .= '<div class="col-md"><div class="lt-card lt-stat-card ' . $consoClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-gas-pump"></i></div>';
    $html .= '<div class="lt-stat-value">' . $consoDisplay . '</div>';
    $html .= '<div class="lt-stat-label">Conso moyenne (mois)</div>';
    $html .= '</div></div>';

    $html .= '</div>';
    return $html;
}

function getDashboardChartsVoyages()
{
    $html = '<div class="row g-3 mb-3">';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Voyages vs Objectifs (30 jours)</h2></div>';
    $html .= '<div id="chart-voyages-vs-obj" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Top destinations</h2></div>';
    $html .= '<div id="chart-top-dest" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-12"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Consommation par véhicule (mois en cours)</h2></div>';
    $html .= '<div id="chart-conso" style="height: 400px;"></div></div></div>';
    $html .= '</div>';

    $html .= '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Véhicules inactifs (7+ jours)</h2></div>';
    $html .= '<table id="table-inactifs" class="table table-striped no-datatable"><thead><tr>
        <th>Véhicule</th><th>Chauffeur</th><th>Dernier voyage</th></tr></thead><tbody></tbody></table></div>';

    $html .= '<script>
    google.charts.load("current", {packages: ["corechart", "table"]});
    google.charts.setOnLoadCallback(function() {
        $.ajax({type:"post", data:"load-voyages-vs-obj=1&days=30", dataType:"json"})
        .done(function(e) {
            if (!e.data || !e.data.length) return;
            var dt = new google.visualization.DataTable();
            dt.addColumn("string", "Date");
            dt.addColumn("number", "Voyages");
            dt.addColumn("number", "Objectif");
            e.data.forEach(function(r) { dt.addRow([r.date, r.voyages, r.objectif]); });
            var c = new google.visualization.LineChart(document.getElementById("chart-voyages-vs-obj"));
            c.draw(dt, {title:"Voyages vs Objectifs journaliers", curveType:"function", legend:{position:"bottom"}, colors:["#5D54A4","#E74C3C"], chartArea:{width:"85%", height:"75%"}});
        });
        $.ajax({type:"post", data:"load-top-destinations=1&limit=10", dataType:"json"})
        .done(function(e) {
            if (!e.data || !e.data.length) return;
            var dt = new google.visualization.DataTable();
            dt.addColumn("string", "Destination");
            dt.addColumn("number", "Nb voyages");
            dt.addColumn("number", "Km total");
            e.data.forEach(function(r) { dt.addRow([r.lib_destination, parseInt(r.nb_voyages), parseFloat(r.total_km)]); });
            var c = new google.visualization.ColumnChart(document.getElementById("chart-top-dest"));
            c.draw(dt, {title:"Top destinations", colors:["#5D54A4","#7C78B8"], chartArea:{width:"80%", height:"70%"}});
        });
        $.ajax({type:"post", data:"load-conso-per-vehicle=1", dataType:"json"})
        .done(function(e) {
            if (!e.data || !e.data.length) return;
            var dt = new google.visualization.DataTable();
            dt.addColumn("string", "Véhicule");
            dt.addColumn("number", "Conso L/100km");
            e.data.forEach(function(r) {
                var conso = r.total_km > 0 ? parseFloat(r.total_carburant) / parseFloat(r.total_km) * 100 : 0;
                dt.addRow([r.immatriculation_vehicule, conso]);
            });
            dt.sort([{column:1, desc:true}]);
            var c = new google.visualization.ColumnChart(document.getElementById("chart-conso"));
            c.draw(dt, {title:"Conso L/100km (mois en cours)", legend:"none", colors:["#E67E22"], chartArea:{width:"80%", height:"70%"}});
        });
        $.ajax({type:"post", data:"load-vehicules-inactifs=1&days=7", dataType:"json"})
        .done(function(e) {
            if (!e.data) return;
            var tbody = $("#table-inactifs tbody");
            tbody.empty();
            if (!e.data.length) { tbody.append("<tr><td colspan=\"3\" class=\"text-center\">Aucun véhicule inactif</td></tr>"); return; }
            e.data.forEach(function(r) {
                tbody.append("<tr><td>" + r.immatriculation_vehicule + "</td><td>" + r.nom_chauffeur + "</td><td>" + (r.derniere_date_voyage || "—") + "</td></tr>");
            });
            $("#table-inactifs").DataTable({order:[[2,"asc"]], pageLength:25, destroy:true});
        });
    });
    </script>';

    return $html;
}

function getAnomaliesVoyages()
{
    global $con;
    $repo = new VoyageRepository($con);
    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $rows = $repo->anomaliesObjectifs(30, $regionIds, $entiteIds);

    if (!count($rows)) return '';

    $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Jours avec faible activité (taux < 50%)</h2></div>';
    $html .= '<table id="table-anomalies-voyages" class="table table-striped no-datatable"><thead><tr>
        <th>Date</th><th>Voyages réalisés</th><th>Objectif</th><th>Taux</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $taux = $r['taux'];
        $badgeClass = $taux < 30 ? 'lt-badge-danger' : 'lt-badge-warning';
        $html .= '<tr>
            <td>' . date('d/m/Y', strtotime($r['date'])) . '</td>
            <td>' . $r['voyages'] . '</td>
            <td>' . $r['objectif'] . '</td>
            <td><span class="lt-badge ' . $badgeClass . '">' . $taux . ' %</span></td></tr>';
    }
    $html .= '</tbody></table></div>';
    $html .= '<script>$("#table-anomalies-voyages").DataTable({order:[[3,"asc"]], pageLength:15, destroy:true});</script>';
    return $html;
}

function getScoreActivite()
{
    global $con;
    $repo = new VoyageRepository($con);
    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $dateFrom = date('Y-m-d', strtotime('-30 days'));
    $dateTo = date('Y-m-d');
    $rows = $repo->vehicleActivityScores($regionIds, $entiteIds, $dateFrom, $dateTo);

    if (!count($rows)) return '<div class="alert alert-info">Aucun véhicule actif trouvé.</div>';

    $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Score d\'activité des véhicules (30 jours)</h2></div>';
    $html .= '<table id="table-score-activite" class="table table-striped no-datatable"><thead><tr>
        <th>Véhicule</th><th>Chauffeur</th><th>Jours actifs</th><th>Km</th><th>Conso L/100km</th>
        <th>Régularité</th><th>Contribution</th><th>Conso</th><th>Score</th><th>État</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $score = $r['score'];
        $color = $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger');
        $etat = $score >= 70 ? 'Bon' : ($score >= 40 ? 'Moyen' : 'Faible');
        $html .= '<tr>
            <td>' . h($r['immatriculation_vehicule']) . '</td>
            <td>' . h($r['nom_chauffeur']) . '</td>
            <td>' . $r['jours_avec_voyage'] . '</td>
            <td>' . number_format($r['total_km'], 0, ',', ' ') . '</td>
            <td>' . ($r['conso_100km'] !== null ? number_format($r['conso_100km'], 1, ',', '') : '—') . '</td>
            <td>' . $r['regularite'] . '/40</td>
            <td>' . $r['contribution'] . '/30</td>
            <td>' . $r['score_conso'] . '/30</td>
            <td><span class="lt-badge lt-badge-' . $color . '">' . $score . '/100</span></td>
            <td><span class="text-' . $color . ' fw-bold">' . $etat . '</span></td></tr>';
    }
    $html .= '</tbody></table></div>';
    $html .= '<script>$("#table-score-activite").DataTable({order:[[8,"asc"]], pageLength:25, destroy:true});</script>';
    return $html;
}

function getProjectionMois()
{
    global $con;
    $repo = new VoyageRepository($con);
    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $p = $repo->projectionFinMois($regionIds, $entiteIds);

    $pct = min($p['taux_projection'], 100);
    $barColor = $pct >= 100 ? 'bg-success' : ($pct >= 75 ? 'bg-info' : ($pct >= 50 ? 'bg-warning' : 'bg-danger'));

    $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Projection fin de mois</h2></div>';
    $html .= '<div class="p-3">';
    $html .= '<div class="row g-3 mb-3">';
    $html .= '<div class="col-md-3"><div class="text-muted small">Voyages réalisés</div><div class="fs-4 fw-bold">' . number_format($p['realise'], 0, ',', ' ') . '</div></div>';
    $html .= '<div class="col-md-3"><div class="text-muted small">Objectif mensuel</div><div class="fs-4 fw-bold">' . number_format($p['objectif_total'], 0, ',', ' ') . '</div></div>';
    $html .= '<div class="col-md-3"><div class="text-muted small">Rythme / jour</div><div class="fs-4 fw-bold">' . $p['rythme_jour'] . '</div></div>';
    $html .= '<div class="col-md-3"><div class="text-muted small">Projection</div><div class="fs-4 fw-bold">' . number_format($p['projection'], 0, ',', ' ') . '</div></div>';
    $html .= '</div>';
    $html .= '<div class="d-flex align-items-center gap-2 mb-1"><span class="small">J-' . $p['jours_ecoules'] . ' / ' . $p['jours_total'] . '</span><span class="small ms-auto fw-bold">' . $p['taux_projection'] . ' %</span></div>';
    $html .= '<div class="progress" style="height:20px"><div class="progress-bar ' . $barColor . '" style="width:' . $pct . '%"></div></div>';
    $html .= '</div></div>';
    return $html;
}
?>