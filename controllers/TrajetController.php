<?php
class TrajetController extends BaseController
{
    private TrajetRepository $repo;

    public function __construct(TrajetRepository $repo) { $this->repo = $repo; }

    /** Get exploded rights array for the 'voyages' object. */
    private function getVoyageRights(): array
    {
        return getUserRightsFor('voyages');
    }

    /** Check a voyages sub-right with backward-compat fallback. */
    private function hasVoyageSubRight(string $specific, string $fallback): bool
    {
        $trajetSpecifics = ['viewtrajet','savetrajet','updtrajet','deltrajet'];
        return hasSubRight($specific, $fallback, $this->getVoyageRights(), $trajetSpecifics);
    }

    /** Require a voyages sub-right — die with 403 if missing. */
    private function requireVoyageSubRight(string $specific, string $fallback): void
    {
        if (!$this->hasVoyageSubRight($specific, $fallback)) {
            $this->jsonError('Accès non autorisé', 403);
        }
    }

    public function fetchByHash(): never {
        $this->requireVoyageSubRight('viewtrajet', 'view');
        $row = $this->repo->findById((int)$this->post('id-destination-forModal'));
        if (!$row) $this->jsonError('Trajet introuvable', 404);
        $this->json(['data' => $row]);
    }

    public function update(): never {
        $this->requireVoyageSubRight('updtrajet', 'upd');
        $distance = (int)$this->post('distance-destination-upd');
        if ($distance < 1) $this->jsonError('La distance doit être d\'au moins 1 km');
        try { $this->repo->transactional(fn() =>
            $this->repo->updateById((int)$this->post('id-destination'), $this->post('nom-upd-destination'), $distance)
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function delete(): never {
        $this->requireVoyageSubRight('deltrajet', 'del');

        // Only superadmin can delete trajets
        $isSuperadmin = $_SESSION['usr-con']['is-superadmin'] ?? false;
        if (!$isSuperadmin) {
            $this->jsonError('Seul le superadmin peut supprimer un trajet', 403);
        }

        $id = (int)$this->post('id-destination-forDel');
        $force = (bool)$this->post('force-destination-del');

        // Check if trajet is used in any voyage
        $usageCount = $this->repo->countVoyageUsage($id);
        if ($usageCount > 0) {
            if (!$force) {
                $this->sendJson([
                    'success' => false,
                    'error' => 'Ce trajet est utilisé dans ' . $usageCount . ' voyage(s). Utilisez la suppression forcée pour supprimer également les voyages liés.',
                    'canForceDelete' => true,
                    'usageCount' => $usageCount,
                ]);
            }
            // Force delete: cascade
            try {
                $this->repo->transactional(fn() =>
                    $this->repo->forceDeleteById($id)
                );
                $this->json();
            } catch (\mysqli_sql_exception $e) {
                $this->jsonError('Erreur lors de la suppression forcée');
            }
        }

        // Normal delete (no voyage references)
        $ok = $this->repo->deleteById($id);
        $ok ? $this->json() : $this->jsonError('Erreur lors de la suppression');
    }

    public function create(): never {
        $this->requireVoyageSubRight('savetrajet', 'save');
        $nom = trim($this->post('nom-destination'));
        $distance = (int)$this->post('distance-destination');
        if ($nom === '') $this->jsonError('Le libellé est obligatoire');
        if ($distance < 1) $this->jsonError('La distance doit être d\'au moins 1 km');
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
