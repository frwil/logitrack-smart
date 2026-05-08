<?php
/**
 * Modele vehicule repository.
 */
class ModeleRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM modele_vehicule WHERE 1", []);
    }

    public function findByName(string $name): ?array
    {
        return $this->selectOne(
            "SELECT * FROM modele_vehicule WHERE nom_modele_vehicule = ?",
            [$name]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM modele_vehicule WHERE id_modele_vehicule = ?",
            [$id]
        );
    }

    public function updateById(int $id, string $nom): bool
    {
        return $this->exec(
            "UPDATE modele_vehicule SET nom_modele_vehicule = ? WHERE id_modele_vehicule = ?",
            [$nom, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM modele_vehicule WHERE id_modele_vehicule = ?",
            [$id]
        );
    }

    public function insert(string $nom): int|string
    {
        return $this->insertGetId(
            "INSERT INTO modele_vehicule (nom_modele_vehicule) VALUES (?)",
            [$nom]
        );
    }

    public function insertIgnore(string $nom): bool
    {
        return $this->execIgnore(
            "INSERT IGNORE INTO modele_vehicule (nom_modele_vehicule) VALUES (?)",
            [$nom]
        );
    }
}
