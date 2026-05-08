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
                    $this->post('vh-upd-vd'),
                    $this->post('date-upd-vd'),
                    (int)$this->post('km-upd-av-vd'),
                    (int)$this->post('km-upd-next-vd'),
                    $this->post('id-upd-pt-vd'),
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
                $this->maintenanceRepo->deleteVidangeByHash($this->post('del-vd-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function historiqueVidange(): never
    {
        $code = $this->post('cd-vd-hist');
        $regionSel = (int)$_SESSION['usr-con']['region-sel'];
        $rows = $this->maintenanceRepo->findHistoriqueVidange($code, $regionSel);

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
            echo "<tr><td>" . date('d M Y', strtotime($r['date_vidange'])) . "</td><td>{$r['date_vidange']}</td><td>{$r['km_vidange']}</td><td>{$r['km_prochaine_vidange']}</td><td>{$r['nom_prestataire']}</td><td>{$r['commentaire_vidange']}</td></tr>";
            $i++;
        endforeach;
        $html = ob_get_clean();

        $this->json(['html' => $html]);
    }

    // ---- Prestataire ----

    public function fetchPrestataire(): never
    {
        $row = $this->maintenanceRepo->findPrestataireByHash($this->post('c-pt-s'));
        if (!$row) {
            $this->jsonError('Prestataire introuvable', 404);
        }
        unset($row[0], $row['id_prestataire']);
        $this->json(['data' => $row]);
    }

    public function updatePrestataire(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->updatePrestataireByHash(
                    $this->post('id-upd-pt'),
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
                $this->maintenanceRepo->deletePrestataireByHash($this->post('del-pt-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    // ---- Centre de coûts ----

    public function fetchCentreCout(): never
    {
        $row = $this->maintenanceRepo->findCentreCoutByHash($this->post('c-cc-s'));
        if (!$row) {
            $this->jsonError('Centre de coût introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function deleteCentreCout(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->deleteCentreCoutByHash($this->post('del-cc-id'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    // ---- Relevé KMS ----

    public function fetchPeriodes(): never
    {
        $semPer = date('Y-m-01', strtotime($this->post('semPer')));
        $psem = getPremiereSemaineDuMois($semPer);
        $rows = $this->maintenanceRepo->findPeriodesReleve($psem[0], date('Y-m-t', strtotime($semPer)));

        ob_start();
        foreach ($rows as $r):
            echo "<option value='" . sha1($r['periode_releve']) . "'>{$r['periode_releve']}</option>";
        endforeach;
        $this->json(['html' => ob_get_clean()]);
    }

    public function fetchKmReleve(): never
    {
        $row = $this->maintenanceRepo->findKmReleve($this->post('perSem'), $this->post('vhPer'));
        $this->json(['kms' => $row ? $row['km_releve'] : 0]);
    }

    public function updateReleve(): never
    {
        try {
            $this->maintenanceRepo->transactional(function () {
                $this->maintenanceRepo->updateReleveKms(
                    (int)$this->post('kmsRel'),
                    $this->post('updRel'),
                    $this->post('vhRel')
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }
}
