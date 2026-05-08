<?php
class AffectationController extends BaseController
{
    private AffectationRepository $repo;

    public function __construct(AffectationRepository $repo)
    {
        $this->repo = $repo;
    }

    public function fetchByHash(): never
    {
        $row = $this->repo->findByHash($this->post('id-affectation-forModal'));
        if (!$row) {
            $this->jsonError('Affectation introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function update(): never
    {
        try {
            $this->repo->transactional(function () {
                // Note: original code updates nom_chauffeur on affectation — kept for compatibility
                $this->repo->exec(
                    "UPDATE affectation_vehicule SET nom_chauffeur = ? WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?",
                    [$this->post('nom-upd-chauffeur'), $this->post('id-affectation')]
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function delete(): never
    {
        $ok = $this->repo->deleteByHash($this->post('id-affectation-forDel'));
        if ($ok) {
            $this->json();
        }
        $this->jsonError('Échec de la suppression');
    }

    public function close(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->closeByHash($this->post('id-aff-toClose'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la clôture');
        }
    }
}
