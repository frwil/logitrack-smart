<?php
$vehiculeRepo = new VehiculeRepository($con);
$voyageRepo = new VoyageRepository($con);
$maintenanceRepo = new MaintenanceRepository($con);
$vehicules = $vehiculeRepo->findAllWithDetails();

// — Tab 1 dates (Transport et Consommations) —
$d1 = $_POST['date_debut'] ?? date('Y-m-d');
$f1 = $_POST['date_fin']   ?? date('Y-m-d');
$allVoyages1 = $voyageRepo->findAllForReportingByContext($d1, $f1, getContextRegions(), getContextEntities());
$byVeh1 = [];
foreach ($allVoyages1 as $v) {
    $vid = (int)$v['id_vehicule'];
    if (!isset($byVeh1[$vid])) $byVeh1[$vid] = [];
    $byVeh1[$vid][] = $v;
}

// — Tab 2 dates (Taux de remplissage) —
$d2 = $_POST['date_debut_r'] ?? date('Y-m-01');
$f2 = $_POST['date_fin_r']   ?? date('Y-m-t');
$allVoyages2 = $voyageRepo->findAllForReportingByContext($d2, $f2, getContextRegions(), getContextEntities());
$byVehTC2 = [];
foreach ($allVoyages2 as $v) {
    $vid = (int)$v['id_vehicule'];
    $tc  = (int)$v['id_type_chargement'];
    if (!isset($byVehTC2[$vid])) $byVehTC2[$vid] = [];
    if (!isset($byVehTC2[$vid][$tc])) $byVehTC2[$vid][$tc] = [];
    $byVehTC2[$vid][$tc][] = $v;
}

// — Tab 3 dates (Consommation) —
$d3 = $_POST['date_debut_c'] ?? date('Y-m-01');
$f3 = $_POST['date_fin_c']   ?? date('Y-m-t');
$allVoyages3 = $voyageRepo->findAllForReportingByContext($d3, $f3, getContextRegions(), getContextEntities());
$byVeh3 = [];
foreach ($allVoyages3 as $v) {
    $vid = (int)$v['id_vehicule'];
    if (!isset($byVeh3[$vid])) $byVeh3[$vid] = [];
    $byVeh3[$vid][] = $v;
}

// — Tab 4 (Coût au km) —
$d4 = $_POST['date_debut_ck'] ?? date('Y-m-01');
$f4 = $_POST['date_fin_ck']   ?? date('Y-m-t');
$costPerKmRows = $maintenanceRepo->costPerKm(getContextRegions(), getContextEntities(), $d4, $f4);

// — Tab 5 dates (Immobilisation) —
$d5 = $_POST['date_debut_i'] ?? date('Y-m-01');
$f5 = $_POST['date_fin_i']   ?? date('Y-m-t');
$allVoyages5 = $voyageRepo->findAllForReportingByContext($d5, $f5, getContextRegions(), getContextEntities());
$byVeh5 = [];
foreach ($allVoyages5 as $v) {
    $vid = (int)$v['id_vehicule'];
    if (!isset($byVeh5[$vid])) $byVeh5[$vid] = [];
    $byVeh5[$vid][] = $v;
}

// — Tab 6 dates (Destinations) —
$d6 = $_POST['date_debut_dest'] ?? date('Y-m-01');
$f6 = $_POST['date_fin_dest']   ?? date('Y-m-t');
$allVoyages6 = $voyageRepo->findAllForReportingByContext($d6, $f6, getContextRegions(), getContextEntities());

// — Tab 7 (Synthèse périodique) —
$d7 = $_POST['date_debut_s'] ?? date('Y-m-01');
$f7 = $_POST['date_fin_s']   ?? date('Y-m-t');
$allVoyages7 = $voyageRepo->findAllForReportingByContext($d7, $f7, getContextRegions(), getContextEntities());
$maintenanceMonthly = $maintenanceRepo->monthlyCostHistory(24, getContextRegions(), getContextEntities());
$costByIdx = [];
foreach ($maintenanceMonthly as $m) $costByIdx[$m['mois']] = (float)$m['total'];

function dateField(string $name, string $default): string {
    $v = isset($_POST[$name]) ? h($_POST[$name]) : $default;
    return "<input type='date' class='form-control' id='$name' name='$name' required value='$v'>";
}
function badge(float $pct): string {
    $c = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warning text-dark' : 'bg-danger');
    return "<span class='badge $c'>" . round($pct) . "%</span>";
}
?>
<div class="container mt-4">
  <ul class="nav nav-tabs" id="reportTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="poussins-tab" data-bs-toggle="tab" data-bs-target="#poussins" type="button" role="tab">Transport et Consommations</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="remplissage-tab" data-bs-toggle="tab" data-bs-target="#remplissage" type="button" role="tab">Taux de remplissage</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="conso-tab" data-bs-toggle="tab" data-bs-target="#conso" type="button" role="tab">Consommation carburant</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="coutkm-tab" data-bs-toggle="tab" data-bs-target="#coutkm" type="button" role="tab">Coût au km</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="immobilisation-tab" data-bs-toggle="tab" data-bs-target="#immobilisation" type="button" role="tab">Temps d'immobilisation</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="destinations-tab" data-bs-toggle="tab" data-bs-target="#destinations" type="button" role="tab">Comparaison destinations</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="synthese-tab" data-bs-toggle="tab" data-bs-target="#synthese" type="button" role="tab">Synthèse périodique</button>
    </li>
  </ul>
  <div class="tab-content" id="reportTabsContent">

<!-- ============ TAB 1 : Transport et Consommations ============ -->
    <div class="tab-pane fade show active p-3" id="poussins" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut" class="form-label">Date de début</label><?php echo dateField('date_debut', date('Y-m-d')); ?></div>
          <div class="col-6"><label for="date_fin" class="form-label">Date de fin</label><?php echo dateField('date_fin', date('Y-m-d')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_report" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Véhicule</th><th>Date</th><th>Type chargement</th><th>Qté chargement</th><th>Kms parcourus</th><th>Consommation</th></tr></thead>
        <tbody>
          <?php
          $t1 = '';
          foreach ($vehicules as $row) {
              $vid = (int)$row['id_vehicule'];
              $voyages = $byVeh1[$vid] ?? [];
              $cnt = count($voyages);
              $sumC = []; $sumK = []; $sumL = [];
              foreach ($voyages as $r2) {
                  $tc = (int)$r2['id_type_chargement'];
                  if (!isset($sumC[$tc])) { $sumC[$tc] = 0; $sumK[$tc] = 0; $sumL[$tc] = 0; }
                  $sumC[$tc] += $r2['qte_chargement'];
                  $sumK[$tc] += $r2['distance_destination'];
                  $sumL[$tc] += $r2['qte_carburant'];
              }
              $summaries = [];
              foreach ($voyages as $r2) {
                  $tc = (int)$r2['id_type_chargement'];
                  if (!isset($summaries[$tc])) {
                      $summaries[$tc] = h($row['immatriculation_vehicule']) . " - " . h($r2['lib_type_chargement']) . " (" . $sumC[$tc] . " - " . $sumK[$tc] . "kms - " . $sumL[$tc] . "L)";
                  }
              }
              foreach ($voyages as $r2) {
                  $tc = (int)$r2['id_type_chargement'];
                  $t1 .= "<tr><td>" . h($row['immatriculation_vehicule']) . " ($cnt voyages)</td>";
                  $t1 .= "<td>" . ($r2['date_voyage'] ? date('d/m/Y', strtotime($r2['date_voyage'])) : '') . "</td>";
                  $t1 .= "<td>" . $summaries[$tc] . "</td><td>" . h($r2['qte_chargement']) . "</td>";
                  $t1 .= "<td>" . h($r2['distance_destination']) . "</td><td>" . h($r2['qte_carburant']) . "</td></tr>";
              }
              if ($cnt == 0) $t1 .= "<tr><td>" . h($row['immatriculation_vehicule']) . " (0)</td><td></td><td>Aucun voyage</td><td></td><td></td><td></td></tr>";
          }
          echo $t1;
          ?>
        </tbody>
      </table>
    </div>

<!-- ============ TAB 2 : Taux de remplissage (par type de chargement) ============ -->
    <div class="tab-pane fade p-3" id="remplissage" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut_r" class="form-label">Date de début</label><?php echo dateField('date_debut_r', date('Y-m-01')); ?></div>
          <div class="col-6"><label for="date_fin_r" class="form-label">Date de fin</label><?php echo dateField('date_fin_r', date('Y-m-t')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_remplissage" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Véhicule</th><th>Type chargement</th><th>Capacité</th><th>Qté totale</th><th>Nb voyages</th><th>Tx moyen</th><th>Tx max</th><th>Tx min</th></tr></thead>
        <tbody>
          <?php
          $t2 = '';
          foreach ($vehicules as $row) {
              $vid = (int)$row['id_vehicule'];
              $capacite = (float)$row['capacite_consommation_vehicule'];
              $byTC = $byVehTC2[$vid] ?? [];
              if (empty($byTC)) {
                  $t2 .= "<tr><td>" . h($row['immatriculation_vehicule']) . "</td><td>-</td><td>" . h($capacite) . "</td><td colspan='5'>Aucun voyage</td></tr>";
                  continue;
              }
              foreach ($byTC as $tc => $voyages) {
                  $cnt = count($voyages);
                  $totalQte = 0; $tMax = 0; $tMin = $capacite > 0 ? PHP_FLOAT_MAX : 0;
                  $tcLabel = h($voyages[0]['lib_type_chargement']);
                  foreach ($voyages as $v) {
                      $qte = (float)$v['qte_chargement'];
                      $totalQte += $qte;
                      if ($capacite > 0) {
                          $taux = ($qte / $capacite) * 100;
                          if ($taux > $tMax) $tMax = $taux;
                          if ($taux < $tMin) $tMin = $taux;
                      }
                  }
                  if ($capacite <= 0) { $tMin = 0; $tMax = 0; }
                  $tMoyen = $capacite > 0 ? ($totalQte / ($cnt * $capacite)) * 100 : 0;
                  $t2 .= "<tr><td>" . h($row['immatriculation_vehicule']) . "</td><td>$tcLabel</td><td>" . h($capacite) . "</td>";
                  $t2 .= "<td>" . h($totalQte) . "</td><td>$cnt</td>";
                  $t2 .= "<td>" . badge($tMoyen) . "</td><td>" . round($tMax) . "%</td><td>" . round($tMin) . "%</td></tr>";
              }
          }
          echo $t2;
          ?>
        </tbody>
      </table>
    </div>

<!-- ============ TAB 3 : Consommation carburant (L/100km) ============ -->
    <div class="tab-pane fade p-3" id="conso" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut_c" class="form-label">Date de début</label><?php echo dateField('date_debut_c', date('Y-m-01')); ?></div>
          <div class="col-6"><label for="date_fin_c" class="form-label">Date de fin</label><?php echo dateField('date_fin_c', date('Y-m-t')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_conso" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Véhicule</th><th>Nb voyages</th><th>Total km</th><th>Total L</th><th>L/100km</th><th>km/L</th></tr></thead>
        <tbody>
          <?php
          $t3 = '';
          $totKm3 = 0; $totL3 = 0;
          foreach ($vehicules as $row) {
              $vid = (int)$row['id_vehicule'];
              $voyages = $byVeh3[$vid] ?? [];
              $cnt = count($voyages);
              $km = 0; $l = 0;
              foreach ($voyages as $v) { $km += $v['distance_destination']; $l += $v['qte_carburant']; }
              $totKm3 += $km; $totL3 += $l;
              if ($cnt == 0) {
                  $t3 .= "<tr><td>" . h($row['immatriculation_vehicule']) . "</td><td colspan='5'>Aucun voyage</td></tr>";
                  continue;
              }
              $l100 = $km > 0 ? round(($l / $km) * 100, 1) : 0;
              $kml  = $l > 0 ? round($km / $l, 1) : 0;
              $cls = $l100 < 20 ? 'bg-success' : ($l100 < 35 ? 'bg-warning text-dark' : 'bg-danger');
              $t3 .= "<tr><td>" . h($row['immatriculation_vehicule']) . "</td><td>$cnt</td><td>" . h($km) . "</td><td>" . h($l) . "</td>";
              $t3 .= "<td><span class='badge $cls'>$l100 L/100km</span></td><td>$kml km/L</td></tr>";
          }
          if ($totKm3 > 0) {
              $avg100 = round(($totL3 / $totKm3) * 100, 1);
              $t3 .= "<tr class='table-dark fw-bold'><td>MOYENNE GLOBALE</td><td></td><td>" . h($totKm3) . "</td><td>" . h($totL3) . "</td><td>$avg100 L/100km</td><td></td></tr>";
          }
          echo $t3;
          ?>
        </tbody>
      </table>
    </div>

<!-- ============ TAB 4 : Coût au km ============ -->
    <div class="tab-pane fade p-3" id="coutkm" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut_ck" class="form-label">Date de début</label><?php echo dateField('date_debut_ck', date('Y-m-01')); ?></div>
          <div class="col-6"><label for="date_fin_ck" class="form-label">Date de fin</label><?php echo dateField('date_fin_ck', date('Y-m-t')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_coutkm" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Véhicule</th><th>Coût maintenance</th><th>Km parcourus</th><th>Coût maintenance / km</th></tr></thead>
        <tbody>
          <?php
          $t4 = '';
          $totalCost4 = 0; $totalKm4 = 0;
          // Index cost data by immat
          $costByIdx2 = [];
          foreach ($costPerKmRows as $r) {
              $immat = $r['immatriculation_vehicule'];
              $costByIdx2[$immat] = ['cout' => (float)$r['total_cout'], 'km' => (int)($r['km_max'] - $r['km_min'])];
              $totalCost4 += (float)$r['total_cout'];
              $totalKm4 += (int)($r['km_max'] - $r['km_min']);
          }
          foreach ($vehicules as $row) {
              $immat = $row['immatriculation_vehicule'];
              if (isset($costByIdx2[$immat])) {
                  $c = $costByIdx2[$immat];
                  $cpk = $c['km'] > 0 ? round($c['cout'] / $c['km'], 2) : 0;
                  $t4 .= "<tr><td>" . h($immat) . "</td><td>" . number_format($c['cout'], 0, ',', ' ') . " FCFA</td><td>" . h($c['km']) . "</td><td>" . $cpk . " FCFA/km</td></tr>";
              } else {
                  $t4 .= "<tr><td>" . h($immat) . "</td><td colspan='3'>Aucune donnée de maintenance</td></tr>";
              }
          }
          if ($totalKm4 > 0) {
              $avgCpk = round($totalCost4 / $totalKm4, 2);
              $t4 .= "<tr class='table-dark fw-bold'><td>TOTAL</td><td>" . number_format($totalCost4, 0, ',', ' ') . " FCFA</td><td>" . h($totalKm4) . "</td><td>$avgCpk FCFA/km</td></tr>";
          }
          echo $t4;
          ?>
        </tbody>
      </table>
      <p class="text-muted mt-2">Coûts issus des bons de réparation (maintenance). Le carburant n'est pas inclus.</p>
    </div>

<!-- ============ TAB 5 : Temps d'immobilisation ============ -->
    <div class="tab-pane fade p-3" id="immobilisation" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut_i" class="form-label">Date de début</label><?php echo dateField('date_debut_i', date('Y-m-01')); ?></div>
          <div class="col-6"><label for="date_fin_i" class="form-label">Date de fin</label><?php echo dateField('date_fin_i', date('Y-m-t')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_immobilisation" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Véhicule</th><th>Nb voyages</th><th>Jours dans période</th><th>Jours avec voyages</th><th>Jours sans voyage</th><th>Taux utilisation</th></tr></thead>
        <tbody>
          <?php
          $t5 = '';
          $periodStart = new DateTime($d5);
          $periodEnd   = new DateTime($f5);
          $totalDays = (int)$periodStart->diff($periodEnd)->days + 1;
          foreach ($vehicules as $row) {
              $vid = (int)$row['id_vehicule'];
              $voyages = $byVeh5[$vid] ?? [];
              $cnt = count($voyages);
              $activeDays = [];
              foreach ($voyages as $v) {
                  if ($v['date_voyage']) $activeDays[$v['date_voyage']] = true;
              }
              $daysActive = count($activeDays);
              $daysIdle = max(0, $totalDays - $daysActive);
              $useRate = $totalDays > 0 ? round(($daysActive / $totalDays) * 100) : 0;
              $cls = $useRate >= 70 ? 'bg-success' : ($useRate >= 40 ? 'bg-warning text-dark' : 'bg-danger');
              $t5 .= "<tr><td>" . h($row['immatriculation_vehicule']) . "</td><td>$cnt</td><td>$totalDays</td><td>$daysActive</td><td>$daysIdle</td>";
              $t5 .= "<td><span class='badge $cls'>$useRate%</span></td></tr>";
          }
          echo $t5;
          ?>
        </tbody>
      </table>
    </div>

<!-- ============ TAB 6 : Comparaison destinations ============ -->
    <div class="tab-pane fade p-3" id="destinations" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut_dest" class="form-label">Date de début</label><?php echo dateField('date_debut_dest', date('Y-m-01')); ?></div>
          <div class="col-6"><label for="date_fin_dest" class="form-label">Date de fin</label><?php echo dateField('date_fin_dest', date('Y-m-t')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_destinations" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Destination</th><th>Nb voyages</th><th>Qté totale</th><th>Total km</th><th>Total L</th><th>Km moyen / voyage</th></tr></thead>
        <tbody>
          <?php
          $t6 = '';
          $destinations = [];
          foreach ($allVoyages6 as $v) {
              $dest = $v['lib_destination'] ?? 'Inconnue';
              if (!isset($destinations[$dest])) $destinations[$dest] = ['count' => 0, 'qte' => 0, 'km' => 0, 'l' => 0];
              $destinations[$dest]['count']++;
              $destinations[$dest]['qte'] += (float)$v['qte_chargement'];
              $destinations[$dest]['km']  += (float)$v['distance_destination'];
              $destinations[$dest]['l']   += (float)$v['qte_carburant'];
          }
          uasort($destinations, fn($a, $b) => $b['qte'] <=> $a['qte']);
          foreach ($destinations as $dest => $d) {
              $avgKm = $d['count'] > 0 ? round($d['km'] / $d['count']) : 0;
              $t6 .= "<tr><td>" . h($dest) . "</td><td>" . $d['count'] . "</td><td>" . h($d['qte']) . "</td>";
              $t6 .= "<td>" . h($d['km']) . "</td><td>" . h($d['l']) . "</td><td>$avgKm</td></tr>";
          }
          if (empty($destinations)) $t6 .= "<tr><td colspan='6'>Aucune donnée</td></tr>";
          echo $t6;
          ?>
        </tbody>
      </table>
    </div>

<!-- ============ TAB 7 : Synthèse périodique (mensuelle) ============ -->
    <div class="tab-pane fade p-3" id="synthese" role="tabpanel">
      <form method="post">
        <div class="mb-3 row">
          <div class="col-6"><label for="date_debut_s" class="form-label">Date de début</label><?php echo dateField('date_debut_s', date('Y-m-01')); ?></div>
          <div class="col-6"><label for="date_fin_s" class="form-label">Date de fin</label><?php echo dateField('date_fin_s', date('Y-m-t')); ?></div>
        </div>
        <button type="submit" class="btn btn-primary">Générer le rapport</button>
      </form>
      <hr>
      <table id="table_synthese" class="table table-striped table-bordered no-datatable" style="width:100%">
        <thead><tr><th>Mois</th><th>Nb voyages</th><th>Total km</th><th>Total L</th><th>Qté chargée</th><th>L/100km</th><th>Coût maintenance</th></tr></thead>
        <tbody>
          <?php
          $t7 = '';
          $byMonth = [];
          foreach ($allVoyages7 as $v) {
              $mois = substr($v['date_voyage'] ?? '', 0, 7);
              if (!$mois) continue;
              if (!isset($byMonth[$mois])) $byMonth[$mois] = ['count' => 0, 'km' => 0, 'l' => 0, 'qte' => 0];
              $byMonth[$mois]['count']++;
              $byMonth[$mois]['km']  += (float)$v['distance_destination'];
              $byMonth[$mois]['l']   += (float)$v['qte_carburant'];
              $byMonth[$mois]['qte'] += (float)$v['qte_chargement'];
          }
          ksort($byMonth);
          foreach ($byMonth as $mois => $d) {
              $l100 = $d['km'] > 0 ? round(($d['l'] / $d['km']) * 100, 1) : 0;
              $cout = $costByIdx[$mois] ?? 0;
              $t7 .= "<tr><td>$mois</td><td>" . $d['count'] . "</td><td>" . h($d['km']) . "</td><td>" . h($d['l']) . "</td>";
              $t7 .= "<td>" . h($d['qte']) . "</td><td>$l100</td><td>" . number_format($cout, 0, ',', ' ') . " FCFA</td></tr>";
          }
          if (empty($byMonth)) $t7 .= "<tr><td colspan='7'>Aucune donnée</td></tr>";
          echo $t7;
          ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
<script>
  const mainDiv = $('#reportTabs').parent().parent().parent()
  mainDiv.find('.col-2').remove()
  mainDiv.find('.col-10').removeClass('col-10').addClass('col-12')

  const dtLang = {
    "lengthMenu": "Afficher _MENU_ enregistrements par page",
    "zeroRecords": "Aucun enregistrement trouvé",
    "info": "Affichage de la page _PAGE_ sur _PAGES_",
    "infoEmpty": "Aucun enregistrement disponible",
    "infoFiltered": "(filtré à partir de _MAX_ enregistrements au total)",
    "search": "Rechercher :",
    "paginate": { "first": "Premier", "last": "Dernier", "next": "Suivant", "previous": "Précédent" }
  };

  $('#table_report').DataTable({
    columnDefs: [{ targets: 0, searchable: false }],
    language: dtLang, ordering: false, dom: 'Bfrtip', buttons: ['excel'],
    order: [[0, "desc"]],
    rowGroup: { dataSrc: [0,3] },
    columnDefs: [{ targets: [0,3], visible: false }]
  });
  $('#table_remplissage').DataTable({ language: dtLang, ordering: true, dom: 'Bfrtip', buttons: ['excel'], order: [[5, "desc"]] });
  $('#table_conso').DataTable({ language: dtLang, ordering: true, dom: 'Bfrtip', buttons: ['excel'], order: [[4, "asc"]] });
  $('#table_coutkm').DataTable({ language: dtLang, ordering: true, dom: 'Bfrtip', buttons: ['excel'], order: [[3, "desc"]] });
  $('#table_immobilisation').DataTable({ language: dtLang, ordering: true, dom: 'Bfrtip', buttons: ['excel'], order: [[5, "asc"]] });
  $('#table_destinations').DataTable({ language: dtLang, ordering: true, dom: 'Bfrtip', buttons: ['excel'], order: [[1, "desc"]] });
  $('#table_synthese').DataTable({ language: dtLang, ordering: true, dom: 'Bfrtip', buttons: ['excel'], order: [[0, "desc"]] });
</script>
