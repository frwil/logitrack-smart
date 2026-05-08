<?php
/**
 * Convoyeur repository.
 */
class ConvoyeurRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM convoyeur WHERE 1", []);
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM convoyeur WHERE id_convoyeur = ?",
            [$id]
        );
    }

    public function insert(string $nom): int|string
    {
        return $this->insertGetId(
            "INSERT INTO convoyeur (nom_convoyeur) VALUES (?)",
            [$nom]
        );
    }

    public function updateById(int $id, string $nom): bool
    {
        return $this->exec(
            "UPDATE convoyeur SET nom_convoyeur = ? WHERE id_convoyeur = ?",
            [$nom, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM convoyeur WHERE id_convoyeur = ?",
            [$id]
        );
    }
}
