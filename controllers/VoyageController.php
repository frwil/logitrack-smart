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
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la modification');
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
}
