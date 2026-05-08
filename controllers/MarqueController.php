<?php
class MarqueController extends BaseController
{
    private MarqueRepository $repo;

    public function __construct(MarqueRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never
    {
        $id = (int)$this->post('id-marque-forModal');
        $row = $this->repo->findById($id);
        if (!$row) {
            $this->jsonError('Marque introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function update(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->updateById(
                    (int)$this->post('id-marque'),
                    trim(strtoupper($this->post('nom-upd-marque')))
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
                $this->repo->deleteById((int)$this->post('id-marque-forDel'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function create(): never {
        $nom = trim(strtoupper($this->post('nom-marque')));
        if ($nom === '') $this->jsonError('Le champ est obligatoire');
        try {
            $this->repo->insert($nom);
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('1062');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    public function refresh(): never {
        $rows = $this->repo->findAll();
        $html = '';
        foreach ($rows as $r) {
            $html .= "<option value='{$r['id_marque']}'>" . h($r['nom_marque']) . "</option>";
        }
        $this->json(['html' => $html]);
    }
}
