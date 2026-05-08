<?php
/**
 * Entite repository.
 */
class EntiteRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM entite WHERE 1", []);
    }

    public function insertIgnore(string $nom): bool
    {
        return $this->execIgnore(
            "INSERT IGNORE INTO entite (nom_entite) VALUES (?)",
            [$nom]
        );
    }
}
