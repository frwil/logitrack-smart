<?php
class TrajetController extends BaseController
{
    private TrajetRepository $repo;

    public function __construct(TrajetRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never {
        $row = $this->repo->findById((int)$this->post('id-destination-forModal'));
        if (!$row) $this->jsonError('Trajet introuvable', 404);
        $this->json(['data' => $row]);
    }

    public function update(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateById((int)$this->post('id-destination'), $this->post('nom-upd-destination'), (int)$this->post('distance-destination-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function delete(): never {
        $ok = $this->repo->deleteById((int)$this->post('id-destination-forDel'));
        $ok ? $this->json() : $this->jsonError('Erreur');
    }

    public function create(): never {
        $nom = trim($this->post('nom-destination'));
        $distance = (int)$this->post('distance-destination');
        if ($nom === '') $this->jsonError('Le champ est obligatoire');
        try { $this->repo->insert($nom, $distance); $this->json(); }
        catch (\mysqli_sql_exception $e) { $this->jsonError("Erreur lors de l'enregistrement"); }
    }

    public function refreshOptions(): never {
        $rows = $this->repo->findAll();
        $html = '';
        foreach ($rows as $r) {
            $html .= "<option value='{$r['id_destination']}'>" . h($r['lib_destination']) . "</option>";
        }
        $this->json(['html' => $html]);
    }

    /** Return available destinations (excluding already selected) as <option> HTML — used by voyage modal. */
    public function availableForVoyage(): never
    {
        $trajets = json_decode($this->post('trajets'), true);
        if (empty($trajets)) $trajets = [];
        try {
            $rows = $this->repo->findAllExcept($trajets);
            $html = '';
            foreach ($rows as $r) {
                $html .= "<option value='" . $r['id_destination'] . "' dest-km='" . h((string)$r['distance_destination']) . "'>" . h($r['lib_destination']) . " (" . h((string)$r['distance_destination']) . " km)</option>";
            }
            $this->json(['html' => $html]);
        } catch (\Throwable $e) {
            error_log('TrajetController::availableForVoyage error: ' . $e->getMessage());
            $this->jsonError('Erreur lors du chargement des trajets : ' . $e->getMessage());
        }
    }
}
