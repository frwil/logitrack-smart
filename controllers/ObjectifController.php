<?php
class ObjectifController extends BaseController
{
    private ObjectifRepository $repo;

    public function __construct(ObjectifRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never {
        $row = $this->repo->findById((int)$this->post('id-objectif-forModal'));
        if (!$row) $this->jsonError('Objectif introuvable', 404);
        $this->json(['data' => $row]);
    }

    public function update(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateById((int)$this->post('id-objectif'), $this->post('date-upd-objectif'), (int)$this->post('objectif-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function delete(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteById((int)$this->post('id-objectif-forDel'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    /** Check if objectives exist for a given date (used by voyage modal). */
    public function checkDateForVoyage(): never
    {
        try {
            $date = $this->post('dateV');
            if (!$date) $this->jsonError('Date manquante');
            $regionRepo = new RegionRepository($GLOBALS['con']);
            // Régions opérationnelles du contexte (hors régions admin comme Cameroun)
            $contextRegions = array_map('intval', array_column($regionRepo->findNonAdminByIds(getContextRegions()), 'id_region'));
            $contextEntities = array_map('intval', getContextEntities());

            // Périmètre précis : région/entité du véhicule sélectionné (si fournies par le formulaire)
            $regionV = (int)$this->post('regionV');
            $entiteV = (int)$this->post('entiteV');
            if ($regionV > 0) {
                $region = $regionRepo->findById($regionV);
                if ($region && (int)$region['is_admin'] >= 1) {
                    // Région admin (CAMEROUN) : les objectifs ne s'y appliquent pas
                    $this->json(['count' => 1]);
                }
                if (in_array($regionV, $contextRegions, true)) {
                    $count = $entiteV > 0
                        ? count($this->repo->findByDateAndRegion($date, $regionV, $entiteV))
                        : count($this->repo->findByDateAndRegions($date, [$regionV], $contextEntities));
                    $this->json(['count' => $count]);
                }
                // région hors contexte : retombe sur la vérification globale ci-dessous
            }
            $count = count($this->repo->findByDateAndRegions($date, $contextRegions, $contextEntities));
            $this->json(['count' => $count]);
        } catch (\Throwable $e) {
            error_log('ObjectifController::checkDateForVoyage error: ' . $e->getMessage());
            $this->jsonError('Erreur lors de la vérification : ' . $e->getMessage());
        }
    }

    /**
     * Règle métier : un voyage ne peut être enregistré que si un objectif du jour
     * est défini pour sa région/entité. Les régions admin (CAMEROUN) en sont exemptées.
     * Appelée par les créations de voyage (flotte et prestataire externe).
     */
    public function assertObjectifJour(string $date, int $regionId, int $entityId): void
    {
        if (!$date || $regionId <= 0) return; // périmètre inconnu : on ne bloque pas
        $regionRepo = new RegionRepository($GLOBALS['con']);
        $region = $regionRepo->findById($regionId);
        if (!$region || (int)$region['is_admin'] >= 1) return; // région admin : objectifs non requis
        $count = $entityId > 0
            ? count($this->repo->findByDateAndRegion($date, $regionId, $entityId))
            : count($this->repo->findByDateAndRegions($date, [$regionId], array_map('intval', getContextEntities())));
        if ($count === 0) {
            $nomRegion = $region['nom_region'] ?? 'cette région';
            $this->jsonError("Aucun objectif de voyage n'est défini pour cette journée ($nomRegion). Définissez d'abord l'objectif du jour avant d'enregistrer ce voyage.");
        }
    }

    public function create(): never {
        $date = $this->post('date-objectif');
        $objectif = (int)$this->post('objectif');
        if (!$date || !$objectif) $this->jsonError('Tous les champs sont obligatoires');

        // Use entity from voyage form prefill if provided, otherwise session context
        $prefillEntite = $this->post('prefill-entite');
        $entities = $prefillEntite ? [(int)$prefillEntite] : getContextEntities();
        if (empty($entities)) $this->jsonError('Aucune entité sélectionnée');

        // Use posted regions if provided, otherwise use non-admin context regions
        $postedRegions = $this->post('regions');
        if ($postedRegions) {
            $regions = is_array($postedRegions) ? array_map('intval', $postedRegions) : array_map('intval', explode(',', $postedRegions));
            // Ensure only non-admin regions
            $regionRepo = new RegionRepository($GLOBALS['con']);
            $regions = array_map('intval', array_column($regionRepo->findNonAdminByIds($regions), 'id_region'));
        } else {
            $regionRepo = new RegionRepository($GLOBALS['con']);
            $regions = array_map('intval', array_column($regionRepo->findNonAdminByIds(getContextRegions()), 'id_region'));
        }
        if (empty($regions)) $this->jsonError('Aucune région sélectionnée');
        try {
            $this->repo->transactional(function () use ($date, $objectif, $regions, $entities) {
                foreach ($regions as $rid) {
                    foreach ($entities as $eid) {
                        $this->repo->insert($date, $objectif, $rid, $eid);
                    }
                }
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('Cet objectif existe déjà pour cette période, région et entité');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }
}
