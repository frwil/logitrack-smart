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
                    (float)$this->post('qtec-upd-voyage')
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
                    (float)$this->post('qtechargement-vg')
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
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->dailyVoyagesVsObjectives($days, $regionIds, $entiteIds);
        $this->json(['data' => $data]);
    }

    public function topDestinations(): never
    {
        $limit = (int)($this->post('limit') ?: 10);
        $regionIds = getContextRegions();
        $entiteIds = getContextEntities();
        $data = $this->voyageRepo->topDestinations($limit, $regionIds, $entiteIds);
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
}
