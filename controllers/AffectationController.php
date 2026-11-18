<?php
class AffectationController extends BaseController
{
    private AffectationRepository $repo;
    private VoyageRepository $voyageRepo;

    public function __construct(AffectationRepository $repo, VoyageRepository $voyageRepo)
    {
        $this->repo = $repo;
        $this->voyageRepo = $voyageRepo;
    }

    public function fetchById(): never
    {
        $row = $this->repo->findById((int)$this->post('id-affectation-forModal'));
        if (!$row) {
            $this->jsonError('Affectation introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function update(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->exec(
                    "UPDATE affectation_vehicule SET
                     id_vehicule = ?,
                     id_chauffeur = ?,
                     id_type_utilisation = ?,
                     id_mode_utilisation = ?,
                     id_entite = ?,
                     id_region = ?,
                     objet_affectation = ?,
                     date_debut_affectation = ?,
                     date_fin_affectation = ?
                     WHERE id_affectation = ?",
                    [
                        (int)$this->post('id-vehicule-upd-aff'),
                        (int)$this->post('id-chauffeur-upd-aff'),
                        (int)$this->post('id-typeutilisation-upd-aff'),
                        (int)$this->post('id-modeutilisation-upd-aff'),
                        (int)$this->post('id-entite-upd-aff'),
                        (int)$this->post('id-region-upd-aff'),
                        $this->post('objet-upd-aff') ?: null,
                        $this->post('date-debut-upd-aff') ?: null,
                        $this->post('date-fin-upd-aff') ?: null,
                        (int)$this->post('id-affectation'),
                    ]
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function delete(): never
    {
        $id = (int)$this->post('id-affectation-forDel');
        // Garde-fou : une affectation portant des voyages ne peut pas être supprimée
        // (ses voyages disparaîtraient des tableaux de la période). Clôturer reste possible.
        $nb = $this->voyageRepo->countByAffectation($id);
        if ($nb > 0) {
            $this->jsonError(
                "Impossible de supprimer cette affectation : $nb voyage(s) y sont rattachés. "
                . "Clôturez-la plutôt pour conserver l'historique des voyages."
            );
        }
        $ok = $this->repo->deleteById($id);
        if ($ok) {
            $this->json();
        }
        $this->jsonError('Échec de la suppression');
    }

    public function close(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->closeById((int)$this->post('id-aff-toClose'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la clôture');
        }
    }

    public function create(): never
    {
        try {
            $this->repo->transactional(function () {
                $vehiculeId = (int)$this->post('id-vehicule-aff');
                // Close any previous active assignment for this vehicle
                $this->repo->closeByVehiculeId($vehiculeId);
                $this->repo->insert(
                    $vehiculeId,
                    (int)$this->post('id-chauffeur-aff'),
                    (int)$this->post('id-typeutilisation-aff'),
                    (int)$this->post('id-modeutilisation-aff'),
                    (int)$this->post('id-entite-aff'),
                    (int)$this->post('id-region-aff'),
                    $this->post('objet-aff') ?: null,
                    $this->post('date-debut-aff'),
                    $this->post('date-fin-aff') ?: null
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('1062');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }
}
