<?php
/**
 * Marque vehicule repository.
 */
class MarqueRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM marque_vehicule WHERE 1", []);
    }

    public function findByName(string $name): ?array
    {
        return $this->selectOne(
            "SELECT * FROM marque_vehicule WHERE nom_marque = ?",
            [$name]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM marque_vehicule WHERE id_marque = ?",
            [$id]
        );
    }

    public function updateById(int $id, string $nom): bool
    {
        return $this->exec(
            "UPDATE marque_vehicule SET nom_marque = ? WHERE id_marque = ?",
            [$nom, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM marque_vehicule WHERE id_marque = ?",
            [$id]
        );
    }

    public function insert(string $nom): int|string
    {
        return $this->insertGetId(
            "INSERT INTO marque_vehicule (nom_marque) VALUES (?)",
            [$nom]
        );
    }

    public function insertIgnore(string $nom): bool
    {
        return $this->insertIgnore(
            "INSERT IGNORE INTO marque_vehicule (nom_marque) VALUES (?)",
            [$nom]
        );
    }
}
