<?php
class AffectationController extends BaseController
{
    private AffectationRepository $repo;

    public function __construct(AffectationRepository $repo)
    {
        $this->repo = $repo;
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
                    "UPDATE affectation_vehicule SET nom_chauffeur = ? WHERE id_affectation = ?",
                    [$this->post('nom-upd-chauffeur'), (int)$this->post('id-affectation')]
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function delete(): never
    {
        $ok = $this->repo->deleteById((int)$this->post('id-affectation-forDel'));
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
}
