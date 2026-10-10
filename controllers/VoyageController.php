<?php
/**
 * Voyage controller — CRUD + lookup.
 */
class VoyageController extends BaseController
{
    private VoyageRepository $voyageRepo;

    public function __construct(VoyageRepository $voyageRepo)
    {
        $this->voyageRepo = $voyageRepo;
    }

    /** Fetch voyage data for modal edit. */
    public function fetchByHash(): never
    {
        $id = (int)$this->post('id-voyage-forModal');
        if (!$id) {
            $this->jsonError('Paramètres manquants');
        }

        $row = $this->voyageRepo->findById($id);
        if (!$row) {
            $this->jsonError('Voyage introuvable', 404);
        }

        $this->json(['data' => $row]);
    }

    /** Update voyage. */
    public function update(): never
    {
        try {
            $this->voyageRepo->transactional(function () {
                $this->voyageRepo->updateById(
                    (int)$this->post('id-voyage'),
                    $this->post('date-upd-voyage'),
                    (float)$this->post('cb-upd-voyage'),
                    $this->post('cv-upd-voyage'),
                    (int)$this->post('tc-upd-voyage'),
                    (float)$this->post('qtec-upd-voyage'),
                    $this->post('numero-scelle-upd-voyage') ?: null
                );
            });
            $this->json();
        } catch (\Throwable $e) {
            error_log('VoyageController::update error: ' . $e->getMessage());
            $this->jsonError('Erreur lors de la modification : ' . $e->getMessage());
        }
    }

    /** Delete voyage. */
    public function delete(): never
    {
        $ok = $this->voyageRepo->deleteById((int)$this->post('id-voyage-forDel'));
        if ($ok) {
            $this->json();
        }
        $this->jsonError('Échec de la suppression');
    }

    /** Create voyage with destinations. */
    public function create(): never
    {
        $trajets = json_decode($this->post('trajets-voyage'), true);
        if (empty($trajets)) $this->jsonError('Aucun trajet ajouté');
        try {
            $this->voyageRepo->transactional(function () use ($trajets) {
                $voyageId = $this->voyageRepo->insertVoyage(
                    $this->post('titre-vg'),
                    $this->post('date-vg'),
                    (int)$this->post('id-vehicule-vg'),
                    (float)$this->post('qtecarburant-vg'),
                    $this->post('id-convoyeur-vg'),
                    (int)$this->post('typechargement-vg'),
                    (float)$this->post('qtechargement-vg'),
                    $this->post('numero-scelle-vg') ?: null
                );
                foreach ($trajets as $destId) {
                    $this->voyageRepo->insertVoyageVehicule((int)$voyageId, (int)$destId);
                }
            });
            $this->json();
        } catch (\Throwable $e) {
            error_log('VoyageController::create error: ' . $e->getMessage());
            $this->jsonError("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    // ---- Dashboard N2 ----

    public function voyagesVsObjectives(): never
    {
        $days = (int)($this->post('days') ?: 30);
        $scope = $this->post('scope') ?: 'tout';
        if (!in_array($scope, ['tout', 'flotte', 'externe', 'comparaison'], true)) $scope = 'tout';
        // Période personnalisée (barre de filtres voyages) : prioritaire sur $days.
        $dateFrom = $this->post('dateFrom') ?: null;
        $dateTo = $this->post('dateTo') ?: null;
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->dailyVoyagesVsObjectives($days, $regionIds, $entiteIds, $scope, $dateFrom, $dateTo);
        $this->json(['data' => $data]);
    }

    public function topDestinations(): never
    {
        $limit = (int)($this->post('limit') ?: 10);
        $scope = $this->post('scope') ?: 'tout';
        if (!in_array($scope, ['tout', 'flotte', 'externe', 'comparaison'], true)) $scope = 'tout';
        // Période personnalisée (barre de filtres voyages) : filtre optionnel.
        $dateFrom = $this->post('dateFrom') ?: null;
        $dateTo = $this->post('dateTo') ?: null;
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->topDestinations($limit, $regionIds, $entiteIds, $scope, $dateFrom, $dateTo);
        $this->json(['data' => $data]);
    }

    public function consoPerVehicle(): never
    {
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $dateFrom = $this->post('dateFrom') ?: date('Y-m-01');
        $dateTo = $this->post('dateTo') ?: date('Y-m-t');
        $data = $this->voyageRepo->consoPerVehicle($regionIds, $entiteIds, $dateFrom, $dateTo);
        $this->json(['data' => $data]);
    }

    public function vehiculesInactifs(): never
    {
        $days = (int)($this->post('days') ?: 7);
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->vehiculesInactifs($days, $regionIds, $entiteIds);
        $this->json(['data' => $data]);
    }

    /**
     * Camembert de l'évaluation des voyages : part de la flotte vs part de chaque
     * transporteur externe, selon une métrique (nb, km, km moyen, qtés, type de chargement).
     */
    public function camembertEvaluation(): never
    {
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        // Filtre région du camembert : jamais en dehors des régions du contexte utilisateur.
        $region = $this->post('region');
        if ($region !== null && $region !== '' && $region !== 'all') {
            $rid = (int)$region;
            if ($rid > 0 && in_array($rid, array_map('intval', $regionIds), true)) {
                $regionIds = [$rid];
            }
        }
        $metric = $this->post('metric') ?: 'nb';
        if (!in_array($metric, ['nb', 'km', 'km_moyen', 'qte', 'type'], true)) $metric = 'nb';
        $dateFrom = $this->post('dateFrom') ?: date('Y-m-01');
        $dateTo = $this->post('dateTo') ?: date('Y-m-t');

        $agg = $this->voyageRepo->camembertEvaluation($regionIds, $entiteIds, $dateFrom, $dateTo);
        $data = [];
        if ($metric === 'type') {
            foreach ($agg['types'] as $t) {
                if ($t['nb'] > 0) $data[] = ['label' => $t['lib_type_chargement'], 'value' => $t['nb']];
            }
        } else {
            $f = $agg['flotte'];
            if ($metric === 'nb') {
                $data[] = ['label' => 'Flotte', 'value' => $f['nb']];
            } elseif ($metric === 'km') {
                $data[] = ['label' => 'Flotte', 'value' => $f['km']];
            } elseif ($metric === 'km_moyen') {
                $data[] = ['label' => 'Flotte', 'value' => $f['nb'] > 0 ? $f['km'] / $f['nb'] : 0];
            } else {
                $data[] = ['label' => 'Flotte', 'value' => $f['qte']];
            }
            foreach ($agg['carriers'] as $c) {
                if ($metric === 'nb') $v = $c['nb'];
                elseif ($metric === 'km') $v = $c['km'];
                elseif ($metric === 'km_moyen') $v = $c['nb'] > 0 ? $c['km'] / $c['nb'] : 0;
                else $v = $c['qte'];
                if ($v > 0) $data[] = ['label' => $c['nom_societe'], 'value' => $v];
            }
        }
        $this->json(['data' => $data]);
    }
}
