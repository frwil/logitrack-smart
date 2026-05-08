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

    /** Single vehicle by SHA1(id_vehicule + immatriculation). */
    public function findByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT *,
             (SELECT SHA1(CONCAT(id_marque, nom_marque)) FROM marque_vehicule mv WHERE mv.id_marque = vehicule.id_marque) AS id_m,
             (SELECT SHA1(CONCAT(id_modele_vehicule, nom_modele_vehicule)) FROM modele_vehicule mdv WHERE mdv.id_modele_vehicule = vehicule.id_marque) AS id_md
             FROM vehicule
             WHERE SHA1(CONCAT(id_vehicule, immatriculation_vehicule)) = ?",
            [$hash]
        );
    }

    public function deleteByImmat(string $immat): bool
    {
        return $this->exec("DELETE FROM vehicule WHERE immatriculation_vehicule = ?", [$immat]);
    }

    public function updateByImmat(
        string $immat,
        int $puissance,
        string $marqueHash,
        string $modeleHash,
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
             id_marque = (SELECT id_marque FROM marque_vehicule WHERE SHA1(CONCAT(id_marque, nom_marque)) = ?),
             id_modele_vehicule = (SELECT id_modele_vehicule FROM modele_vehicule WHERE SHA1(CONCAT(id_modele_vehicule, nom_modele_vehicule)) = ?),
             chassis_vehicule = ?,
             premiere_utilisation = ?,
             expiration_carte_grise = ?,
             capacite_consommation_vehicule = ?,
             nb_place = ?,
             type_carburant = ?
             WHERE immatriculation_vehicule = ?",
            [$puissance, $marqueHash, $modeleHash, $chassis, $premiereUtilisation, $expirationCarteGrise, $capacite, $nbPlace, $typeCarburant, $immat]
        );
    }

    public function upsertPermis(string $immat, string $permisHash): bool
    {
        return $this->exec(
            "REPLACE INTO qualification_permis_vehicule (id_vehicule, id_type_permis)
             VALUES (
               (SELECT id_vehicule FROM vehicule WHERE immatriculation_vehicule = ?),
               (SELECT id_type_permis FROM type_permis_vehicule WHERE SHA1(CONCAT(id_type_permis, lib_type_permis)) = ?)
             )",
            [$immat, $permisHash]
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
        return $this->insert(
            "INSERT INTO vehicule (puissance_vehicule, chassis_vehicule, premiere_utilisation, expiration_carte_grise, nb_place, type_carburant, id_marque, id_modele_vehicule, id_entite, immatriculation_vehicule, capacite_consommation_vehicule)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)",
            [$puissance, $chassis, $dutil, $dexpir, $nbPlace, $tcarb, $idMarque, $idModele, $immat, $capacite]
        );
    }

    public function insertPermis(string $immat, string $permisHash): bool
    {
        return $this->exec(
            "INSERT INTO qualification_permis_vehicule (id_vehicule, id_type_permis)
             VALUES (
               (SELECT id_vehicule FROM vehicule WHERE immatriculation_vehicule = ?),
               (SELECT id_type_permis FROM type_permis_vehicule WHERE SHA1(CONCAT(id_type_permis, lib_type_permis)) = ?)
             )",
            [$immat, $permisHash]
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
