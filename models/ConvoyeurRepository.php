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

    public function findByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT * FROM convoyeur WHERE SHA1(CONCAT(id_convoyeur, nom_convoyeur)) = ?",
            [$hash]
        );
    }

    public function insert(string $nom): int|string
    {
        return $this->insert(
            "INSERT INTO convoyeur (nom_convoyeur) VALUES (?)",
            [$nom]
        );
    }

    public function updateByHash(string $hash, string $nom): bool
    {
        return $this->exec(
            "UPDATE convoyeur SET nom_convoyeur = ? WHERE SHA1(CONCAT(id_convoyeur, nom_convoyeur)) = ?",
            [$nom, $hash]
        );
    }

    public function deleteByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM convoyeur WHERE SHA1(CONCAT(id_convoyeur, nom_convoyeur)) = ?",
            [$hash]
        );
    }
}
