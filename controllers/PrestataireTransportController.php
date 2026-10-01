<?php
/**
 * Prestataire transport controller — external carriers and their voyages.
 */
class PrestataireTransportController extends BaseController
{
    private PrestataireTransportRepository $prestataireRepo;
    private VoyagePrestataireRepository $voyagePrestataireRepo;
    private VoyageRepository $voyageRepo;

    public function __construct(PrestataireTransportRepository $prestataireRepo, VoyagePrestataireRepository $voyagePrestataireRepo, VoyageRepository $voyageRepo)
    {
        $this->prestataireRepo = $prestataireRepo;
        $this->voyagePrestataireRepo = $voyagePrestataireRepo;
        $this->voyageRepo = $voyageRepo;
    }

    // ---- Droits (objet 'voyages') ----

    /** Require a generic 'voyages' right — die with 403 if missing. */
    private function requireVoyageRight(string $right): void
    {
        if (!in_array($right, getUserRightsFor('voyages'), true)) {
            $this->jsonError('Accès non autorisé', 403);
        }
    }

    // Droits dédiés aux prestataires de transport (sous-droits de l'objet 'voyages',
    // visibles dans le modal Modifier l'utilisateur). hasSubRight assure la
    // rétro-compatibilité : un utilisateur n'ayant AUCUN de ces sous-droits
    // retombe sur le droit générique voyages correspondant.
    private const PT_RIGHTS = [
        'viewPrestataireTransport',
        'savePrestataireTransport',
        'updPrestataireTransport',
        'delPrestataireTransport',
    ];

    private function requirePrestataireRight(string $specific, string $fallback): void
    {
        if (!hasSubRight($specific, $fallback, getUserRightsFor('voyages'), self::PT_RIGHTS)) {
            $this->jsonError('Accès non autorisé', 403);
        }
    }

    // ---- Prestataire (transporteur externe) ----

    public function createPrestataire(): never
    {
        $this->requirePrestataireRight('savePrestataireTransport', 'save');
        // Nom de société : seul champ obligatoire — nettoyé des espaces superflus.
        $societe = trim(preg_replace('/\s+/', ' ', (string)$this->post('societe-pt-transport')));
        if ($societe === '') $this->jsonError('Le nom de la société est obligatoire');
        if ($this->prestataireRepo->findBySocieteSimilar($societe)) {
            $this->jsonError('Ce prestataire existe déjà (nom de société identique ou similaire)');
        }
        try {
            $id = $this->prestataireRepo->insert(
                $societe,
                trim((string)$this->post('adresse-pt-transport')) ?: null,
                trim((string)$this->post('telephone-pt-transport')) ?: null
            );
            $this->json(['id' => (int)$id, 'label' => $societe]);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('Ce prestataire existe déjà (nom de société en doublon)');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    /** Charge un prestataire existant dans le modal de modification. */
    public function fetchPrestataire(): never
    {
        $this->requirePrestataireRight('viewPrestataireTransport', 'view');
        $row = $this->prestataireRepo->findById((int)$this->post('id-prestataire-transport-forModal'));
        if (!$row || (int)($row['is_deleted'] ?? 0) === 1) $this->jsonError('Prestataire introuvable');
        $this->json($row);
    }

    public function updatePrestataire(): never
    {
        $this->requirePrestataireRight('updPrestataireTransport', 'upd');
        $id = (int)$this->post('id-prestataire-transport-upd');
        $row = $this->prestataireRepo->findById($id);
        if (!$row || (int)($row['is_deleted'] ?? 0) === 1) $this->jsonError('Prestataire introuvable');

        $societe = trim(preg_replace('/\s+/', ' ', (string)$this->post('societe-pt-transport-upd')));
        if ($societe === '') $this->jsonError('Le nom de la société est obligatoire');
        // Même contrôle de doublon qu'à la création, en s'excluant soi-même.
        $similar = $this->prestataireRepo->findBySocieteSimilar($societe);
        if ($similar && (int)$similar['id_prestataire_transport'] !== $id) {
            $this->jsonError('Ce prestataire existe déjà (nom de société identique ou similaire)');
        }
        try {
            $this->prestataireRepo->update(
                $id,
                $societe,
                trim((string)$this->post('adresse-pt-transport-upd')) ?: null,
                trim((string)$this->post('telephone-pt-transport-upd')) ?: null
            );
            // Libellé renvoyé pour rafraîchir le select du formulaire Nouveau voyage
            // sans recharger la page (immatriculation historique conservée à titre indicatif).
            $fresh = $this->prestataireRepo->findById($id);
            if (!$fresh) $this->jsonError('Prestataire introuvable');
            $label = $fresh['nom_societe'] . (!empty($fresh['immatriculation']) ? ' — ' . $fresh['immatriculation'] : '');
            $this->json(['id' => $id, 'label' => $label, 'immat' => $fresh['immatriculation'] ?? null]);
        } catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('Ce prestataire existe déjà (nom de société en doublon)');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    /** Suppression logique : les voyages déjà enregistrés restent rattachés au prestataire. */
    public function deletePrestataire(): never
    {
        $this->requirePrestataireRight('delPrestataireTransport', 'del');
        $ok = $this->prestataireRepo->softDelete((int)$this->post('id-prestataire-transport-del'));
        if ($ok) $this->json();
        $this->jsonError('Échec de la suppression');
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
        $chauffeur = trim((string)$this->post($upd ? 'chauffeur-upd-vge' : 'chauffeur-vge')) ?: null;
        // L'immatriculation est saisie par voyage (le véhicule du prestataire n'est pas maîtrisé)
        $immatriculation = trim((string)$this->post($upd ? 'immatriculation-upd-vge' : 'immatriculation-vge')) ?: null;
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
            $this->voyagePrestataireRepo->transactional(function () use ($id, $date, $chauffeur, $immatriculation, $prestataireId, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $scelle, $trajets) {
                if ($id === null) {
                    $id = $this->voyagePrestataireRepo->insertVoyagePrestataire($date, $prestataireId, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $scelle, $chauffeur, $immatriculation);
                } else {
                    $this->voyagePrestataireRepo->updateById($id, $date, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $scelle, $chauffeur, $immatriculation);
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

    /**
     * Qtés transportées, portée : externe | flotte | tout | comparaison
     * (le bouton radio du bloc Qtés transportées choisit la portée ;
     * comparaison renvoie flotte / externes côte à côte).
     * Période optionnelle (date-from-stat / date-to-stat, barre de filtres voyages) :
     * absente ou invalide → mois courant.
     */
    public function stats(): never
    {
        $this->requireVoyageRight('view');
        $typeId = (int)$this->post('type-chargement-stat');
        $scope = (string)$this->post('scope-stat-prestataires', 'externe');
        if (!in_array($scope, ['externe', 'flotte', 'tout', 'comparaison'], true)) $scope = 'externe';

        $dateFrom = $this->post('date-from-stat') ?: null;
        $dateTo = $this->post('date-to-stat') ?: null;
        $hasRange = $dateFrom !== null && $dateTo !== null
            && strtotime($dateFrom) !== false && strtotime($dateTo) !== false;
        if ($hasRange) {
            $dateFrom = date('Y-m-d', strtotime($dateFrom));
            $dateTo = date('Y-m-d', strtotime($dateTo));
            if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
            $stats = $this->voyagePrestataireRepo->statsExternesBetween(getContextRegions(), getContextEntities(), $typeId ?: null, $dateFrom, $dateTo);
        } else {
            $stats = $this->voyagePrestataireRepo->statsExternes(getContextRegions(), getContextEntities(), $typeId ?: null);
        }
        if ($scope !== 'externe') {
            $fleet = $hasRange
                ? $this->voyageRepo->statsQteFlotteBetween(getContextRegions(), getContextEntities(), $typeId ?: null, $dateFrom, $dateTo)
                : $this->voyageRepo->statsQteFlotte(getContextRegions(), getContextEntities(), $typeId ?: null);
            if ($scope === 'flotte') {
                $stats = $fleet;
            } elseif ($scope === 'comparaison') {
                // Flotte / externes côte à côte, même convention d'affichage que le tableau de bord
                $stats = [
                    'nb_voyages' => $fleet['nb_voyages'] . ' / ' . $stats['nb_voyages'],
                    'total_qte_fmt' => $fleet['total_qte_fmt'] . ' / ' . $stats['total_qte_fmt'],
                    'unite' => $stats['unite'],
                ];
            } else {
                // Les 2 : même table type_chargement_voyage des deux côtés, l'unité reste valable
                $stats['nb_voyages'] += $fleet['nb_voyages'];
                $stats['total_qte'] += $fleet['total_qte'];
                $stats['total_qte_fmt'] = number_format($stats['total_qte'], 0, ',', ' ');
            }
        }
        $stats['scope'] = $scope;
        $this->json($stats);
    }
}
