<?php
/**
 * Affectation vehicule repository.
 */
class AffectationRepository extends BaseRepository
{
    /** All affectations with all joins, filtered by region IDs. */
    public function findAllByRegions(array $regionIds): array
    {
        [$placeholders, $params] = db_in($regionIds);
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON region.id_region = affectation_vehicule.id_region
             LEFT JOIN entite ON entite.id_entite = affectation_vehicule.id_entite
             LEFT JOIN type_utilisation_vehicule ON type_utilisation_vehicule.id_type_utilisation = affectation_vehicule.id_type_utilisation
             LEFT JOIN mode_utilisation_vehicule ON mode_utilisation_vehicule.id_mode_utilisation = affectation_vehicule.id_mode_utilisation
             LEFT JOIN marque_vehicule ON marque_vehicule.id_marque = vehicule.id_marque
             LEFT JOIN modele_vehicule ON modele_vehicule.id_modele_vehicule = vehicule.id_modele_vehicule
             WHERE affectation_vehicule.id_region IN ($placeholders)
             ORDER BY date_affectation DESC",
            $params
        );
    }

    /** All affectations filtered by region + entity context. */
    public function findAllByContext(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON region.id_region = affectation_vehicule.id_region
             LEFT JOIN entite ON entite.id_entite = affectation_vehicule.id_entite
             LEFT JOIN type_utilisation_vehicule ON type_utilisation_vehicule.id_type_utilisation = affectation_vehicule.id_type_utilisation
             LEFT JOIN mode_utilisation_vehicule ON mode_utilisation_vehicule.id_mode_utilisation = affectation_vehicule.id_mode_utilisation
             LEFT JOIN marque_vehicule ON marque_vehicule.id_marque = vehicule.id_marque
             LEFT JOIN modele_vehicule ON modele_vehicule.id_modele_vehicule = vehicule.id_modele_vehicule
             WHERE $where AND is_ferme = 0
             ORDER BY date_affectation DESC",
            $params
        );
    }

    /** Single affectation by ID. */
    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM affectation_vehicule WHERE id_affectation = ?",
            [$id]
        );
    }

    /** All active affectations (non-ferme, all regions). */
    public function findAllActive(): array
    {
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON affectation_vehicule.id_region = region.id_region
             WHERE is_ferme = 0",
            []
        );
    }

    /** Active affectations for a region (non-ferme). */
    public function findActiveByRegion(int $regionId): array
    {
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON affectation_vehicule.id_region = region.id_region
             WHERE is_ferme = 0 AND affectation_vehicule.id_region = ?",
            [$regionId]
        );
    }

    /** Active affectations for region + entity context (non-ferme). */
    public function findActiveByContext(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON affectation_vehicule.id_region = region.id_region
             WHERE is_ferme = 0 AND $where",
            $params
        );
    }

    public function findActiveByVehiculeAndRegion(int $vehiculeId, int $regionId): array
    {
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON affectation_vehicule.id_region = region.id_region
             WHERE is_ferme = 0 AND vehicule.id_vehicule = ? AND affectation_vehicule.id_region = ?",
            [$vehiculeId, $regionId]
        );
    }

    public function findActiveByVehiculeAndContext(int $vehiculeId, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params[] = $vehiculeId;
        return $this->select(
            "SELECT * FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN region ON affectation_vehicule.id_region = region.id_region
             WHERE is_ferme = 0 AND $where AND vehicule.id_vehicule = ?",
            $params
        );
    }

    public function closeById(int $id): bool
    {
        return $this->exec(
            "UPDATE affectation_vehicule SET is_ferme = 1, date_fin_affectation = CURRENT_DATE
             WHERE id_affectation = ?",
            [$id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM affectation_vehicule WHERE id_affectation = ?",
            [$id]
        );
    }

    /** INSERT IGNORE — used by import.php. Looks up all FKs by name. */
    public function insertIgnore(
        string $immat,
        string $chauffeurNom,
        string $typeUtilisationLib,
        string $modeUtilisationNom,
        string $entiteNom,
        ?int $idRegion = null
    ): bool {
        return $this->execIgnore(
            "INSERT IGNORE INTO affectation_vehicule (id_vehicule, id_chauffeur, id_type_utilisation, id_mode_utilisation, id_entite, objet_affectation, date_debut_affectation, date_fin_affectation, id_region, date_affectation, is_ferme)
             VALUES (
               (SELECT id_vehicule FROM vehicule WHERE immatriculation_vehicule = ?),
               (SELECT id_chauffeur FROM chauffeur WHERE nom_chauffeur = ? LIMIT 1),
               (SELECT id_type_utilisation FROM type_utilisation_vehicule WHERE lib_type_utilisation = ?),
               (SELECT id_mode_utilisation FROM mode_utilisation_vehicule WHERE nom_mode_utilisation = ?),
               (SELECT id_entite FROM entite WHERE nom_entite = ?),
               NULL, CURRENT_TIMESTAMP, NULL, ?, CURRENT_TIMESTAMP, '0')",
            [$immat, $chauffeurNom, $typeUtilisationLib, $modeUtilisationNom, $entiteNom, $idRegion]
        );
    }
}
