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
    $q = mysqli_query($con, "select * from vehicule left join affectation_vehicule on affectation_vehicule.id_vehicule=vehicule.id_vehicule where id_region={$_SESSION['usr-con']['region-sel']} and is_ferme=0");
    $tableau = "<table class='table table-striped'><thead><tr><th>Immatriculation</th>";
    foreach ($date_range as $date) :
        $tableau .= "<th>{$date->format('d M Y')}</th>";
    endforeach;
    $tableau .= "<th># Voyages</th><th># Kms</th><th>Chargement</th><th>Carburant</th></tr></thead><tbody>";
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td class='text-bg-dark'>{$r['immatriculation_vehicule']}</td>";
        $voyage = "";
        $total_voyages = 0;
        $total_kms = 0;
        $total_chargement = 0;
        $total_carburant = 0;
        foreach ($date_range as $date) :
            //echo "select * from voyage,voyage_vehicule where voyage.id_voyage=voyage_vehicule.id_voyage and id_affectation=(select id_affectation from affectation_vehicule where id_vehicule={$r[0]} and is_ferme=0 order by date_affectation limit 1) and date_voyage='{$date->format('Y-m-d')}'";
            $q1 = mysqli_query($con, "select * from voyage,voyage_vehicule,destination_voyage,type_chargement_voyage where voyage.id_voyage=voyage_vehicule.id_voyage and destination_voyage.id_destination=voyage_vehicule.id_destination and voyage.id_type_chargement=type_chargement_voyage.id_type_chargement and id_affectation=(select id_affectation from affectation_vehicule where id_vehicule={$r[0]} and is_ferme=0 order by date_affectation limit 1) and date_voyage='{$date->format('Y-m-d')}'");
            $voyage .= "<td><ul class='list-group'>";
            if (mysqli_num_rows($q1) == 0) $voyage .= "</ul></td>";
            $cpte = 0;
            while ($r1 = mysqli_fetch_array($q1)):
                $voyage .= "<li class='list-group-item'>" . ($cpte > 0 ? "<span class='text-white' style='font-size:5px'>|==></span>" : "") . "{$r1['lib_destination']} - {$r1['distance_destination']}km - {$r1['qte_carburant']}L - {$r1['qte_chargement']} ({$r1['lib_type_chargement']}) <div class='btn-group'>".(in_array('upd',$rights_voyage) ? "<button class='btn btn-light btn-sm' onclick='updvg(\"" . sha1($r1[0] . $r1['titre_voyage']) . "\",\"" . sha1($r1['id_affectation'] . $r['id_vehicule']) . "\")'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array("del",$rights_voyage) ? "<button class='btn btn-danger btn-sm' onclick='delvg(\"" . sha1($r1[0] . $r1['titre_voyage']) . "\")'><i class='fa fa-times'></i></button>" : "")."</div></li>";
                $total_voyages++;
                $total_kms += $r1['distance_destination'];
                $total_carburant += $r1['qte_carburant'];
                $total_chargement += $r1['qte_chargement'];
                $cpte++;
            endwhile;
            if (mysqli_num_rows($q1) > 0) $voyage .= "</ul></td>";
        endforeach;
        $tableau .= $voyage;
        $tableau .= "<td class='text-bg-dark'>$total_voyages</td><td class='text-bg-dark'>$total_kms</td><td class='text-bg-dark'>$total_chargement</td><td class='text-bg-dark'>$total_carburant</td></tr>";
    endwhile;
    $tableau .= "</tbody></table>";
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $form . "<hr>" . $tableau;
}
function getTableauVoyagesVehicules()
{
    global $con;
    $q = mysqli_query($con, "select *,(select nom_chauffeur from chauffeur where id_chauffeur=(select id_chauffeur from affectation_vehicule where id_vehicule=vehicule.id_vehicule and is_ferme=0 limit 1)) as n_chauffeur from vehicule  where 1 ");
    $nblignes = mysqli_num_rows($q);
    $tableau = "<table class='table table-striped'><thead><tr><th>#</th><th>Immatriculation</th><th># Voyages</th><th># Kms</th><th>Carburant (en L)</th><th>Conso. 100km</th>";
    $q1 = mysqli_query($con, "select * from destination_voyage where 1");
    $nbTrajets = mysqli_num_rows($q1);
    $trajets = array();
    while ($r = mysqli_fetch_array($q1)):
        $tableau .= "<th>{$r['lib_destination']}</th>";
        array_push($trajets, $r[0]);
    endwhile;
    $tableau .= "</tr></thead><tbody>";
    $i = 1;
    $total_voyages = 0;
    $total_kms = 0;
    $total_cbt = 0;
    $total_voyage_col = array();
    $total_kms_col = array();
    $total_distance = 0;
    $voyages_array = array();
    $k = 0;
    $total_cbt_ligne = 0;
    while ($r = mysqli_fetch_array($q)):
        $voyages_array = array();
        $v_arr = array();
        $total_voyage_ligne = 0;
        $kms = 0;
        $total_kms_ligne = 0;
        $total_distance = 0;
        $total_cbt_ligne = 0;
        $tableau .= "<tr><td>$i</td><td>{$r['immatriculation_vehicule']} - {$r['n_chauffeur']}</td><td><span id='total_vg_ln_{$r[0]}'></span></td><td><span id='total_kms_ln_{$r[0]}'></span></td><td><span id='total_cbt_ln_{$r[0]}'></span></td><td><span id='total_cbt_100_ln_{$r[0]}'></span></td>";
        for ($j = 0; $j < count($trajets); $j++):
            //if($k==0) echo  "select id_voyage_vehicule,id_voyage,(select distance_destination from destination_voyage where id_destination={$trajets[$j]}) as dist_trajet from voyage_vehicule where id_destination={$trajets[$j]} and id_voyage in(select id_voyage from voyage where id_affectation in(select id_affectation from affectation_vehicule where id_vehicule={$r[0]})" . (isset($_POST['date-f']) ? " and date_voyage between '{$_POST['date-f']}' and '{$_POST['date-t']}'" : "") . ")";
            $v_arr[$trajets[$j]] = array();
            $q1 = mysqli_query($con, "select id_voyage_vehicule,voyage_vehicule.id_voyage,voyage.*,(select distance_destination from destination_voyage where id_destination={$trajets[$j]}) as dist_trajet from voyage_vehicule left join voyage on voyage.id_voyage=voyage_vehicule.id_voyage where id_destination={$trajets[$j]} and voyage.id_voyage in(select id_voyage from voyage where id_affectation in(select id_affectation from affectation_vehicule where id_vehicule={$r[0]})" . (isset($_POST['date-f']) ? " and date_voyage between '{$_POST['date-f']}' and '{$_POST['date-t']}'" : " and date_voyage between '" . date('Y-m-01') . "' and '" . date('Y-m-t') . "'") . ")");
            //$total_distance=0;
            while ($r1 = mysqli_fetch_array($q1)) :
                $total_distance += $r1['dist_trajet'];
                $total_cbt_ligne += $r1['qte_carburant'];
                if (!in_array($r1['id_voyage'], $v_arr[$trajets[$j]])) : array_push($v_arr[$trajets[$j]], $r1['id_voyage']);
                endif;
                //if(!in_array($r1['id_voyage'],$v_arr) && !in_array($r1['id_voyage'],$voyages_array)) : array_push($v_arr,$r1['id_voyage']); endif;
                if (!in_array($r1['id_voyage'], $voyages_array)) : array_push($voyages_array, $r1['id_voyage']);
                endif;
            endwhile;
            //if($k==0) print_r($v_arr);
            $tableau .= "<td " . (mysqli_num_rows($q1) > 0 ? "class='text-bg-success' style='background-color:#198754;color:white'>" . count($v_arr[$trajets[$j]]) : " class='text-bg-info' style='background-color:#0dcaf0;'>") . "</td>";

            if (mysqli_num_rows($q1) > 0):
                $total_voyage_ligne++;
                $q1 = mysqli_query($con, "select distance_destination from destination_voyage where id_destination={$trajets[$j]}");
                $kms = 0;
                while ($r1 = mysqli_fetch_array($q1)) $kms += $r1[0];
                $total_kms_ligne  = $total_distance;
                if (!isset($total_voyage_col[$i][$j])) $total_voyage_col[$i][$j] = 0;
                $total_voyage_col[$i][$j] += mysqli_num_rows($q1);
                if (!isset($total_kms_col[$i][$j])) $total_kms_col[$i][$j] = 0;
                $total_kms_col[$i][$j] += $kms * mysqli_num_rows($q1);
            else :
                $total_voyage_col[$i][$j] = 0;
                $total_kms_col[$j][$j] = 0;
            endif;


        endfor;
        $tableau .= "</tr>";
        $i++;
        $tableau .= "<script>getTotalLine({$r[0]}," . count($voyages_array) . ",$total_kms_ligne,$total_cbt_ligne)</script>";
        //$total_voyages += $total_voyage_ligne;
        $total_voyages += count($voyages_array);
        $total_kms += $total_kms_ligne;
        $total_cbt += $total_cbt_ligne;
        $k++;
    endwhile;
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
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $form . "<hr>" . $tableau;
}
function getTableauVoyagesPeriodes()
{
    /* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */
    global $con;
    $debut = date_create((isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-d', strtotime(date('Y-m-01')))));
    $fin = date_create((isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-d', strtotime(date('Y-m-t')))));
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($debut, $interval, $fin->add($interval));
    $nblignes = ((int)$fin->diff($debut)->format('%a')) + 1;
    $tableau = "<table class='table table-striped'><thead><tr><th>#</th><th>Date</th><th># Voyages</th><th># Kms</th><th>Carburant (en L)</th><th>Conso. 100km</th>";
    $q1 = mysqli_query($con, "select * from destination_voyage");
    $nbTrajets = mysqli_num_rows($q1);
    $trajets = array();
    while ($r = mysqli_fetch_array($q1)):
        $tableau .= "<th>{$r['lib_destination']}</th>";
        array_push($trajets, $r[0]);
    endwhile;
    $tableau .= "</tr></thead><tbody>";
    $i = 1;
    $total_voyages = 0;
    $total_kms = 0;
    $total_cbt = 0;
    $total_voyage_col = array();
    $total_kms_col = array();
    $total_distance = 0;
    $voyages_array = array();
    $total_cbt_ligne = 0;
    foreach ($date_range as $date) :
        $total_voyage_ligne = 0;
        $kms = 0;
        $total_kms_ligne = 0;
        $total_distance = 0;
        $voyages_array = array();
        $total_cbt_ligne = 0;
        $tableau .= "<tr><td>$i</td><td>" . $date->format('d M Y') . "</td><td><span id='total_vg_ln_{$date->format('dmY')}'></span></td><td><span id='total_kms_ln_{$date->format('dmY')}'></span></td><td><span id='total_cbt_ln_{$date->format('dmY')}'></span></td><td><span id='total_cbt_100_ln_{$date->format('dmY')}'></span></td>";
        for ($j = 0; $j < count($trajets); $j++):
            $q1 = mysqli_query($con, "select id_voyage_vehicule,voyage_vehicule.id_voyage,voyage.*,(select distance_destination from destination_voyage where id_destination={$trajets[$j]}) as dist_trajet from voyage_vehicule left join voyage on voyage.id_voyage=voyage_vehicule.id_voyage where id_destination={$trajets[$j]} and voyage_vehicule.id_voyage in(select id_voyage from voyage where date_voyage ='{$date->format('Y-m-d')}')");
            while ($r1 = mysqli_fetch_array($q1)) :
                $total_distance += $r1['dist_trajet'];
                $total_cbt_ligne += $r1['qte_carburant'];
                if (!in_array($r1['id_voyage'], $voyages_array)) : array_push($voyages_array, $r1['id_voyage']);
                endif;
            endwhile;
            $tableau .= "<td " . (mysqli_num_rows($q1) > 0 ? "class='text-bg-success' style='background-color:#198754;color:white'>" . mysqli_num_rows($q1) : " class='text-bg-info' style='background-color:#0dcaf0;'>") . "</td>";

            if (mysqli_num_rows($q1) > 0):
                $total_voyage_ligne++;
                $nbv = mysqli_num_rows($q1);
                $q1 = mysqli_query($con, "select distance_destination from destination_voyage where id_destination={$trajets[$j]}");
                while ($r1 = mysqli_fetch_array($q1)) $kms = $r1[0] * $nbv;
                $total_kms_ligne += $kms;
                if (!isset($total_voyage_col[$i])) :
                    $total_voyage_col[$i] = array(0);
                    $total_kms_col[$i] = array(0);
                endif;
                if (!isset($total_voyage_col[$i][$j])) :
                    $total_voyage_col[$i][$j] = 0;
                    $total_kms_col[$i][$j] = 0;
                endif;
                $total_voyage_col[$i][$j] += mysqli_num_rows($q1);
                $total_kms_col[$i][$j] += $kms * mysqli_num_rows($q1);
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
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";
    return $form . "<hr>" . $tableau;
}

function getTableauEvaluationVoyages()
{
    global $con;
    $debut = date_create((isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01')));
    $fin = date_create((isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t')));
    $interval = new DateInterval('P1D');
    $date_range = new DatePeriod($debut, $interval, $fin->add($interval));
    $nblignes = ((int)$fin->diff($debut)->format('%a')) + 1;
    $tableau = "<table class='table table-striped no-datatable' id='table-evaluation'><thead><tr><th rowspan=2>Date</th>";
    $regions = explode(",", $_SESSION['usr-con']['users_region']);
    $q = mysqli_query($con, "select * from region where is_admin<1 and id_region in({$_SESSION['usr-con']['users_region']})");
    $nb_regions = mysqli_num_rows($q);
    $reg = array();
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<th colspan='5' style='text-align:center'>{$r[1]}</th>";
        array_push($reg, $r);
    endwhile;
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
        $tableau .= "<tr><td>{$date->format('d M Y')}</td>";
        for ($i = 0; $i < count($reg); $i++):
            $q = mysqli_query($con, "select * from objectif_periode_region where date_objectif_periode='{$date->format('Y-m-d')}' and id_region={$reg[$i][0]}");
            $plan = 0;
            while ($r = mysqli_fetch_array($q)):
                $tableau .= "<td>{$r['objectif']}</td>";
                $plan = $r['objectif'];
            endwhile;
            if (mysqli_num_rows($q) == 0):
                $tableau .= "<td>0</td>";
            endif;
            $total_plan += $plan;
            $q = mysqli_query($con, "select *,(select sum(distance_destination) from destination_voyage where id_destination in(select id_destination from voyage_vehicule where id_voyage=voyage.id_voyage)) as total_dest from voyage where date_voyage='{$date->format('Y-m-d')}' and id_voyage in(select id_voyage from voyage_vehicule where id_affectation in(select id_affectation from affectation_vehicule where id_region={$reg[$i][0]}))");
            $tableau .= "<td>" . mysqli_num_rows($q) . "</td>";
            $real = mysqli_num_rows($q);
            $total_real += $real;
            $total_distance = 0;
            while ($r = mysqli_fetch_array($q)) $total_distance += $r['total_dest'];
            $score = round($plan > 0 ? $real / $plan * 100 : 0, 1);
            $tableau .= "<td " . ($score < 100 ? 'class="text-bg-danger"' : 'class="text-bg-success"') . ">$score%</td>";
            $tableau .= "<td>" . ($plan - $real) . "</td>";
            $tableau .= "<td class='border-end'>$total_distance</td>";
            $total_distances += $total_distance;
        endfor;
        $total_score = round($total_plan == 0 ? 0 : $total_real / $total_plan * 100, 1);
        $total_gap = $total_plan - $total_real;
        $tableau .= "<td style='font-weight:bold'>$total_plan</td><td style='font-weight:bold'>$total_real</td><td style='font-weight:bold' class='text-bg-" . ($total_score >= 100 ? 'success' : 'danger') . "'>$total_score%</td><td style='font-weight:bold'>$total_gap</td><td style='font-weight:bold'>$total_distances</td>";
        $tableau .= "</tr>";
    endforeach;
    $tableau .= "</tbody></table>";
    $form = "<form method='post' action='#' class='row'><div class='col-4'><div class='form-floating'><input type='date' id='date-f' name='date-f' class='form-control' value='" . (isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01')) . "'><label for='date-f'>Date départ</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='" . (isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t')) . "'><label for='date-t'>Date fin</label></div></div><div class='col-4' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form>";

    return $form . "<hr>" . $tableau;
}
?>
<?php include('modalNewVoyage.php'); ?>
<?php if (isset($_POST['id-voyage-forModal'])):
   $q = mysqli_query($con, "select *,(select id_vehicule from vehicule where sha1(concat(voyage.id_affectation,id_vehicule))='{$_POST['id-vh-forModal']}') as vh,sha1(concat(type_chargement_voyage.id_type_chargement,lib_type_chargement)) as tc from voyage,voyage_vehicule,destination_voyage,type_chargement_voyage where voyage.id_voyage=voyage_vehicule.id_voyage and destination_voyage.id_destination=voyage_vehicule.id_destination and voyage.id_type_chargement=type_chargement_voyage.id_type_chargement and sha1(concat(voyage.id_voyage,titre_voyage))='{$_POST['id-voyage-forModal']}'");
    while ($r = mysqli_fetch_array($q)):
        $voyage = $r;
    endwhile;
    die("UpdVoyage%%%%%%" . json_encode($voyage));
endif;
if (isset($_POST['id-voyage'])):
    $_POST['nom-upd-voyage'] = trim(strtoupper($_POST['nom-upd-voyage']));
    $keys = array_keys($_POST);
    for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = $_POST[$keys[$i]] == '' ? '' : mysqli_real_escape_string($con, $_POST[$keys[$i]]);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "update voyage set date_voyage='{$_POST['date-upd-voyage']}',qte_carburant='{$_POST['cb-upd-voyage']}',convoyeur='{$_POST['cv-upd-voyage']}',id_type_chargement=(select id_type_chargement from type_chargement_voyage where sha1(concat(id_type_chargement,lib_type_chargement))='{$_POST['tc-upd-voyage']}'),qte_chargement='{$_POST['qtec-upd-voyage']}' where sha1(concat(id_voyage,titre_voyage))='{$_POST['id-voyage']}'");
        mysqli_commit($con);
        die("UpdVoyage%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdVoyage%%%%%%0");
    }
endif;
if (isset($_POST['id-voyage-forDel'])):
    $q = mysqli_query($con, "delete from voyage where sha1(concat(id_voyage,titre_voyage))='{$_POST['id-voyage-forDel']}'");
    if ($q) die("UpdVoyage%%%%%%1");
    die("UpdVoyage%%%%%%0");
endif;
?>
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
            data: 'id-voyage-forModal=' + id + '&id-vh-forModal=' + vh
        }).done((e) => {
            let v = e.split('UpdVoyage%%%%%%')[1]
            v = JSON.parse(v);
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
        })
    }

    function updateVoyage($id) {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-voyage').serialize()
            }).done((e) => {
                let v = e.split('UpdVoyage%%%%%%')[1]
                if (v == '1') {
                    $.notify('Modification effectuée!!', {
                        className: 'success'
                    })
                    <?php if(isset($_POST['date-f'])): 
                    echo "$('body').append('<form method=\"post\" action=\"#\" id=\"form-reload-after-upd\"><input type=\"hidden\" name=\"date-f\" value=\"{$_POST['date-f']}\"><input type=\"hidden\" name=\"date-t\" value=\"{$_POST['date-t']}\"></form>');$('#form-reload-after-upd').submit();";
                    else : ?>
                    location = "?page=voyages"
                    <?php endif; ?>
                } else {
                    $.notify("Erreur lors de la modificaiton")
                }
            })
        }
    }
    <?php endif; ?>
    <?php if(in_array("del",$rights_voyage)): ?>
    function delvg(id) {
        if (confirm("Etes-vous sûr de vouloir supprimer?")) {
            $.ajax({
                type: 'post',
                data: 'id-voyage-forDel=' + id
            }).done((e) => {
                let v = e.split('UpdVoyage%%%%%%')[1]
                if (v == '1') {
                    $.notify('Voyage supprimée!!', {
                        className: 'success'
                    })
                    location.reload()
                } else {
                    $.notify("Echec de l'opération")
                }
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
                                <?php $q = mysqli_query($con, "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0 and affectation_vehicule.id_region " . ($_SESSION['usr-con']['region-sel'] != '' ? "=({$_SESSION['usr-con']['region-sel']})" : "=''"));
                                while ($r = mysqli_fetch_array($q)):
                                    echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "'>{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                                endwhile;
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
                            <?php $q = mysqli_query($con, "select * from type_chargement_voyage where 1");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "' val-min='{$r['valeur_min']}' val-max='{$r['valeur_max']}'>{$r[1]}</option>";
                                    endwhile;
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