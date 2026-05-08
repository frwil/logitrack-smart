<?php
class TrajetController extends BaseController
{
    private TrajetRepository $repo;

    public function __construct(TrajetRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never {
        $row = $this->repo->findById((int)$this->post('id-destination-forModal'));
        if (!$row) $this->jsonError('Trajet introuvable', 404);
        $this->json(['data' => $row]);
    }

    public function update(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateById((int)$this->post('id-destination'), $this->post('nom-upd-destination'), (int)$this->post('distance-destination-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function delete(): never {
        $ok = $this->repo->deleteById((int)$this->post('id-destination-forDel'));
        $ok ? $this->json() : $this->jsonError('Erreur');
    }
}
