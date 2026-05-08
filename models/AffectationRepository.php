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
}
