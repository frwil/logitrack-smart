<?php
/**
 * Prestataire transport controller — external carriers and their voyages.
 */
class PrestataireTransportController extends BaseController
{
    private PrestataireTransportRepository $prestataireRepo;
    private VoyagePrestataireRepository $voyagePrestataireRepo;

    public function __construct(PrestataireTransportRepository $prestataireRepo, VoyagePrestataireRepository $voyagePrestataireRepo)
    {
        $this->prestataireRepo = $prestataireRepo;
        $this->voyagePrestataireRepo = $voyagePrestataireRepo;
    }

    // ---- Droits (objet 'voyages') ----

    /** Require a generic 'voyages' right — die with 403 if missing. */
    private function requireVoyageRight(string $right): void
    {
        if (!in_array($right, getUserRightsFor('voyages'), true)) {
            $this->jsonError('Accès non autorisé', 403);
        }
    }

    // ---- Prestataire (transporteur externe) ----

    public function createPrestataire(): never
    {
        $this->requireVoyageRight('save');
        $immat = strtoupper(trim($this->post('immat-pt-transport')));
        $societe = trim($this->post('societe-pt-transport'));
        $chauffeur = trim($this->post('chauffeur-pt-transport'));
        if ($immat === '' || $societe === '' || $chauffeur === '') $this->jsonError('Société, immatriculation et chauffeur obligatoires');
        try {
            $id = $this->prestataireRepo->insert(
                $immat,
                $societe,
                $chauffeur,
                $this->post('copilote-pt-transport') ?: null,
                $this->post('adresse-pt-transport') ?: null,
                $this->post('telephone-pt-transport') ?: null,
                (float)($this->post('capacite-pt-transport') ?: 0),
                $this->post('unite-pt-transport') ?: ''
            );
            $this->json(['id' => (int)$id, 'label' => $societe . ' — ' . $immat . ' (' . $chauffeur . ')']);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('Ce prestataire existe déjà (immatriculation en doublon)');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    // ---- Voyages prestataires externes ----

    public function createVoyage(): never
    {
        $this->requireVoyageRight('save');
        $this->saveVoyage(null);
    }

    public function updateVoyage(): never
    {
        $this->requireVoyageRight('upd');
        $this->saveVoyage((int)$this->post('id-voyage-prestataire-upd'));
    }

    private function saveVoyage(?int $id): never
    {
        $upd = $id !== null;
        $date = $this->post($upd ? 'date-upd-vge' : 'date-vge');
        if ($upd) {
            // Prestataire non modifiable : clés distinctes (date-upd-vge…) pour ne pas déclencher la route de création
            $prestataireId = 0;
            $entiteId = (int)$this->post('id-entite-upd-vge');
            $regionId = (int)$this->post('id-region-upd-vge');
            $typeChargementId = (int)$this->post('typechargement-upd-vge');
            $convoyeur = $this->post('convoyeur-upd-vge') ?: null;
            $qte = (float)($this->post('qtechargement-upd-vge') ?: 0);
            $scelle = $this->post('numero-scelle-upd-vge') ?: null;
        } else {
            $prestataireId = (int)$this->post('id-prestataire-vge');
            $entiteId = (int)$this->post('id-entite-vge');
            $regionId = (int)$this->post('id-region-vge');
            $typeChargementId = (int)$this->post('typechargement-vge');
            $convoyeur = $this->post('convoyeur-vge') ?: null;
            $qte = (float)($this->post('qtechargement-vge') ?: 0);
            $scelle = $this->post('numero-scelle-vge') ?: null;
        }

        if (!$date) $this->jsonError('La date est obligatoire');
        if (!$upd && $prestataireId <= 0) $this->jsonError('Prestataire requis pour un voyage externe');
        if ($typeChargementId <= 0) $this->jsonError('Type de chargement obligatoire');
        // L'entité et la région doivent appartenir au contexte de session
        if (!in_array($entiteId, array_map('intval', getContextEntities()))) $this->jsonError('Entité hors contexte de session');
        if (!in_array($regionId, array_map('intval', getContextRegions()))) $this->jsonError('Région hors contexte de session');

        $trajets = json_decode($this->post('trajets-voyage', '[]'), true);
        if (!is_array($trajets)) $this->jsonError('Ajoutez au moins un trajet au voyage');
        $trajets = array_values(array_unique(array_filter(array_map('intval', $trajets))));
        if (empty($trajets)) $this->jsonError('Ajoutez au moins un trajet au voyage');

        try {
            $this->voyagePrestataireRepo->transactional(function () use ($id, $date, $prestataireId, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $scelle, $trajets) {
                if ($id === null) {
                    $id = $this->voyagePrestataireRepo->insertVoyagePrestataire($date, $prestataireId, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $scelle);
                } else {
                    $this->voyagePrestataireRepo->updateById($id, $date, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $scelle);
                    $this->voyagePrestataireRepo->deleteDestinations($id);
                }
                foreach ($trajets as $destinationId) {
                    $this->voyagePrestataireRepo->insertDestination((int)$id, $destinationId);
                }
            });
            $this->json();
        } catch (\Throwable $e) {
            error_log('PrestataireTransportController::saveVoyage error: ' . $e->getMessage());
            $this->jsonError("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    public function fetchVoyage(): never
    {
        $this->requireVoyageRight('view');
        $id = (int)$this->post('id-voyage-prestataire-forModal');
        $row = $this->voyagePrestataireRepo->findById($id);
        if (!$row) $this->jsonError('Voyage introuvable');
        $row['trajets'] = $this->voyagePrestataireRepo->findDestinations($id);
        $this->json($row);
    }

    public function deleteVoyage(): never
    {
        $this->requireVoyageRight('del');
        try {
            $ok = $this->voyagePrestataireRepo->deleteById((int)$this->post('id-voyage-prestataire-del'));
            if ($ok) {
                $this->json();
            }
            $this->jsonError('Échec de la suppression');
        } catch (\Throwable $e) {
            $this->jsonError('Échec de la suppression');
        }
    }

    // ---- Stats (AJAX) ----

    public function stats(): never
    {
        $this->requireVoyageRight('view');
        $typeId = (int)$this->post('type-chargement-stat');
        $stats = $this->voyagePrestataireRepo->statsExternes(getContextRegions(), getContextEntities(), $typeId ?: null);
        $this->json($stats);
    }
}
