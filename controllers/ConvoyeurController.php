<?php
class ConvoyeurController extends BaseController
{
    private ConvoyeurRepository $repo;

    public function __construct(ConvoyeurRepository $repo)
    {
        $this->repo = $repo;
    }

    public function fetchByHash(): never
    {
        $row = $this->repo->findByHash($this->post('id-convoyeur-forModal'));
        if (!$row) {
            $this->jsonError('Convoyeur introuvable', 404);
        }
        $this->json(['data' => $row]);
    }

    public function update(): never
    {
        try {
            $this->repo->transactional(function () {
                $this->repo->updateByHash(
                    $this->post('id-convoyeur'),
                    trim(strtoupper($this->post('nom-upd-convoyeur')))
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
                $this->repo->deleteByHash($this->post('id-convoyeur-forDel'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    public function create(): never
    {
        $nom = trim(strtoupper($this->post('nom-convoyeur')));
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
            $html .= "<option value='{$r['id_convoyeur']}'>" . h($r['nom_convoyeur']) . "</option>";
        }
        $this->json(['html' => $html]);
    }
}
