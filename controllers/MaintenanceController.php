<?php
/**
 * Maintenance controller — vidange, prestataire, centre coûts, bons réparation, relevé KMS.
 */
class MaintenanceController extends BaseController
{
    private MaintenanceRepository $maintenanceRepo;

    public function __construct(MaintenanceRepository $maintenanceRepo)
    {
        $this->maintenanceRepo = $maintenanceRepo;
    }

    // ---- Vidange ----

    public function fetchVidange(): never
    {
        $code = $this->post('c-vd-s');
        $row = $this->maintenanceRepo->findVidangeByCode($code);
        if (!$row) {
            $this->jsonError('Vidange introuvable', 404);
        }
        unset($row[0], $row['id_vidange']);
        $this->json(['data' => $row]);
    }

    public function updateVidange(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->updateVidangeByCode(
                    $this->post('c-upd-vd'),
                    (int)$this->post('vh-upd-vd'),
                    $this->post('date-upd-vd'),
                    (int)$this->post('km-upd-av-vd'),
                    (int)$this->post('km-upd-next-vd'),
                    (int)$this->post('id-upd-pt-vd'),
                    $this->post('comment-upd-vd') ?: null
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function deleteVidange(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->deleteVidangeById((int)$this->post('del-vd-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function historiqueVidange(): never
    {
        $code = $this->post('cd-vd-hist');
        $rows = $this->maintenanceRepo->findHistoriqueVidangeByContext($code, getContextRegions(), getContextEntities());

        // Build HTML table (legacy — could be moved to view layer later)
        ob_start();
        $i = 0;
        foreach ($rows as $r):
            if ($i === 0):
                echo "<tr><td colspan='6' class='text-center'>HISTORIQUE VIDANGE VEHICULE</td></tr>";
                echo "<tr><td>Véhicule</td><td colspan='3'>{$r['immatriculation_vehicule']} - {$r['nom_chauffeur']}</td><td>Km Actuel</td><td>{$r['kms_actuel']}</td></tr>";
                echo "<tr><td colspan='6'></td></tr>";
                echo "<tr><td>Date vidange</td><td>Date</td><td>KM (avant vidange)</td><td>KM (prochaine vidange)</td><td>Prestataire</td><td>Commentaire</td></tr>";
            endif;
            echo "<tr><td>" . ($r['date_vidange'] ? date('d M Y', strtotime($r['date_vidange'])) : '') . "</td><td>{$r['date_vidange']}</td><td>{$r['km_vidange']}</td><td>{$r['km_prochaine_vidange']}</td><td>{$r['nom_prestataire']}</td><td>{$r['commentaire_vidange']}</td></tr>";
            $i++;
        endforeach;
        $html = ob_get_clean();

        $this->json(['html' => $html]);
    }

    // ---- Prestataire ----

    public function fetchPrestataire(): never
    {
        $row = $this->maintenanceRepo->findPrestataireById((int)$this->post('c-pt-s'));
        if (!$row) {
            $this->jsonError('Prestataire introuvable', 404);
        }
        unset($row[0]);
        $this->json(['data' => $row]);
    }

    public function updatePrestataire(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->updatePrestataireById(
                    (int)$this->post('id-upd-pt'),
                    $this->post('nom-upd-pt'),
                    $this->post('contact-upd-pt') ?: null,
                    $this->post('localisation-upd-pt') ?: null
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function deletePrestataire(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->deletePrestataireById((int)$this->post('del-pt-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    // ---- Centre de coûts ----

    public function fetchAllCentresCouts(): never
    {
        $rows = $this->maintenanceRepo->findAllCentresCouts();
        $html = '';
        foreach ($rows as $r) {
            $html .= "<option value='{$r['id_centre_cout']}'>" . h($r['lib_centre_cout']) . "</option>";
        }
        $this->json(['html' => $html]);
    }

    public function createCentreCout(): never
    {
        $nom = $this->post('nom-cc');
        if (!$nom) {
            $this->jsonError('La désignation est obligatoire');
        }
        try {
            $id = $this->maintenanceRepo->transactional(function () use ($nom) {
                return $this->maintenanceRepo->insertCentreCout($nom);
            });
            $this->json(['data' => ['id' => $id]]);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $this->jsonError('Ce centre de coût existe déjà');
            }
            $this->jsonError('Erreur lors de la création');
        }
    }

    public function updateCentreCout(): never
    {
        $id = (int)$this->post('id-upd-cc');
        $nom = $this->post('nom-upd-cc');
        if (!$id || !$nom) {
            $this->jsonError('Tous les champs sont obligatoires');
        }
        try {
            $this->maintenanceRepo->transactional(function () use ($id, $nom) {
                $this->maintenanceRepo->updateCentreCout($id, $nom);
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $this->jsonError('Ce centre de coût existe déjà');
            }
            $this->jsonError('Erreur lors de la modification');
        }
    }

    public function fetchCentreCout(): never
    {
        $row = $this->maintenanceRepo->findCentreCoutById((int)$this->post('c-cc-s'));
        if (!$row) {
            $this->jsonError('Centre de coût introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function deleteCentreCout(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->deleteCentreCoutById((int)$this->post('del-cc-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    // ---- Relevé KMS ----

    private function getPremiereSemaineDuMois(string $date): array
    {
        $premierJourDuMois = date('Y-m-01', strtotime($date));
        $jourDeLaSemaine = (int)date('w', strtotime($premierJourDuMois));
        $semaine = [];

        // Jours de la semaine précédente si le 1er n'est pas un lundi
        for ($i = $jourDeLaSemaine - 1; $i >= 0; $i--) {
            $semaine[] = date('Y-m-d', strtotime($premierJourDuMois . ' -' . ($i + 1) . ' days'));
        }
        for ($i = 0; count($semaine) < 7; $i++) {
            $semaine[] = date('Y-m-d', strtotime($premierJourDuMois . ' +' . $i . ' days'));
        }
        usort($semaine, fn($a, $b) => strtotime($a) - strtotime($b));
        return $semaine;
    }

    public function fetchPeriodes(): never
    {
        $input = $this->post('semPer');
        $semPer = $input ? date('Y-m-01', strtotime($input)) : date('Y-m-01');
        $psem = $this->getPremiereSemaineDuMois($semPer);
        $rows = $this->maintenanceRepo->findPeriodesReleve($psem[0], date('Y-m-t', strtotime($semPer)));

        ob_start();
        foreach ($rows as $r):
            echo "<option value='" . $r['periode_releve'] . "'>{$r['periode_releve']}</option>";
        endforeach;
        $this->json(['html' => ob_get_clean()]);
    }

    public function fetchKmReleve(): never
    {
        $row = $this->maintenanceRepo->findKmReleve($this->post('perSem'), (int)$this->post('vhPer'));
        $this->json(['kms' => $row ? $row['km_releve'] : 0]);
    }

    public function updateReleve(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->updateReleveKms(
                    (int)$this->post('kmsRel'),
                    $this->post('updRel'),
                    (int)$this->post('vhRel')
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    // ---- Prestataire create ----

    public function createPrestataire(): never
    {
        $nom = trim($this->post('nom-pt'));
        if ($nom === '') $this->jsonError('Le nom du prestataire est obligatoire');
        try {
            $this->maintenanceRepo->transactional(function () use ($nom) {
                $this->maintenanceRepo->insertPrestataire(
                    $nom,
                    $this->post('contact-pt') ?: null,
                    $this->post('localisation-pt') ?: null
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('Ce prestataire existe déjà');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    // ---- Vidange create ----

    public function createVidange(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->insertVidange(
                    (int)$this->post('vh-vd'),
                    $this->post('date-vd'),
                    (int)$this->post('km-av-vd'),
                    (int)$this->post('km-next-vd'),
                    (int)$this->post('id-pt-vd'),
                    $this->post('comment-vd') ?: null
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    // ---- Relevé KMS create ----

    public function createReleveKms(): never
    {
        $km = (int)$this->post('val-releve-kms');
        if ($km <= 0) $this->jsonError('La valeur du kilométrage est obligatoire');
        try {
            $this->maintenanceRepo->transactional(function () use ($km) {
                $this->maintenanceRepo->insertReleveKms(
                    (int)$this->post('vh-releve-kms'),
                    $this->post('per-releve-kms'),
                    $km,
                    $this->post('start-per'),
                    $this->post('end-per')
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    // ---- Bon de réparation ----

    public function fetchBonReparation(): never
    {
        $row = $this->maintenanceRepo->findBonReparationById((int)$this->post('c-br-s'));
        if (!$row) $this->jsonError('Bon de réparation introuvable', 404);
        unset($row[0]);
        $this->json(['data' => $row]);
    }

    public function deleteBonReparation(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->deleteBonReparationById((int)$this->post('del-br-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function updateBonReparation(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->updateBonReparation(
                    (int)$this->post('id-upd-br'),
                    $this->post('num-br-upd'),
                    (int)$this->post('vh-br-upd'),
                    $this->post('date-entree-br-upd'),
                    $this->post('diagnostic-br-upd'),
                    $this->post('type-execution-br-upd'),
                    (int)$this->post('prestataire-br-upd'),
                    (float)$this->post('montant-br-upd'),
                    $this->post('plus-moins-br-upd') ? (int)$this->post('plus-moins-br-upd') : null,
                    $this->post('plus-moins-val-br-upd') ? (float)$this->post('plus-moins-val-br-upd') : null,
                    $this->post('destination-br-upd'),
                    (int)$this->post('duree-br-upd'),
                    $this->post('date-justif-br-upd'),
                    (int)$this->post('centrecout-br-upd'),
                    $this->post('date-prevue-br-upd'),
                    $this->post('date-fin-br-upd'),
                    $this->post('observation-br-upd')
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function createBonReparation(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->insertBonReparation(
                    $this->post('num-br'),
                    (int)$this->post('vh-br'),
                    $this->post('date-entree-br'),
                    $this->post('diagnostic-br'),
                    $this->post('type-execution-br'),
                    (int)$this->post('prestataire-br'),
                    (float)$this->post('montant-br'),
                    $this->post('plus-moins-br') ? (int)$this->post('plus-moins-br') : null,
                    $this->post('plus-moins-val-br') ? (float)$this->post('plus-moins-val-br') : null,
                    $this->post('destination-br'),
                    (int)$this->post('duree-br'),
                    $this->post('date-justif-br'),
                    (int)$this->post('centrecout-br'),
                    $this->post('date-prevue-br'),
                    $this->post('date-fin-br'),
                    $this->post('observation-br')
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    // ---- Dashboard Analytics ----

    public function budgetProjection(): never
    {
        $rows = $this->maintenanceRepo->monthlyCostHistory(12, getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function providerComparison(): never
    {
        $rows = $this->maintenanceRepo->providerComparison(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function costPerKm(): never
    {
        $rows = $this->maintenanceRepo->costPerKm(
            getContextRegions(),
            getContextEntities(),
            $this->post('dateFrom'),
            $this->post('dateTo')
        );
        $this->json(['data' => $rows]);
    }

    public function costByCentre(): never
    {
        $rows = $this->maintenanceRepo->costByCentreCout(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function recurrence(): never
    {
        $rows = $this->maintenanceRepo->recurrenceByVehicle(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function dureeByDiagnostic(): never
    {
        $rows = $this->maintenanceRepo->avgDurationByDiagnostic(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function coutByType(): never
    {
        $rows = $this->maintenanceRepo->costByExecutionType(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function docsExpiration(): never
    {
        $rows = $this->maintenanceRepo->documentsExpiration(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function chauffeurImpact(): never
    {
        $rows = $this->maintenanceRepo->chauffeurMaintenanceImpact(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    public function repairConflicts(): never
    {
        $rows = $this->maintenanceRepo->repairVoyageConflicts(getContextRegions(), getContextEntities());
        $this->json(['data' => $rows]);
    }

    /** Single endpoint for all dashboard charts — one connection, no lock contention. */
    public function dashboardAll(): never
    {
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $dateTo = date('Y-m-d');
        $this->json(['data' => [
            'budget'        => $this->maintenanceRepo->monthlyCostHistory(12, $regionIds, $entiteIds),
            'centres'       => $this->maintenanceRepo->costByCentreCout($regionIds, $entiteIds),
            'typeExec'      => $this->maintenanceRepo->costByExecutionType($regionIds, $entiteIds),
            'diagnostics'   => $this->maintenanceRepo->avgDurationByDiagnostic($regionIds, $entiteIds),
            'providers'     => $this->maintenanceRepo->providerComparison($regionIds, $entiteIds),
            'recurrence'    => $this->maintenanceRepo->recurrenceByVehicle($regionIds, $entiteIds),
            'costPerKm'     => $this->maintenanceRepo->costPerKm($regionIds, $entiteIds, '2024-01-01', $dateTo),
            'docsExpiration' => $this->maintenanceRepo->documentsExpiration($regionIds, $entiteIds),
            'chauffeurImpact' => $this->maintenanceRepo->chauffeurMaintenanceImpact($regionIds, $entiteIds),
            'repairConflicts' => $this->maintenanceRepo->repairVoyageConflicts($regionIds, $entiteIds),
        ]]);
    }

    public function healthScoresHtml(): never
    {
        $rows = $this->maintenanceRepo->vehicleHealthScores(getContextRegions(), getContextEntities());
        if (!count($rows)) {
            $this->json(['html' => '<div class="alert alert-info">Aucun véhicule actif trouvé.</div>']);
        }

        $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Score de santé des véhicules</h2></div>';
        $html .= '<table id="table-health-scores" class="table table-striped no-datatable"><thead><tr>
            <th>Véhicule</th><th>Chauffeur</th><th>Km actuel</th><th>Proch. vidange</th>
            <th>Pannes (6 mois)</th><th>Coût total (' . devise() . ')</th><th>Score</th><th>État</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $score = (int)$r['score'];
            $color = $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger');
            $etat = $score >= 70 ? 'Bon' : ($score >= 40 ? 'Moyen' : 'Critique');
            $html .= '<tr>
                <td>' . h($r['immatriculation_vehicule']) . '</td>
                <td>' . h($r['nom_chauffeur']) . '</td>
                <td>' . ($r['km_actuel'] ?? '-') . '</td>
                <td>' . ($r['km_prochaine_vidange'] ?? '-') . '</td>
                <td>' . (int)($r['nb_pannes_6mois'] ?? 0) . '</td>
                <td>' . number_format((float)($r['total_cout'] ?? 0), 0, ',', ' ') . '</td>
                <td><span class="lt-badge lt-badge-' . $color . '">' . $score . '/100</span></td>
                <td><span class="text-' . $color . ' fw-bold">' . $etat . '</span></td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<script>$("#table-health-scores").DataTable({order:[[6,"asc"]], pageLength:25, destroy:true});</script>';
        $this->json(['html' => $html]);
    }

    public function upcomingVidangesHtml(): never
    {
        $rows = $this->maintenanceRepo->upcomingVidanges(60, getContextRegions(), getContextEntities());
        if (!count($rows)) {
            $this->json(['html' => '<div class="alert alert-info">Aucune vidange à prévoir dans les 60 jours.</div>']);
        }

        $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Planification des vidanges à venir (60 jours)</h2></div>';
        $html .= '<table id="table-upcoming-vidanges" class="table table-striped no-datatable"><thead><tr>
            <th>Véhicule</th><th>Chauffeur</th><th>Km actuel</th><th>Proch. vidange</th>
            <th>Km restants</th><th>Km/jour</th><th>Jours estimés</th><th>Urgence</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $urgenceClass = $r['urgence'] === 'Dépassée' ? 'danger' : ($r['urgence'] === 'Urgent' ? 'warning' : ($r['urgence'] === 'Bientôt' ? 'info' : 'success'));
            $html .= '<tr>
                <td>' . h($r['immatriculation_vehicule']) . '</td>
                <td>' . h($r['nom_chauffeur']) . '</td>
                <td>' . ($r['km_actuel'] ?? '-') . '</td>
                <td>' . ($r['km_prochaine_vidange'] ?? '-') . '</td>
                <td>' . (int)($r['km_restant'] ?? 0) . '</td>
                <td>' . ($r['km_moyen_jour'] ?? '-') . '</td>
                <td>' . (int)($r['jours_estimes'] ?? 0) . '</td>
                <td><span class="lt-badge lt-badge-' . $urgenceClass . '">' . h($r['urgence']) . '</span></td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<script>$("#table-upcoming-vidanges").DataTable({order:[[6,"asc"]], pageLength:25, destroy:true});</script>';
        $this->json(['html' => $html]);
    }
}
