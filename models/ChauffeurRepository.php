<?php
/**
 * Chauffeur repository.
 */
class ChauffeurRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM chauffeur WHERE 1", []);
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM chauffeur WHERE id_chauffeur = ?",
            [$id]
        );
    }

    public function insert(string $nom): int|string
    {
        return $this->insertGetId(
            "INSERT INTO chauffeur (nom_chauffeur) VALUES (?)",
            [$nom]
        );
    }

    public function updateById(int $id, string $nom): bool
    {
        return $this->exec(
            "UPDATE chauffeur SET nom_chauffeur = ? WHERE id_chauffeur = ?",
            [$nom, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM chauffeur WHERE id_chauffeur = ?",
            [$id]
        );
    }
}
