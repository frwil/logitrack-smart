<?php
class ObjectifController extends BaseController
{
    private ObjectifRepository $repo;

    public function __construct(ObjectifRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never {
        $row = $this->repo->findByHash($this->post('id-objectif-forModal'));
        if (!$row) $this->jsonError('Objectif introuvable', 404);
        $this->json(['data' => $row]);
    }

    public function update(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateByHash($this->post('id-objectif'), $this->post('date-upd-objectif'), (int)$this->post('objectif-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function delete(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteByHash($this->post('id-objectif-forDel'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
}
