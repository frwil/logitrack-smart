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

    public function findById(int $id): ?array
    {
        return $this->selectOne("SELECT * FROM entite WHERE id_entite = ?", [$id]);
    }

    public function findByUser(int $userId): array
    {
        return $this->select(
            "SELECT e.* FROM entite e
             INNER JOIN users_entite ue ON ue.id_entite = e.id_entite
             WHERE ue.id_user = ? AND ue.is_active = 1",
            [$userId]
        );
    }
}
