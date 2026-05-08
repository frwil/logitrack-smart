<?php
/**
 * Vehicule repository — vehicule + related lookup tables.
 */
class VehiculeRepository extends BaseRepository
{
    /** All vehicles with marque/modele joined. */
    public function findAllWithDetails(): array
    {
        return $this->select(
            "SELECT * FROM vehicule
             LEFT JOIN marque_vehicule ON vehicule.id_marque = marque_vehicule.id_marque
             LEFT JOIN modele_vehicule ON vehicule.id_modele_vehicule = modele_vehicule.id_modele_vehicule
             WHERE 1",
            []
        );
    }

    /** Single vehicle by immatriculation. */
    public function findByImmat(string $immat): ?array
    {
        return $this->selectOne(
            "SELECT * FROM vehicule
             WHERE immatriculation_vehicule = ?",
            [$immat]
        );
    }

    public function deleteByImmat(string $immat): bool
    {
        return $this->exec("DELETE FROM vehicule WHERE immatriculation_vehicule = ?", [$immat]);
    }

    public function updateByImmat(
        string $immat,
        int $puissance,
        int $marqueId,
        int $modeleId,
        ?string $chassis,
        ?string $premiereUtilisation,
        ?string $expirationCarteGrise,
        int $capacite,
        int $nbPlace,
        string $typeCarburant
    ): bool {
        return $this->exec(
            "UPDATE vehicule SET
             puissance_vehicule = ?,
             id_marque = ?,
             id_modele_vehicule = ?,
             chassis_vehicule = ?,
             premiere_utilisation = ?,
             expiration_carte_grise = ?,
             capacite_consommation_vehicule = ?,
             nb_place = ?,
             type_carburant = ?
             WHERE immatriculation_vehicule = ?",
            [$puissance, $marqueId, $modeleId, $chassis, $premiereUtilisation, $expirationCarteGrise, $capacite, $nbPlace, $typeCarburant, $immat]
        );
    }

    public function upsertPermis(string $immat, int $permisId): bool
    {
        return $this->exec(
            "REPLACE INTO qualification_permis_vehicule (id_vehicule, id_type_permis)
             VALUES (
               (SELECT id_vehicule FROM vehicule WHERE immatriculation_vehicule = ?),
               ?
             )",
            [$immat, $permisId]
        );
    }

    /** INSERT IGNORE — used by import.php. Looks up marque/modele by name. */
    public function insertIgnore(
        int $puissance,
        ?string $chassis,
        ?string $dutil,
        ?string $dexpir,
        int $nbPlace,
        string $tcarb,
        string $marqueNom,
        string $modeleNom,
        string $immat,
        int $capacite
    ): bool {
        return $this->execIgnore(
            "INSERT IGNORE INTO vehicule (puissance_vehicule, chassis_vehicule, premiere_utilisation, expiration_carte_grise, nb_place, type_carburant, id_marque, id_modele_vehicule, id_entite, immatriculation_vehicule, capacite_consommation_vehicule)
             VALUES (?, ?, ?, ?, ?, ?,
               (SELECT id_marque FROM marque_vehicule WHERE nom_marque = ?),
               (SELECT id_modele_vehicule FROM modele_vehicule WHERE nom_modele_vehicule = ?),
               NULL, ?, ?)",
            [$puissance, $chassis, $dutil, $dexpir, $nbPlace, $tcarb, $marqueNom, $modeleNom, $immat, $capacite]
        );
    }

    public function insert(
        int $puissance,
        ?string $chassis,
        ?string $dutil,
        ?string $dexpir,
        int $nbPlace,
        string $tcarb,
        int $idMarque,
        int $idModele,
        string $immat,
        int $capacite
    ): int|string {
        return $this->insertGetId(
            "INSERT INTO vehicule (puissance_vehicule, chassis_vehicule, premiere_utilisation, expiration_carte_grise, nb_place, type_carburant, id_marque, id_modele_vehicule, id_entite, immatriculation_vehicule, capacite_consommation_vehicule)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)",
            [$puissance, $chassis, $dutil, $dexpir, $nbPlace, $tcarb, $idMarque, $idModele, $immat, $capacite]
        );
    }

    public function insertPermis(string $immat, int $permisId): bool
    {
        return $this->exec(
            "INSERT INTO qualification_permis_vehicule (id_vehicule, id_type_permis)
             VALUES (
               (SELECT id_vehicule FROM vehicule WHERE immatriculation_vehicule = ?),
               ?
             )",
            [$immat, $permisId]
        );
    }

    /** Active vehicles in a region (non-ferme). */
    public function findActiveByRegion(int $regionId): array
    {
        return $this->select(
            "SELECT * FROM vehicule
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_vehicule = vehicule.id_vehicule
             WHERE id_region = ? AND is_ferme = 0",
            [$regionId]
        );
    }

    /** All vehicles with chauffeur name (for voyage tables). */
    public function findAllWithChauffeur(): array
    {
        return $this->select(
            "SELECT *,
             (SELECT nom_chauffeur FROM chauffeur WHERE id_chauffeur = (
               SELECT id_chauffeur FROM affectation_vehicule WHERE id_vehicule = vehicule.id_vehicule AND is_ferme = 0 LIMIT 1
             )) AS n_chauffeur
             FROM vehicule WHERE 1",
            []
        );
    }
}
