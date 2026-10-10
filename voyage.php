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

    $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
    $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

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

    $vehiculeRepo = new VehiculeRepository($con);
    $voyageRepo = new VoyageRepository($con);

    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $activeVehicles = $vehiculeRepo->findActiveByContext($regionIds, $entiteIds);

    // Single batch query instead of N vehicles × M days queries
    $allVoyages = $voyageRepo->findBatchByDateRange($regionIds, $entiteIds, $dateFrom, $dateTo);

    // Index voyages by [vehicle_id][date]
    $byVehicle = [];
    foreach ($allVoyages as $v) {
        $vid = (int)$v['id_vehicule'];
        $d = $v['date_voyage'];
        if (!isset($byVehicle[$vid])) $byVehicle[$vid] = [];
        if (!isset($byVehicle[$vid][$d])) $byVehicle[$vid][$d] = [];
        $byVehicle[$vid][$d][] = $v;
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
                $scelle = !empty($r1['numero_scelle']) ? " <span class='badge text-bg-warning'>🔒 " . h($r1['numero_scelle']) . "</span>" : '';
                $cell .= "<li class='list-group-item'>" . $sep
                    . h($r1['lib_destination']) . $scelle . " - " . h($r1['distance_destination']) . "km - "
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

    // La période est fournie par la barre de filtres en haut de page (POST date-f/date-t).
    return $tableau . getTableauVoyagesPrestataires();
}
function getTableauVoyagesPrestataires()
{
    global $con;
    global $rights_voyage;
    $hasUpd = in_array('upd', $rights_voyage);
    $hasDel = in_array('del', $rights_voyage);

    $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
    $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

    $vpRepo = new VoyagePrestataireRepository($con);
    $rows = $vpRepo->findBetween($dateFrom, $dateTo, getContextRegions(), getContextEntities());

    $tableau = "<h3 class='h5 mt-4'>Voyages prestataires externes</h3>";
    $tableau .= "<table id='table-voyages-prestataires' class='table table-striped'><thead><tr><th>Date</th><th>Société</th><th>Immatriculation</th><th>Chauffeur</th><th>Entité</th><th>Région</th><th>Trajets</th><th>Type chargement</th><th>Qté</th><th>Convoyeur</th><th>N° scellé</th>";
    if ($hasUpd || $hasDel) $tableau .= "<th>Actions</th>";
    $tableau .= "</tr></thead><tbody>";

    foreach ($rows as $r):
        $chauffeur = $r['nom_chauffeur'];
        $qte = h($r['qte_chargement']);
        if (!empty($r['unite_mesure'])) $qte .= ' ' . h($r['unite_mesure']);
        $societeTitle = trim(($r['adresse_societe'] ?? '') . ' ' . ($r['telephone_societe'] ?? ''));
        $tableau .= "<tr><td>" . h($r['date_voyage']) . "</td><td" . ($societeTitle !== '' ? " title='" . h($societeTitle) . "'" : '') . ">" . h($r['nom_societe'] ?? '') . "</td><td>" . h($r['immatriculation']) . "</td><td>" . h($chauffeur) . "</td><td>" . h($r['nom_entite']) . "</td><td>" . h($r['nom_region']) . "</td><td>" . h($r['trajets_display'] ?? '') . "</td><td>" . h($r['lib_type_chargement']) . "</td><td>" . $qte . "</td><td>" . h($r['convoyeur'] ?? '') . "</td><td>" . h($r['numero_scelle'] ?? '') . "</td>";
        if ($hasUpd || $hasDel) {
            $tableau .= "<td><div class='btn-group'>";
            if ($hasUpd) $tableau .= "<button class='btn btn-light btn-sm' title='Modifier' onclick='updVoyagePresta(" . (int)$r['id_voyage_prestataire'] . ")'><i class='fa fa-pencil-alt'></i></button>";
            if ($hasDel) $tableau .= "<button class='btn btn-danger btn-sm' title='Supprimer' onclick='delVoyagePresta(" . (int)$r['id_voyage_prestataire'] . ")'><i class='fa fa-times'></i></button>";
            $tableau .= "</div></td>";
        }
        $tableau .= "</tr>";
    endforeach;

    // Pas de ligne factice quand la liste est vide : DataTables affiche son
    // propre message d'état vide (sinon la ligne est comptée comme une entrée).
    $tableau .= "</tbody></table>";
    return $tableau . getRecapVoyagesPrestataires();
}
function getRecapVoyagesPrestataires()
{
    global $con;
    $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
    $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

    $vpRepo = new VoyagePrestataireRepository($con);
    $rows = $vpRepo->recapBySociete($dateFrom, $dateTo, getContextRegions(), getContextEntities());

    $html = "<h3 class='h5 mt-4'>Récap par prestataire</h3>";
    $html .= "<table id='table-recap-prestataires' class='table table-striped'><thead><tr><th>Société</th><th># Voyages</th><th>Km parcourus</th><th>Km moyen / voyage</th><th>Quantités chargées</th></tr></thead><tbody>";

    $totalVoyages = 0;
    $totalKm = 0;
    foreach ($rows as $r):
        $nb = (int)$r['nb_voyages'];
        $km = (float)$r['total_km'];
        $totalVoyages += $nb;
        $totalKm += $km;
        $qteHtml = '';
        foreach ($r['quantites'] as $q):
            $val = rtrim(rtrim(number_format((float)$q['total_qte'], 2, ',', ' '), '0'), ',');
            $unite = $q['unite_mesure'] ?? '';
            $qteHtml .= h($q['lib_type_chargement']) . ' : <strong>' . $val . '</strong>' . ($unite !== '' ? ' ' . h($unite) : '') . '<br>';
        endforeach;
        if ($qteHtml === '') $qteHtml = '—';
        $html .= "<tr><td>" . ($r['nom_societe'] ? h($r['nom_societe']) : '—') . "</td><td>$nb</td><td>" . number_format($km, 0, ',', ' ') . "</td><td>" . ($nb > 0 ? number_format($km / $nb, 1, ',', '') : '—') . "</td><td>$qteHtml</td></tr>";
    endforeach;

    $html .= "</tbody><tfoot><tr style='font-weight:bold'><td class='text-bg-dark'>Total</td><td class='text-bg-dark'>$totalVoyages</td><td class='text-bg-dark'>" . number_format($totalKm, 0, ',', ' ') . "</td><td class='text-bg-dark'>" . ($totalVoyages > 0 ? number_format($totalKm / $totalVoyages, 1, ',', '') : '—') . "</td><td class='text-bg-dark'></td></tr></tfoot></table>";
    return $html;
}
function getTableauPrestatairesTransport()
{
    global $con;
    global $rights_voyage;
    $ptSpecifics = ['viewPrestataireTransport', 'savePrestataireTransport', 'updPrestataireTransport', 'delPrestataireTransport'];
    $hasUpd = hasSubRight('updPrestataireTransport', 'upd', $rights_voyage, $ptSpecifics);
    $hasDel = hasSubRight('delPrestataireTransport', 'del', $rights_voyage, $ptSpecifics);

    $ptRepo = new PrestataireTransportRepository($con);
    $rows = $ptRepo->findAll();

    $tableau = "<table id='table-prestataires-transport' class='table table-striped'><thead><tr><th>Société</th><th>Adresse</th><th>Téléphone</th>";
    if ($hasUpd || $hasDel) $tableau .= "<th>Actions</th>";
    $tableau .= "</tr></thead><tbody>";

    foreach ($rows as $r):
        $tableau .= "<tr><td>" . h($r['nom_societe']) . "</td><td>" . h($r['adresse_societe'] ?? '') . "</td><td>" . h($r['telephone_societe'] ?? '') . "</td>";
        if ($hasUpd || $hasDel) {
            $tableau .= "<td><div class='btn-group'>";
            if ($hasUpd) $tableau .= "<button class='btn btn-light btn-sm' title='Modifier' onclick='updPrestataireTransport(" . (int)$r['id_prestataire_transport'] . ")'><i class='fa fa-pencil-alt'></i></button>";
            if ($hasDel) $tableau .= "<button class='btn btn-danger btn-sm' title='Supprimer' onclick='delPrestataireTransport(" . (int)$r['id_prestataire_transport'] . ")'><i class='fa fa-times'></i></button>";
            $tableau .= "</div></td>";
        }
        $tableau .= "</tr>";
    endforeach;

    $tableau .= "</tbody></table>";
    return $tableau;
}
function getTableauVoyagesVehicules()
{
    set_time_limit(120);
    global $con;
    $vehiculeRepo = new VehiculeRepository($con);
    $voyageRepo = new VoyageRepository($con);
    $trajetRepo = new TrajetRepository($con);

    $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
    $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

    $vehicleRows = $vehiculeRepo->findAllWithChauffeur();
    $destinations = $trajetRepo->findAll();

    // Pre-build destination lookup: id → [lib, distance]
    $destMap = [];
    foreach ($destinations as $d) {
        $destMap[(int)$d['id_destination']] = [
            'lib' => $d['lib_destination'],
            'distance' => (int)$d['distance_destination'],
        ];
    }

    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $scope = getVoyagesScope();
    $allRows = $voyageRepo->findBatchVoyagesVehicules($regionIds, $entiteIds, $dateFrom, $dateTo);

    // Voyages prestataires externes : totaux + cellules par transporteur et destination.
    $vpRepo = new VoyagePrestataireRepository($con);
    $extCarriers = [];
    foreach ($vpRepo->countByCarrier($dateFrom, $dateTo, $regionIds, $entiteIds) as $c) {
        $key = $c['id_prestataire_transport'] . '|' . $c['immatriculation'];
        $extCarriers[$key] = [
            'societe' => $c['nom_societe'] ?? '',
            'immatriculation' => $c['immatriculation'] ?? '',
            'chauffeur' => $c['nom_chauffeur'] ?? '',
            'total' => (int)$c['nb_voyages'],
            'cells' => [],
        ];
    }
    foreach ($vpRepo->countByCarrierAndDestination($dateFrom, $dateTo, $regionIds, $entiteIds) as $c) {
        $key = $c['id_prestataire_transport'] . '|' . $c['immatriculation'];
        if (isset($extCarriers[$key])) {
            $extCarriers[$key]['cells'][(int)$c['id_destination']] = (int)$c['nb_voyages'];
        }
    }

    // Index: [vehicle_id][destination_id] → [voyage_ids, total_carburant]
    $byVehDest = [];
    foreach ($allRows as $row) {
        $vid = (int)$row['id_vehicule'];
        $did = (int)$row['id_destination'];
        if (!isset($byVehDest[$vid])) $byVehDest[$vid] = [];
        if (!isset($byVehDest[$vid][$did])) $byVehDest[$vid][$did] = ['ids' => [], 'carb' => 0];
        $byVehDest[$vid][$did]['ids'][] = (int)$row['id_voyage'];
        $byVehDest[$vid][$did]['carb'] += (float)$row['qte_carburant'];
    }

    $tableau = "<table class='table table-striped'><thead><tr><th>#</th><th>Immatriculation</th><th># Voyages</th><th># Kms</th><th>Carburant (en L)</th><th>Conso. 100km</th>";
    foreach ($destinations as $r):
        $tableau .= "<th>" . h($r['lib_destination']) . "</th>";
    endforeach;
    $tableau .= "</tr></thead><tbody>";

    $i = 1;
    $total_voyages = 0;
    $total_kms = 0;
    $total_cbt = 0;
    $total_voyages_flotte = 0;
    $total_kms_flotte = 0;
    $total_voyages_ext = 0;
    $total_kms_ext = 0;
    $total_voyage_col = [];
    $total_kms_col = [];
    $nbTrajets = count($destinations);

    if ($scope !== 'externe'):
    foreach ($vehicleRows as $r):
        $vid = (int)$r['id_vehicule'];
        $vehData = $byVehDest[$vid] ?? [];

        $ligneVoyages = 0;
        $ligneKms = 0;
        $ligneCarb = 0;
        $voyagesArray = [];

        foreach ($vehData as $did => $data) {
            $cnt = count(array_unique($data['ids']));
            $ligneVoyages += $cnt;
            $ligneCarb += $data['carb'];
            $dist = $destMap[$did]['distance'] ?? 0;
            $ligneKms += $dist * $cnt;
            foreach ($data['ids'] as $idv) {
                $voyagesArray[$idv] = true;
            }
        }
        $nbVoyagesUniques = count($voyagesArray);
        $conso = $ligneKms > 0 ? round($ligneCarb / $ligneKms * 100, 2) : 0;

        $tableau .= "<tr><td>$i</td><td>" . h($r['immatriculation_vehicule']) . " - " . h($r['n_chauffeur']) . " <span class='badge text-bg-primary ms-1'>Flotte</span></td>"
            . "<td><span id='total_vg_ln_$vid'>$nbVoyagesUniques</span></td>"
            . "<td><span id='total_kms_ln_$vid'>$ligneKms</span></td>"
            . "<td><span id='total_cbt_ln_$vid'>$ligneCarb</span></td>"
            . "<td><span id='total_cbt_100_ln_$vid'>$conso</span></td>";

        for ($j = 0; $j < $nbTrajets; $j++):
            $did = (int)$destinations[$j]['id_destination'];
            $cell = $vehData[$did] ?? null;
            $cnt = $cell ? count(array_unique($cell['ids'])) : 0;
            $tableau .= "<td " . ($cnt > 0 ? "class='text-bg-success' style='background-color:#198754;color:white'>$cnt" : " class='text-bg-info' style='background-color:#0dcaf0;'>") . "</td>";

            $kms = $cnt > 0 ? ($destMap[$did]['distance'] ?? 0) * $cnt : 0;
            if (!isset($total_voyage_col[$i])) $total_voyage_col[$i] = [];
            if (!isset($total_voyage_col[$i][$j])) $total_voyage_col[$i][$j] = 0;
            $total_voyage_col[$i][$j] += $cnt;
            if (!isset($total_kms_col[$i])) $total_kms_col[$i] = [];
            if (!isset($total_kms_col[$i][$j])) $total_kms_col[$i][$j] = 0;
            $total_kms_col[$i][$j] += $kms;
        endfor;

        $tableau .= "</tr>";
        $i++;
        $total_voyages += $nbVoyagesUniques;
        $total_kms += $ligneKms;
        $total_cbt += $ligneCarb;
        $total_voyages_flotte += $nbVoyagesUniques;
        $total_kms_flotte += $ligneKms;
    endforeach;
    endif;

    // Lignes prestataires externes : une ligne par transporteur (société + immatriculation).
    if ($scope !== 'flotte'):
    foreach ($extCarriers as $carrier):
        $ligneKms = 0;
        foreach ($carrier['cells'] as $did => $cnt) {
            $ligneKms += ($destMap[$did]['distance'] ?? 0) * $cnt;
        }
        $label = h($carrier['societe']) . ' — ' . h($carrier['immatriculation']);
        if ($carrier['chauffeur'] !== '') $label .= ' — ' . h($carrier['chauffeur']);
        $tableau .= "<tr><td>$i</td><td>$label <span class='badge text-bg-warning ms-1'>Externe</span></td>"
            . "<td><span id='total_vg_ln_ext_$i'>" . $carrier['total'] . "</span></td>"
            . "<td><span id='total_kms_ln_ext_$i'>$ligneKms</span></td>"
            . "<td>—</td><td>—</td>";

        for ($j = 0; $j < $nbTrajets; $j++):
            $did = (int)$destinations[$j]['id_destination'];
            $cnt = $carrier['cells'][$did] ?? 0;
            $tableau .= "<td " . ($cnt > 0 ? "style='background-color:#fd7e14;color:white'>$cnt" : " class='text-bg-info' style='background-color:#0dcaf0;'>") . "</td>";

            $kms = $cnt > 0 ? ($destMap[$did]['distance'] ?? 0) * $cnt : 0;
            if (!isset($total_voyage_col[$i])) $total_voyage_col[$i] = [];
            if (!isset($total_voyage_col[$i][$j])) $total_voyage_col[$i][$j] = 0;
            $total_voyage_col[$i][$j] += $cnt;
            if (!isset($total_kms_col[$i])) $total_kms_col[$i] = [];
            if (!isset($total_kms_col[$i][$j])) $total_kms_col[$i][$j] = 0;
            $total_kms_col[$i][$j] += $kms;
        endfor;

        $tableau .= "</tr>";
        $i++;
        $total_voyages += $carrier['total'];
        $total_kms += $ligneKms;
        $total_voyages_ext += $carrier['total'];
        $total_kms_ext += $ligneKms;
    endforeach;
    endif;

    $nblignes = $i - 1;

    $tfoot = "";
    if ($nbTrajets > 0):
        $total_col = [];
        $total_k = [];
        for ($r = 0; $r < $nblignes; $r++) {
            for ($c = 0; $c < $nbTrajets; $c++) {
                if (!isset($total_col[$c])) $total_col[$c] = 0;
                if (!isset($total_k[$c])) $total_k[$c] = 0;
                $total_col[$c] += $total_voyage_col[$r + 1][$c] ?? 0;
                $total_k[$c] += $total_kms_col[$r + 1][$c] ?? 0;
            }
        }
        $tfoot = "<tr style='font-weight:bold'><td colspan=2 class='text-bg-dark'>Total</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_voyages_flotte, $total_voyages_ext, $scope) . "</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_kms_flotte, $total_kms_ext, $scope) . "</td><td class='text-bg-dark'>" . ($scope === 'externe' ? '—' : $total_cbt) . "</td><td class='text-bg-dark'>" . ($scope === 'externe' ? '—' : round($total_cbt / ($total_kms > 0 ? $total_kms : 1) * 100, 2)) . "</td>";
        for ($c = 0; $c < $nbTrajets; $c++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . (int)$total_col[$c] . "</td>";
        $tfoot .= "</tr>";
        $tfoot .= "<tr style='font-weight:bold'><td colspan=2 class='text-bg-dark'>Total</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_voyages_flotte, $total_voyages_ext, $scope) . "</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_kms_flotte, $total_kms_ext, $scope) . "</td><td></td><td></td>";
        for ($c = 0; $c < $nbTrajets; $c++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . (int)$total_k[$c] . "</td>";
        $tfoot .= "</tr>";
    endif;
    $tableau .= "</tbody><tfoot>$tfoot</tfoot></table>";
    $tableau .= getVoyagesTypeLegend($scope);
    // La période est fournie par la barre de filtres en haut de page (POST date-f/date-t).
    return $tableau;
}
function getTableauVoyagesPeriodes()
{
    set_time_limit(120);
    global $con;
    $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
    $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

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

    $voyageRepo = new VoyageRepository($con);
    $trajetRepo = new TrajetRepository($con);
    $destinations = $trajetRepo->findAll();

    // Pre-build destination lookup
    $destMap = [];
    foreach ($destinations as $dest) {
        $destMap[(int)$dest['id_destination']] = [
            'lib' => $dest['lib_destination'],
            'distance' => (int)$dest['distance_destination'],
        ];
    }

    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $scope = getVoyagesScope();
    $allRows = $voyageRepo->findBatchVoyagesVehicules($regionIds, $entiteIds, $dateFrom, $dateTo);

    // Index: [date][destination_id] → [voyage_ids, total_carburant]
    $byDateDest = [];
    foreach ($allRows as $row) {
        $dt = $row['date_voyage'];
        $did = (int)$row['id_destination'];
        if (!isset($byDateDest[$dt])) $byDateDest[$dt] = [];
        if (!isset($byDateDest[$dt][$did])) $byDateDest[$dt][$did] = ['ids' => [], 'carb' => 0];
        $byDateDest[$dt][$did]['ids'][] = (int)$row['id_voyage'];
        $byDateDest[$dt][$did]['carb'] += (float)$row['qte_carburant'];
    }

    // Voyages prestataires externes : totaux par date et par date + destination.
    $vpRepo = new VoyagePrestataireRepository($con);
    $extByDate = [];
    foreach ($vpRepo->countByDate($dateFrom, $dateTo, $regionIds, $entiteIds) as $row) {
        $extByDate[$row['date']] = (int)$row['nb'];
    }
    $extByDateDest = [];
    foreach ($vpRepo->countByDateAndDestination($dateFrom, $dateTo, $regionIds, $entiteIds) as $row) {
        if ($row['id_destination'] === null) continue;
        $extByDateDest[$row['date_voyage']][(int)$row['id_destination']] = (int)$row['nb_voyages'];
    }

    $tableau = "<table class='table table-striped'><thead><tr><th>#</th><th>Date</th><th># Voyages</th><th># Kms</th><th>Carburant (en L)</th><th>Conso. 100km</th>";
    foreach ($destinations as $r):
        $tableau .= "<th>" . h($r['lib_destination']) . "</th>";
    endforeach;
    $tableau .= "</tr></thead><tbody>";

    $nbTrajets = count($destinations);
    $nblignes = count($dates);
    $i = 1;
    $total_voyages = 0;
    $total_kms = 0;
    $total_cbt = 0;
    $total_voyages_flotte = 0;
    $total_kms_flotte = 0;
    $total_voyages_ext = 0;
    $total_kms_ext = 0;
    $total_voyage_col = [];
    $total_kms_col = [];
    $total_voyage_ext_col = [];
    $total_kms_ext_col = [];

    foreach ($dates as $idx => $dateStr):
        $dateData = $byDateDest[$dateStr] ?? [];
        $displayDate = $dateCols[$idx];

        $ligneVoyages = 0;
        $ligneKms = 0;
        $ligneCarb = 0;
        $voyagesArray = [];

        foreach ($dateData as $did => $data) {
            $cnt = count(array_unique($data['ids']));
            $ligneVoyages += $cnt;
            $ligneCarb += $data['carb'];
            $dist = $destMap[$did]['distance'] ?? 0;
            $ligneKms += $dist * $cnt;
            foreach ($data['ids'] as $idv) {
                $voyagesArray[$idv] = true;
            }
        }
        $nbVoyagesUniques = count($voyagesArray);
        $conso = $ligneKms > 0 ? round($ligneCarb / $ligneKms * 100, 2) : 0;
        $nbVoyagesExt = $extByDate[$dateStr] ?? 0;
        $ligneKmsExt = 0;
        foreach ($extByDateDest[$dateStr] ?? [] as $did => $cntE) {
            $ligneKmsExt += ($destMap[$did]['distance'] ?? 0) * $cntE;
        }

        $tableau .= "<tr><td>$i</td><td>$displayDate</td>"
            . "<td>" . getVoyagesTypeBadges($nbVoyagesUniques, $nbVoyagesExt, $scope) . "</td>"
            . "<td>" . getVoyagesTypeBadges($ligneKms, $ligneKmsExt, $scope) . "</td>"
            . "<td>" . ($scope === 'externe' ? '—' : $ligneCarb) . "</td>"
            . "<td>" . ($scope === 'externe' ? '—' : $conso) . "</td>";

        for ($j = 0; $j < $nbTrajets; $j++):
            $did = (int)$destinations[$j]['id_destination'];
            $cell = $dateData[$did] ?? null;
            $cnt = $cell ? count(array_unique($cell['ids'])) : 0;
            $cntE = $extByDateDest[$dateStr][$did] ?? 0;
            if ($cnt === 0 && $cntE === 0) {
                $tableau .= "<td class='text-bg-info' style='background-color:#0dcaf0;'></td>";
            } else {
                $tableau .= "<td>" . getVoyagesTypeBadges($cnt, $cntE, $scope) . "</td>";
            }

            $kms = $cnt > 0 ? ($destMap[$did]['distance'] ?? 0) * $cnt : 0;
            $kmsE = $cntE > 0 ? ($destMap[$did]['distance'] ?? 0) * $cntE : 0;
            if (!isset($total_voyage_col[$i])) $total_voyage_col[$i] = [];
            if (!isset($total_voyage_col[$i][$j])) $total_voyage_col[$i][$j] = 0;
            $total_voyage_col[$i][$j] += $cnt;
            if (!isset($total_kms_col[$i])) $total_kms_col[$i] = [];
            if (!isset($total_kms_col[$i][$j])) $total_kms_col[$i][$j] = 0;
            $total_kms_col[$i][$j] += $kms;
            if (!isset($total_voyage_ext_col[$i])) $total_voyage_ext_col[$i] = [];
            if (!isset($total_voyage_ext_col[$i][$j])) $total_voyage_ext_col[$i][$j] = 0;
            $total_voyage_ext_col[$i][$j] += $cntE;
            if (!isset($total_kms_ext_col[$i])) $total_kms_ext_col[$i] = [];
            if (!isset($total_kms_ext_col[$i][$j])) $total_kms_ext_col[$i][$j] = 0;
            $total_kms_ext_col[$i][$j] += $kmsE;
        endfor;

        $tableau .= "</tr>";
        $i++;
        $total_voyages += $nbVoyagesUniques;
        $total_kms += $ligneKms;
        $total_cbt += $ligneCarb;
        $total_voyages_flotte += $nbVoyagesUniques;
        $total_kms_flotte += $ligneKms;
        $total_voyages_ext += $nbVoyagesExt;
        $total_kms_ext += $ligneKmsExt;
    endforeach;

    $tfoot = "";
    if ($nbTrajets > 0):
        $total_col = [];
        $total_k = [];
        $total_ext_col = [];
        $total_ext_k = [];
        for ($r = 0; $r < $nblignes; $r++) {
            for ($c = 0; $c < $nbTrajets; $c++) {
                if (!isset($total_col[$c])) $total_col[$c] = 0;
                if (!isset($total_k[$c])) $total_k[$c] = 0;
                $total_col[$c] += $total_voyage_col[$r + 1][$c] ?? 0;
                $total_k[$c] += $total_kms_col[$r + 1][$c] ?? 0;
                if (!isset($total_ext_col[$c])) $total_ext_col[$c] = 0;
                if (!isset($total_ext_k[$c])) $total_ext_k[$c] = 0;
                $total_ext_col[$c] += $total_voyage_ext_col[$r + 1][$c] ?? 0;
                $total_ext_k[$c] += $total_kms_ext_col[$r + 1][$c] ?? 0;
            }
        }
        $tfoot = "<tr style='font-weight:bold'><td colspan=2 class='text-bg-dark'>Total</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_voyages_flotte, $total_voyages_ext, $scope) . "</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_kms_flotte, $total_kms_ext, $scope) . "</td><td class='text-bg-dark'>" . ($scope === 'externe' ? '—' : $total_cbt) . "</td><td class='text-bg-dark'>" . ($scope === 'externe' ? '—' : round($total_cbt / ($total_kms > 0 ? $total_kms : 1) * 100, 2)) . "</td>";
        for ($c = 0; $c < $nbTrajets; $c++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . getVoyagesTypeBadges((int)$total_col[$c], (int)$total_ext_col[$c], $scope) . "</td>";
        $tfoot .= "</tr>";
        $tfoot .= "<tr style='font-weight:bold'><td colspan=2 class='text-bg-dark'>Total</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_voyages_flotte, $total_voyages_ext, $scope) . "</td><td class='text-bg-dark'>" . getVoyagesTypeBadges($total_kms_flotte, $total_kms_ext, $scope) . "</td><td></td><td></td>";
        for ($c = 0; $c < $nbTrajets; $c++) $tfoot .= "<td class='text-bg-dark dt-type-numeric'>" . getVoyagesTypeBadges((int)$total_k[$c], (int)$total_ext_k[$c], $scope) . "</td>";
        $tfoot .= "</tr>";
    endif;
    $tableau .= "</tbody><tfoot>$tfoot</tfoot></table>";
    $tableau .= getVoyagesTypeLegend($scope);
    // La période est fournie par la barre de filtres en haut de page (POST date-f/date-t).
    return $tableau;
}

function getTableauEvaluationVoyages()
{
    set_time_limit(120);
    global $con;
    $dateFrom = isset($_POST['date-f']) ? date('Y-m-d', strtotime($_POST['date-f'])) : date('Y-m-01');
    $dateTo   = isset($_POST['date-t']) ? date('Y-m-d', strtotime($_POST['date-t'])) : date('Y-m-t');

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

    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $scope = getVoyagesScope();
    $regionRepo = new RegionRepository($con);
    $objectifRepo = new ObjectifRepository($con);
    $voyageRepo = new VoyageRepository($con);
    $reg = $regionRepo->findNonAdminByIds($regionIds);

    // 2 batch queries instead of D×R×2
    $allObjectifs = $objectifRepo->findByDateRangeAndRegions($dateFrom, $dateTo, $regionIds, $entiteIds);
    $cntByDateRegion = $voyageRepo->countBatchByDateAndRegionsByType($regionIds, $entiteIds, $dateFrom, $dateTo);

    // Index objectifs by [date][region] (aggregate across entities)
    $objByDateRegion = [];
    foreach ($allObjectifs as $o) {
        $rid = (int)$o['id_region'];
        if (!isset($objByDateRegion[$o['date_objectif_periode']][$rid])) {
            $objByDateRegion[$o['date_objectif_periode']][$rid] = 0;
        }
        $objByDateRegion[$o['date_objectif_periode']][$rid] += (int)$o['objectif'];
    }

    $tableau = "<table class='table table-striped no-datatable' id='table-evaluation'><thead><tr><th rowspan=2>Date</th>";
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

    foreach ($dates as $idx => $dateStr):
        $total_plan = 0;
        $total_real = 0;
        $total_distances = 0;
        $tableau .= "<tr><td>" . $dateCols[$idx] . "</td>";
        foreach ($reg as $r):
            $regionId = (int)$r['id_region'];
            $plan = $objByDateRegion[$dateStr][$regionId] ?? 0;
            $tableau .= "<td>" . ($plan ? h((string)$plan) : '0') . "</td>";
            $total_plan += $plan;

            $cnt = $cntByDateRegion[$dateStr . '|' . $regionId] ?? null;
            $nbF = $cnt['nb_flotte'] ?? 0;
            $nbE = $cnt['nb_externe'] ?? 0;
            $distF = $cnt['dist_flotte'] ?? 0.0;
            $distE = $cnt['dist_externe'] ?? 0.0;
            $real = $scope === 'flotte' ? $nbF : ($scope === 'externe' ? $nbE : $nbF + $nbE);
            $dist = $scope === 'flotte' ? $distF : ($scope === 'externe' ? $distE : $distF + $distE);
            $tableau .= "<td>" . getVoyagesTypeBadges($nbF, $nbE, $scope) . "</td>";
            $total_real += $real;
            $total_distances += $dist;

            $score = round($plan > 0 ? $real / $plan * 100 : 0, 1);
            $tableau .= "<td " . ($score < 100 ? 'class="text-bg-danger"' : 'class="text-bg-success"') . ">$score%</td>";
            $tableau .= "<td>" . ($plan - $real) . "</td>";
            $tableau .= "<td class='border-end'>" . getVoyagesTypeBadges($distF, $distE, $scope) . "</td>";
        endforeach;
        $total_score = round($total_plan == 0 ? 0 : $total_real / $total_plan * 100, 1);
        $total_gap = $total_plan - $total_real;
        $tableau .= "<td style='font-weight:bold'>$total_plan</td><td style='font-weight:bold'>$total_real</td><td style='font-weight:bold' class='text-bg-" . ($total_score >= 100 ? 'success' : 'danger') . "'>$total_score%</td><td style='font-weight:bold'>$total_gap</td><td style='font-weight:bold'>$total_distances</td>";
        $tableau .= "</tr>";
    endforeach;
    $tableau .= "</tbody></table>";
    $tableau .= getVoyagesTypeLegend($scope);
    // La période est fournie par la barre de filtres en haut de page (POST date-f/date-t).
    return $tableau;
}
?>
<?php include('modalNewVoyage.php'); ?>
<?php include('modalNewPrestataireTransport.php'); ?>
<?php $ptTransportSpecifics = ['viewPrestataireTransport','savePrestataireTransport','updPrestataireTransport','delPrestataireTransport']; ?>
<?php if (hasSubRight('updPrestataireTransport', 'upd', $rights_voyage, $ptTransportSpecifics) || hasSubRight('delPrestataireTransport', 'del', $rights_voyage, $ptTransportSpecifics)) include('modalUpdPrestataireTransport.php'); ?>
<?php if (in_array('upd', $rights_voyage) || in_array('del', $rights_voyage)) include('modalUpdVoyagePrestataire.php'); ?>
<?php /* POST handled by VoyageController — see controllers/router.php */ ?>
<?php if (isset($_GET['action']) && $_GET['action'] == 'new' && !isset($_GET['subpage'])): ?>
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
            $('#numero-scelle-upd-voyage').val(v.numero_scelle || '')
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
                    echo "$('body').append('<form method=\"post\" action=\"#\" id=\"form-reload-after-upd\"><input type=\"hidden\" name=\"csrf_token\" value=\"' + window.CSRF_TOKEN + '\"><input type=\"hidden\" name=\"date-f\" value=" . j($_POST['date-f']) . "><input type=\"hidden\" name=\"date-t\" value=" . j($_POST['date-t']) . "><input type=\"hidden\" name=\"scope\" value=" . j(getVoyagesScope()) . "></form>');$('#form-reload-after-upd').submit();";
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
                            <input type="text" id="numero-scelle-upd-voyage" name="numero-scelle-upd-voyage" class="form-control" placeholder="N° scellé">
                            <label for="numero-scelle-upd-voyage">N° de scellé</label>
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
/**
 * Période d'analyse des statistiques voyages (POST date-f / date-t, barre de filtres).
 * Défaut : mois courant. Les bornes sont validées, normalisées et interverties si besoin.
 */
function getVoyagesPeriod(): array
{
    $from = $_POST['date-f'] ?? '';
    $to = $_POST['date-t'] ?? '';
    $tsFrom = strtotime($from);
    $tsTo = strtotime($to);
    if ($tsFrom === false || $tsTo === false) return [date('Y-m-01'), date('Y-m-t')];
    $dateFrom = date('Y-m-d', $tsFrom);
    $dateTo = date('Y-m-d', $tsTo);
    if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
    return [$dateFrom, $dateTo];
}

/** Vrai si une période personnalisée a été soumise (différente du mois courant). */
function getVoyagesPeriodIsCustom(): bool
{
    $from = $_POST['date-f'] ?? '';
    $to = $_POST['date-t'] ?? '';
    if (strtotime($from) === false || strtotime($to) === false) return false;
    [$dateFrom, $dateTo] = getVoyagesPeriod();
    return $dateFrom !== date('Y-m-01') || $dateTo !== date('Y-m-t');
}

/**
 * Scope courant des statistiques voyages (POST barre de filtres, puis GET ?scope=).
 * tout | flotte | externe | comparaison — comparaison affiche flotte et externes côte à côte.
 */
function getVoyagesScope()
{
    $scope = $_POST['scope'] ?? ($_GET['scope'] ?? 'tout');
    return in_array($scope, ['tout', 'flotte', 'externe', 'comparaison'], true) ? $scope : 'tout';
}

/**
 * Badges colorés des voyages par type selon la portée de la barre de filtres :
 * bleu = flotte, jaune = prestataires externes. « tout »/« comparaison » : les deux.
 */
function getVoyagesTypeBadges(int|float $nbFlotte, int|float $nbExterne, string $scope): string
{
    if ($scope === 'flotte') {
        return '<span class="badge text-bg-primary" title="Voyages flotte">' . $nbFlotte . '</span>';
    }
    if ($scope === 'externe') {
        return '<span class="badge text-bg-warning" title="Voyages prestataires externes">' . $nbExterne . '</span>';
    }
    return '<span class="badge text-bg-primary" title="Voyages flotte">' . $nbFlotte . '</span>'
        . ' <span class="badge text-bg-warning" title="Voyages prestataires externes">' . $nbExterne . '</span>';
}

/** Légende des badges flotte / externes (affichée quand les deux types sont visibles). */
function getVoyagesTypeLegend(string $scope): string
{
    if ($scope === 'flotte' || $scope === 'externe') return '';
    return "<div class='mt-2 small'><span class='badge text-bg-primary'>Flotte</span> <span class='badge text-bg-warning'>Prestataires externes</span></div>";
}

/**
 * Barre de filtres période + portée des statistiques voyages.
 * Formulaire POST unique en haut de page : les tableaux et les cartes lisent
 * $_POST['date-f']/['date-t']/['scope'] (la soumission du select conserve les dates).
 */
function getVoyagesFilterBar(string $scope)
{
    [$dateFrom, $dateTo] = getVoyagesPeriod();

    $options = [
        'tout' => 'Tous les voyages',
        'flotte' => 'Voyages flotte',
        'externe' => 'Voyages externes',
        'comparaison' => 'Comparaison flotte / externes',
    ];
    $presets = [
        'Mois en cours' => [date('Y-m-01'), date('Y-m-t')],
        'Mois dernier' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))],
        '30 derniers jours' => [date('Y-m-d', strtotime('-29 days')), date('Y-m-d')],
    ];

    $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title"><i class="fa fa-sliders-h me-1"></i>Filtres des statistiques voyages</h2></div>';
    $html .= '<div class="p-3"><form method="post" action="#" id="form-filtres-voyages" class="d-flex flex-wrap align-items-end gap-2">';
    // Token injecté côté serveur : le onchange="this.form.submit()" du select de portée
    // est un submit natif qui ne déclenche pas l'événement jQuery qui l'ajouterait.
    $html .= '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token'] ?? '') . '">';
    $html .= '<div class="me-2">'
        . '<label class="fw-bold small text-muted d-block mb-1"><i class="fa fa-calendar-alt me-1"></i>PÉRIODE D\'ANALYSE</label>'
        . '<div class="d-flex align-items-center gap-2">'
        . '<div class="form-floating" style="min-width:160px"><input type="date" id="date-f" name="date-f" class="form-control" value="' . h($dateFrom) . '"><label for="date-f">Du</label></div>'
        . '<span class="text-muted"><i class="fa fa-arrow-right"></i></span>'
        . '<div class="form-floating" style="min-width:160px"><input type="date" id="date-t" name="date-t" class="form-control" value="' . h($dateTo) . '"><label for="date-t">Au</label></div>'
        . '<button type="submit" class="btn btn-primary"><i class="fa fa-check me-1"></i>Afficher</button>'
        . '</div></div>';
    $html .= '<div class="me-2">'
        . '<label class="fw-bold small text-muted d-block mb-1">RACCOURCIS</label>'
        . '<div class="btn-group">';
    foreach ($presets as $label => [$from, $to]) {
        $html .= '<button type="button" class="btn btn-outline-primary btn-period-shortcut" data-period-from="' . h($from) . '" data-period-to="' . h($to) . '">' . h($label) . '</button>';
    }
    $html .= '</div></div>';
    $html .= '<div class="ms-auto">'
        . '<label for="scope-stat-voyages" class="fw-bold small text-muted d-block mb-1">PORTÉE DES STATISTIQUES</label>'
        . '<div class="input-group" style="max-width:320px">'
        . '<span class="input-group-text"><i class="fa fa-filter"></i></span>'
        . '<select id="scope-stat-voyages" name="scope" class="form-select" aria-label="Filtre des statistiques" onchange="this.form.submit()">';
    foreach ($options as $value => $label) {
        $sel = $value === $scope ? ' selected' : '';
        $html .= '<option value="' . $value . '"' . $sel . '>' . h($label) . '</option>';
    }
    $html .= '</select></div></div>';
    $html .= '</form></div></div>';
    $html .= '<script>'
        . "$('#form-filtres-voyages .btn-period-shortcut').on('click', function () {"
        . "$('#date-f').val($(this).data('period-from'));"
        . "$('#date-t').val($(this).data('period-to'));"
        . "$('#form-filtres-voyages').submit();"
        . '});'
        . '</script>';
    return $html;
}

function getDashboardCardsVoyages()
{
    global $con;
    $repo = new VoyageRepository($con);
    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();
    $scope = getVoyagesScope();
    [$dateFrom, $dateTo] = getVoyagesPeriod();
    $isCustom = getVoyagesPeriodIsCustom();
    // « du mois » quand la période par défaut (ou égale au mois courant) est active,
    // « de la période » quand une période personnalisée est choisie.
    $periodWord = $isCustom ? 'de la période' : 'du mois';
    $periodDetail = $isCustom ? ' (du ' . date('d/m/Y', strtotime($dateFrom)) . ' au ' . date('d/m/Y', strtotime($dateTo)) . ')' : '';

    $html = getVoyagesFilterBar($scope);

    if ($scope === 'comparaison') {
        $voyagesF = $repo->countVoyagesBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'flotte');
        $voyagesE = $repo->countVoyagesBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'externe');
        $tauxF = $repo->tauxRealisationBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'flotte');
        $tauxE = $repo->tauxRealisationBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'externe');
        $kmF = $repo->sumKmBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'flotte');
        $kmE = $repo->sumKmBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'externe');
        $voyagesMois = $voyagesF + $voyagesE;
        $taux = $repo->tauxRealisationBetween($regionIds, $entiteIds, $dateFrom, $dateTo, 'tout');
        $kmMois = $kmF + $kmE;
    } else {
        $voyagesMois = $repo->countVoyagesBetween($regionIds, $entiteIds, $dateFrom, $dateTo, $scope);
        $taux = $repo->tauxRealisationBetween($regionIds, $entiteIds, $dateFrom, $dateTo, $scope);
        $kmMois = $repo->sumKmBetween($regionIds, $entiteIds, $dateFrom, $dateTo, $scope);
    }
    $vehicules = $repo->countActiveVehiclesBetween($regionIds, $entiteIds, $dateFrom, $dateTo);
    $conso = $repo->avgConsumptionBetween($regionIds, $entiteIds, $dateFrom, $dateTo);

    $tauxClass = $taux >= 100 ? 'lt-stat-success' : ($taux >= 80 ? 'lt-stat-warning' : 'lt-stat-danger');
    $consoClass = $conso === null ? '' : ($conso <= 15 ? 'lt-stat-success' : ($conso <= 25 ? 'lt-stat-warning' : 'lt-stat-danger'));

    if ($scope === 'comparaison') {
        $voyagesDisplay = number_format($voyagesF, 0, ',', ' ') . ' / ' . number_format($voyagesE, 0, ',', ' ');
        $voyagesLabel = 'Voyages ' . $periodWord . ' (flotte / externes)' . $periodDetail;
        $tauxDisplay = $tauxF . ' % / ' . $tauxE . ' %';
        $tauxLabel = 'Taux réalisation ' . $periodWord . ' (flotte / externes)' . $periodDetail;
        $kmDisplay = number_format($kmF, 0, ',', ' ') . ' / ' . number_format($kmE, 0, ',', ' ') . ' km';
        $kmLabel = 'Km ' . $periodWord . ' (flotte / externes)' . $periodDetail;
    } elseif ($scope === 'externe') {
        $voyagesDisplay = number_format($voyagesMois, 0, ',', ' ');
        $voyagesLabel = 'Voyages externes ' . $periodWord . $periodDetail;
        $tauxDisplay = $taux . ' %';
        $tauxLabel = 'Taux réalisation externes ' . $periodWord . $periodDetail;
        $kmDisplay = number_format($kmMois, 0, ',', ' ') . ' km';
        $kmLabel = 'Km externes ' . $periodWord . $periodDetail;
    } elseif ($scope === 'flotte') {
        $voyagesDisplay = number_format($voyagesMois, 0, ',', ' ');
        $voyagesLabel = 'Voyages flotte ' . $periodWord . $periodDetail;
        $tauxDisplay = $taux . ' %';
        $tauxLabel = 'Taux réalisation flotte ' . $periodWord . $periodDetail;
        $kmDisplay = number_format($kmMois, 0, ',', ' ') . ' km';
        $kmLabel = 'Km flotte ' . $periodWord . $periodDetail;
    } else {
        $voyagesDisplay = number_format($voyagesMois, 0, ',', ' ');
        $voyagesLabel = 'Voyages ' . $periodWord . $periodDetail;
        $tauxDisplay = $taux . ' %';
        $tauxLabel = 'Taux réalisation objectifs ' . $periodWord . $periodDetail;
        $kmDisplay = number_format($kmMois, 0, ',', ' ') . ' km';
        $kmLabel = 'Km parcourus ' . $periodWord . $periodDetail;
    }

    $html .= '<div class="row g-3 mb-3">';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-road"></i></div>';
    $html .= '<div class="lt-stat-value">' . $voyagesDisplay . '</div>';
    $html .= '<div class="lt-stat-label">' . $voyagesLabel . '</div>';
    $html .= '</div></div>';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card ' . $tauxClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-bullseye"></i></div>';
    $html .= '<div class="lt-stat-value">' . $tauxDisplay . '</div>';
    $html .= '<div class="lt-stat-label">' . $tauxLabel . '</div>';
    $html .= '</div></div>';

    $html .= '<div class="col-md"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-tachometer-alt"></i></div>';
    $html .= '<div class="lt-stat-value">' . $kmDisplay . '</div>';
    $html .= '<div class="lt-stat-label">' . $kmLabel . '</div>';
    $html .= '</div></div>';

    $vehiculesDisplay = $scope === 'externe' ? '—' : $vehicules['actifs'] . ' / ' . $vehicules['total'];
    $html .= '<div class="col-md"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-truck"></i></div>';
    $html .= '<div class="lt-stat-value">' . $vehiculesDisplay . '</div>';
    $html .= '<div class="lt-stat-label">' . ($scope === 'comparaison' ? 'Véhicules actifs ' . $periodWord . ' (flotte)' : 'Véhicules actifs ' . $periodWord) . $periodDetail . '</div>';
    $html .= '</div></div>';

    $consoDisplay = $scope === 'externe' ? '—' : ($conso !== null ? number_format($conso, 1, ',', '') . ' L/100km' : '—');
    $html .= '<div class="col-md"><div class="lt-card lt-stat-card ' . $consoClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-gas-pump"></i></div>';
    $html .= '<div class="lt-stat-value">' . $consoDisplay . '</div>';
    $html .= '<div class="lt-stat-label">' . ($scope === 'comparaison' ? 'Conso moyenne flotte ' . $periodWord : 'Conso moyenne ' . $periodWord) . $periodDetail . '</div>';
    $html .= '</div></div>';

    $html .= '</div>';
    ob_start();
    include('statCardsPrestataires.php');
    $html .= ob_get_clean();
    return $html;
}

function getDashboardChartsVoyages()
{
    $scope = getVoyagesScope();
    [$dateFrom, $dateTo] = getVoyagesPeriod();
    $isCustom = getVoyagesPeriodIsCustom();
    $periodLabel = $isCustom ? ' (du ' . date('d/m/Y', strtotime($dateFrom)) . ' au ' . date('d/m/Y', strtotime($dateTo)) . ')' : '';
    $vsObjSuffix = $isCustom ? $periodLabel : ' (30 jours)';
    $html = '<div class="row g-3 mb-3">';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">'
        . ($scope === 'comparaison' ? 'Voyages flotte vs externes vs Objectifs' : 'Voyages vs Objectifs') . $vsObjSuffix . '</h2></div>';
    $html .= '<div id="chart-voyages-vs-obj" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">'
        . ($scope === 'comparaison' ? 'Top destinations (flotte vs externes)' : 'Top destinations') . $periodLabel . '</h2></div>';
    $html .= '<div id="chart-top-dest" style="height: 350px;"></div></div></div>';
    if ($scope !== 'externe') {
        $html .= '<div class="col-12"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Consommation par véhicule'
            . ($isCustom ? $periodLabel : ' (mois en cours)') . '</h2></div>';
        $html .= '<div id="chart-conso" style="height: 400px;"></div></div></div>';
    }
    $html .= '</div>';

    if ($scope !== 'externe') {
        $html .= '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Véhicules inactifs (7+ jours)</h2></div>';
        $html .= '<table id="table-inactifs" class="table table-striped no-datatable"><thead><tr>
        <th>Véhicule</th><th>Chauffeur</th><th>Dernier voyage</th></tr></thead><tbody></tbody></table></div>';
    }

    $html .= '<script>
    var scopeStatsVoyages = ' . json_encode($scope) . ';
    var customPeriodStats = ' . ($isCustom ? 'true' : 'false') . ';
    var dateFromStats = ' . json_encode($dateFrom) . ';
    var dateToStats = ' . json_encode($dateTo) . ';
    var rangeStats = customPeriodStats ? "&dateFrom=" + dateFromStats + "&dateTo=" + dateToStats : "";
    google.charts.load("current", {packages: ["corechart", "table"]});
    google.charts.setOnLoadCallback(function() {
        $.ajax({type:"post", data:"load-voyages-vs-obj=1&days=30&scope=" + scopeStatsVoyages + rangeStats, dataType:"json"})
        .done(function(e) {
            if (!e.data || !e.data.length) return;
            var dt = new google.visualization.DataTable();
            dt.addColumn("string", "Date");
            var c = new google.visualization.LineChart(document.getElementById("chart-voyages-vs-obj"));
            if (scopeStatsVoyages === "comparaison") {
                dt.addColumn("number", "Voyages flotte");
                dt.addColumn("number", "Voyages externes");
                dt.addColumn("number", "Objectif");
                e.data.forEach(function(r) { dt.addRow([r.date, r.voyages_flotte, r.voyages_externe, r.objectif]); });
                c.draw(dt, {title:"Voyages vs Objectifs journaliers", curveType:"function", legend:{position:"bottom"}, colors:["#5D54A4","#E67E22","#E74C3C"], chartArea:{width:"85%", height:"75%"}});
            } else {
                dt.addColumn("number", "Voyages");
                dt.addColumn("number", "Objectif");
                e.data.forEach(function(r) { dt.addRow([r.date, r.voyages, r.objectif]); });
                c.draw(dt, {title:"Voyages vs Objectifs journaliers", curveType:"function", legend:{position:"bottom"}, colors:["#5D54A4","#E74C3C"], chartArea:{width:"85%", height:"75%"}});
            }
        });
        $.ajax({type:"post", data:"load-top-destinations=1&limit=10&scope=" + scopeStatsVoyages + rangeStats, dataType:"json"})
        .done(function(e) {
            if (!e.data || !e.data.length) return;
            var dt = new google.visualization.DataTable();
            dt.addColumn("string", "Destination");
            var c = new google.visualization.ColumnChart(document.getElementById("chart-top-dest"));
            if (scopeStatsVoyages === "comparaison") {
                dt.addColumn("number", "Flotte");
                dt.addColumn("number", "Externes");
                e.data.forEach(function(r) { dt.addRow([r.lib_destination, parseInt(r.nb_voyages_flotte), parseInt(r.nb_voyages_externe)]); });
                c.draw(dt, {title:"Top destinations (flotte vs externes)", colors:["#5D54A4","#E67E22"], chartArea:{width:"80%", height:"70%"}});
            } else {
                dt.addColumn("number", "Nb voyages");
                dt.addColumn("number", "Km total");
                e.data.forEach(function(r) { dt.addRow([r.lib_destination, parseInt(r.nb_voyages), parseFloat(r.total_km)]); });
                c.draw(dt, {title:"Top destinations", colors:["#5D54A4","#7C78B8"], chartArea:{width:"80%", height:"70%"}});
            }
        });';
    if ($scope !== 'externe') {
        $html .= '
        $.ajax({type:"post", data:"load-conso-per-vehicle=1" + rangeStats, dataType:"json"})
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
            c.draw(dt, {title:"Conso L/100km' . ($isCustom ? $periodLabel : ' (mois en cours)') . '", legend:"none", colors:["#E67E22"], chartArea:{width:"80%", height:"70%"}});
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
        });';
    }
    $html .= '
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