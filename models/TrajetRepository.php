<?php
/**
 * Trajet (destination_voyage) repository.
 */
class TrajetRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM destination_voyage WHERE 1", []);
    }

    /** All destinations EXCEPT those whose IDs are in the given list. */
    public function findAllExcept(array $ids): array
    {
        if (empty($ids)) {
            return $this->findAll();
        }
        [$placeholders, $params] = db_in($ids);
        return $this->select(
            "SELECT * FROM destination_voyage WHERE id_destination NOT IN ($placeholders) ORDER BY lib_destination",
            $params
        );
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM destination_voyage WHERE id_destination = ?",
            [$id]
        );
    }

    public function findDistanceById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT distance_destination FROM destination_voyage WHERE id_destination = ?",
            [$id]
        );
    }

    public function insert(string $lib, int $distance): int|string
    {
        return $this->insertGetId(
            "INSERT INTO destination_voyage (lib_destination, distance_destination) VALUES (?, ?)",
            [$lib, $distance]
        );
    }

    public function updateById(int $id, string $lib, int $distance): bool
    {
        return $this->exec(
            "UPDATE destination_voyage SET lib_destination = ?, distance_destination = ?
             WHERE id_destination = ?",
            [$lib, $distance, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM destination_voyage WHERE id_destination = ?",
            [$id]
        );
    }

    /** Count how many voyage_vehicule rows reference this destination. */
    public function countVoyageUsage(int $id): int
    {
        $row = $this->selectOne(
            "SELECT COUNT(*) AS cnt FROM voyage_vehicule WHERE id_destination = ?",
            [$id]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Force-delete a destination that is used in voyages.
     * Cascade: deletes voyage_vehicule rows → orphaned voyages → the destination itself.
     * Must be called inside a transaction.
     */
    public function forceDeleteById(int $id): bool
    {
        // 1. Remove all voyage-destination links for this destination
        $this->exec(
            "DELETE FROM voyage_vehicule WHERE id_destination = ?",
            [$id]
        );
        // 2. Remove orphaned voyages (no remaining voyage_vehicule links)
        $this->exec(
            "DELETE FROM voyage WHERE id_voyage NOT IN (SELECT DISTINCT id_voyage FROM voyage_vehicule)"
        );
        // 3. Delete the destination itself
        return $this->exec(
            "DELETE FROM destination_voyage WHERE id_destination = ?",
            [$id]
        );
    }
}
