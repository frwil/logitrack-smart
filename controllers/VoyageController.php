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

        // Carburations du voyage (1 voyage = N carburations).
        $row['carburants'] = $this->voyageRepo->fuelEventsByVoyage($id);

        $this->json(['data' => $row]);
    }

    /** Update voyage. */
    public function update(): never
    {
        try {
            $this->voyageRepo->transactional(function () {
                $carburants = json_decode($this->post('carburants-upd-voyage'), true);
                if (!is_array($carburants)) $carburants = [];
                $totalCarb = 0;
                foreach ($carburants as $c) {
                    $totalCarb += max(0, (int)($c['qte'] ?? 0));
                }
                $idVoyage = (int)$this->post('id-voyage');
                $this->voyageRepo->updateById(
                    $idVoyage,
                    $this->post('date-upd-voyage'),
                    $totalCarb,
                    $this->post('cv-upd-voyage'),
                    (int)$this->post('tc-upd-voyage'),
                    (float)$this->post('qtec-upd-voyage'),
                    $this->post('numero-scelle-upd-voyage') ?: null
                );
                $this->voyageRepo->saveFuelEvents($idVoyage, $carburants);
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
        $carburants = json_decode($this->post('carburants-voyage'), true);
        if (!is_array($carburants)) $carburants = [];
        $totalCarb = 0;
        foreach ($carburants as $c) {
            $totalCarb += max(0, (int)($c['qte'] ?? 0));
        }
        try {
            $this->voyageRepo->transactional(function () use ($trajets, $carburants, $totalCarb) {
                $voyageId = $this->voyageRepo->insertVoyage(
                    $this->post('titre-vg'),
                    $this->post('date-vg'),
                    (int)$this->post('id-vehicule-vg'),
                    $totalCarb,
                    $this->post('id-convoyeur-vg'),
                    (int)$this->post('typechargement-vg'),
                    (float)$this->post('qtechargement-vg'),
                    $this->post('numero-scelle-vg') ?: null
                );
                foreach ($trajets as $destId) {
                    $this->voyageRepo->insertVoyageVehicule((int)$voyageId, (int)$destId);
                }
                $this->voyageRepo->saveFuelEvents((int)$voyageId, $carburants);
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
        $rows = $this->voyageRepo->consoPerVehicle($regionIds, $entiteIds, $dateFrom, $dateTo);
        // Carburant compté une seule fois par voyage (sans jointure trajets).
        $fuelByVid = $this->voyageRepo->fuelPerVehicle($regionIds, $entiteIds, $dateFrom, $dateTo);

        // Statistiques de groupe (modèle + carburant + puissance) — même logique que le Tab 3 du reporting.
        $consoMinKm = 50; // km minimum sur la période pour entrer dans les statistiques de groupe
        $veh = [];
        $groups = [];
        foreach ($rows as $r) {
            $immat = $r['immatriculation_vehicule'];
            $km = (float)$r['total_km'];
            $l = $fuelByVid[(int)$r['id_vehicule']] ?? 0.0;
            $gk = (int)$r['id_modele_vehicule'] . '|' . ($r['type_carburant'] ?? '') . '|' . (int)$r['puissance_vehicule'];
            $veh[$immat] = [
                'gk'        => $gk,
                'marque'    => $r['nom_marque'] ?? '',
                'modele'    => $r['nom_modele_vehicule'] ?? '',
                'carburant' => $r['type_carburant'] ?? '',
                'puissance' => (int)$r['puissance_vehicule'],
                'km'        => $km,
                'l'         => $l,
                'l100'      => $km > 0 ? ($l / $km) * 100 : null,
            ];
            // Garde qualité de données : carburant non renseigné (l <= 0) ou km insuffisants → hors statistiques de groupe.
            if ($km >= $consoMinKm && $l > 0) {
                if (!isset($groups[$gk])) $groups[$gk] = ['vals' => [], 'marques' => []];
                $groups[$gk]['vals'][$immat] = $veh[$immat]['l100'];
                $groups[$gk]['marques'][$veh[$immat]['marque']] = true;
            }
        }
        $groupRows = [];
        $zByImmat = [];
        foreach ($groups as $gk => $g) {
            $vals = array_values($g['vals']);
            $n = count($vals);
            $mean = array_sum($vals) / $n;
            $sumSq = 0;
            foreach ($vals as $v) $sumSq += ($v - $mean) ** 2;
            // Écart-type « population » : on décrit la flotte observée, pas un échantillon.
            $sd = $n > 1 ? sqrt($sumSq / $n) : 0;
            $cv = $mean > 0 ? ($sd / $mean) * 100 : 0;
            $alerts = [];
            if ($n > 1 && $sd > 0) {
                foreach ($g['vals'] as $immat => $l100) {
                    $z = ($l100 - $mean) / $sd;
                    $zByImmat[$immat] = $z;
                    if ($z >= 2) $alerts[] = ['immat' => $immat, 'z' => $z];
                }
            }
            $first = $veh[key($g['vals'])];
            $marques = array_filter(array_keys($g['marques']));
            // Modèle non renseigné en base : groupe « fourre-tout » hétérogène, on l'assume mais on le dit.
            $groupRows[] = [
                'label'  => $first['modele'] === ''
                    ? 'Modèle non renseigné'
                    : (($marques ? implode('/', $marques) . ' ' : '') . $first['modele'] . ' · ' . $first['carburant']
                        . ($first['puissance'] > 0 ? ' · ' . $first['puissance'] . ' ch' : '')),
                'n'      => $n,
                'mean'   => round($mean, 1),
                'sd'     => round($sd, 1),
                'cv'     => round($cv, 1),
                'min'    => round(min($vals), 1),
                'max'    => round(max($vals), 1),
                'alerts' => $alerts,
            ];
        }
        $data = [];
        foreach ($veh as $immat => $v) {
            if ($v['l100'] === null) continue; // km = 0 : rien à afficher
            $data[] = [
                'immatriculation_vehicule' => $immat,
                'group' => $v['modele'] === ''
                    ? 'Modèle non renseigné'
                    : (($v['marque'] !== '' ? $v['marque'] . ' ' : '') . $v['modele'] . ' · ' . $v['carburant']
                        . ($v['puissance'] > 0 ? ' · ' . $v['puissance'] . ' ch' : '')),
                'total_km'        => $v['km'],
                'total_carburant' => $v['l'],
                'z'               => $zByImmat[$immat] ?? null,
            ];
        }
        $this->json(['data' => $data, 'groups' => $groupRows, 'min_km' => $consoMinKm]);
    }

    public function vehiculesInactifs(): never
    {
        $days = (int)($this->post('days') ?: 7);
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->vehiculesInactifs($days, $regionIds, $entiteIds);
        $this->json(['data' => $data]);
    }

    /** Jauge virtuelle carburant : état actuel estimé du réservoir, sur tout l'historique. */
    public function jaugeVirtuelle(): never
    {
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->fuelGaugeByContext($regionIds, $entiteIds);
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
