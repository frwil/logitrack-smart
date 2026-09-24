<?php
/**
 * Prestataire transport repository — external carriers for voyages.
 */
class PrestataireTransportRepository extends BaseRepository
{
    /** All active external carriers. */
    public function findAll(): array
    {
        return $this->select(
            "SELECT * FROM prestataire_transport WHERE is_deleted = 0 ORDER BY immatriculation",
            []
        );
    }

    /** Single carrier by id. */
    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM prestataire_transport WHERE id_prestataire_transport = ?",
            [$id]
        );
    }

    /** Insert a carrier; returns new id. Throws mysqli_sql_exception 1062 on duplicate immatriculation. */
    public function insert(string $immatriculation, string $nomSociete, string $nomChauffeur, ?string $nomCopilote, ?string $adresseSociete, ?string $telephoneSociete, float $capacite, string $unite): int|string
    {
        return $this->insertGetId(
            "INSERT INTO prestataire_transport (immatriculation, nom_societe, nom_chauffeur, nom_copilote, adresse_societe, telephone_societe, capacite_transport, unite_mesure) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$immatriculation, $nomSociete, $nomChauffeur, $nomCopilote, $adresseSociete, $telephoneSociete, $capacite, $unite]
        );
    }
}
