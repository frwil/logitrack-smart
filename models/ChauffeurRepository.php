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

    public function findByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT * FROM chauffeur WHERE SHA1(CONCAT(id_chauffeur, nom_chauffeur)) = ?",
            [$hash]
        );
    }

    public function insert(string $nom): int|string
    {
        return $this->insert(
            "INSERT INTO chauffeur (nom_chauffeur) VALUES (?)",
            [$nom]
        );
    }

    public function updateByHash(string $hash, string $nom): bool
    {
        return $this->exec(
            "UPDATE chauffeur SET nom_chauffeur = ? WHERE SHA1(CONCAT(id_chauffeur, nom_chauffeur)) = ?",
            [$nom, $hash]
        );
    }

    public function deleteByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM chauffeur WHERE SHA1(CONCAT(id_chauffeur, nom_chauffeur)) = ?",
            [$hash]
        );
    }
}
