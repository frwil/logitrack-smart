<?php
class ObjectifController extends BaseController
{
    private ObjectifRepository $repo;

    public function __construct(ObjectifRepository $repo) { $this->repo = $repo; }

    public function fetchByHash(): never {
        $row = $this->repo->findById((int)$this->post('id-objectif-forModal'));
        if (!$row) $this->jsonError('Objectif introuvable', 404);
        $this->json(['data' => $row]);
    }

    public function update(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateById((int)$this->post('id-objectif'), $this->post('date-upd-objectif'), (int)$this->post('objectif-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function delete(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteById((int)$this->post('id-objectif-forDel'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    /** Check if objectives exist for a given date (used by voyage modal). */
    public function checkDateForVoyage(): never
    {
        $date = $this->post('dateV');
        if (!$date) $this->jsonError('Date manquante');
        $regionIds = getContextRegions();
        try {
            $count = count($this->repo->findByDateAndRegions($date, $regionIds));
            $this->json(['count' => $count]);
        } catch (\Throwable $e) {
            error_log('ObjectifController::checkDateForVoyage error: ' . $e->getMessage());
            $this->jsonError('Erreur lors de la vérification : ' . $e->getMessage());
        }
    }

    public function create(): never {
        $date = $this->post('date-objectif');
        $objectif = (int)$this->post('objectif');
        if (!$date || !$objectif) $this->jsonError('Tous les champs sont obligatoires');
        $regions = getContextRegions();
        if (empty($regions)) $this->jsonError('Aucune région sélectionnée');
        try {
            $this->repo->transactional(function () use ($date, $objectif, $regions) {
                foreach ($regions as $rid) {
                    $this->repo->insert($date, $objectif, $rid);
                }
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('Cet objectif existe déjà pour cette période et région');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }
}
