<?php
class ModeleController extends BaseController
{
    private ModeleRepository $repo;

    public function __construct(ModeleRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never
    {
        $id = (int)$this->post('id-modele-forModal');
        $row = $this->repo->findById($id);
        if (!$row) {
            $this->jsonError('Modele introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function update(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->updateById(
                    (int)$this->post('id-modele'),
                    trim(strtoupper($this->post('nom-upd-modele')))
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
                $this->repo->deleteById((int)$this->post('id-modele-forDel'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function create(): never {
        $nom = trim(strtoupper($this->post('nom-modele')));
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
            $html .= "<option value='{$r['id_modele_vehicule']}'>" . h($r['nom_modele_vehicule']) . "</option>";
        }
        $this->json(['html' => $html]);
    }
}
