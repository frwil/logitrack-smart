<?php
/**
 * Vehicule controller — CRUD + lookup.
 */
class VehiculeController extends BaseController
{
    private VehiculeRepository $vehiculeRepo;

    public function __construct(VehiculeRepository $vehiculeRepo)
    {
        $this->vehiculeRepo = $vehiculeRepo;
    }

    /** Fetch single vehicle data by SHA1 hash. */
    public function fetchByHash(): never
    {
        $hash = $this->post('im-vh-upd');
        if (!$hash) {
            $this->jsonError('Hash manquant');
        }

        $row = $this->vehiculeRepo->findByHash($hash);
        if (!$row) {
            $this->jsonError('Véhicule introuvable', 404);
        }

        unset($row[0], $row['id_vehicule']);
        $this->json(['data' => $row]);
    }

    /** Delete vehicle by immatriculation. */
    public function delete(): never
    {
        $immat = $this->post('vh-del-id');
        if (!$immat) {
            $this->jsonError('Immatriculation manquante');
        }

        try {
            $this->vehiculeRepo->transactional(function () use ($immat) {
                $this->vehiculeRepo->deleteByImmat($immat);
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    /** Update vehicle. */
    public function update(): never
    {
        $immat = $this->post('immat-vh-upd');
        if (!$immat) {
            $this->jsonError('Immatriculation manquante');
        }

        try {
            $this->vehiculeRepo->transactional(function () use ($immat) {
                $this->vehiculeRepo->updateByImmat(
                    $immat,
                    (int)$this->post('puissance-vh-upd'),
                    $this->post('marque-vh-upd'),
                    $this->post('modele-vh-upd'),
                    $this->post('chassis-vh-upd') ?: null,
                    $this->post('dutil-vh-upd') ?: null,
                    $this->post('dexpir-vh-upd') ?: null,
                    (int)$this->post('capacite-vh-upd'),
                    (int)$this->post('nbplace-vh-upd'),
                    $this->post('tcarb-vh-upd')
                );
                $this->vehiculeRepo->upsertPermis($immat, $this->post('qualif-permis-upd'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la mise à jour');
        }
    }

    /** Create new vehicle. */
    public function create(): never
    {
        $immat = $this->post('immat-vh');
        if (!$immat) {
            $this->jsonError('Immatriculation manquante');
        }

        try {
            $this->vehiculeRepo->transactional(function () use ($immat) {
                $this->vehiculeRepo->insert(
                    (int)$this->post('puissance-vh'),
                    $this->post('chassis-vh') ?: null,
                    $this->post('dutil-vh') ?: null,
                    $this->post('dexpir-vh') ?: null,
                    (int)$this->post('nbplace-vh'),
                    $this->post('tcarb-vh'),
                    (int)$this->post('marque-vh'),
                    (int)$this->post('modele-vh'),
                    $immat,
                    (int)$this->post('capacite-vh')
                );
                $this->vehiculeRepo->insertPermis($immat, $this->post('qualif-permis'));
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == '1062') {
                $this->jsonError('Ce véhicule existe déjà', 409);
            }
            $this->jsonError('Erreur lors de l\'enregistrement');
        }
    }
}
