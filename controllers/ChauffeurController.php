<?php
/**
 * Chauffeur controller — CRUD.
 */
class ChauffeurController extends BaseController
{
    private ChauffeurRepository $repo;

    public function __construct(ChauffeurRepository $repo)
    {
        $this->repo = $repo;
    }

    public function fetchByHash(): never
    {
        $row = $this->repo->findById((int)$this->post('id-chauffeur-forModal'));
        if (!$row) {
            $this->jsonError('Chauffeur introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function update(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->updateById(
                    $this->post('id-chauffeur'),
                    trim(strtoupper($this->post('nom-upd-chauffeur')))
                );
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    public function delete(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->deleteById((int)$this->post('id-chauffeur-forDel'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function create(): never
    {
        $nom = trim(strtoupper($this->post('nom-chauffeur')));
        if ($nom === '') $this->jsonError('Le champ est obligatoire');
        try {
            $this->repo->insert($nom);
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('1062');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    public function refreshOptions(): never
    {
        $rows = $this->repo->findAll();
        $html = '';
        foreach ($rows as $r) {
            $html .= "<option value='{$r['id_chauffeur']}'>" . h($r['nom_chauffeur']) . "</option>";
        }
        $this->json(['html' => $html]);
    }
}
