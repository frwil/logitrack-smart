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

    /** All destinations EXCEPT those whose SHA1 hashes are in the given list. */
    public function findAllExcept(array $hashes): array
    {
        if (empty($hashes)) {
            return $this->findAll();
        }
        [$placeholders, $params] = db_in($hashes);
        return $this->select(
            "SELECT * FROM destination_voyage WHERE SHA1(CONCAT(id_destination, lib_destination)) NOT IN ($placeholders) ORDER BY lib_destination",
            $params
        );
    }

    public function findByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT * FROM destination_voyage WHERE SHA1(CONCAT(id_destination, lib_destination)) = ?",
            [$hash]
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
        return $this->insert(
            "INSERT INTO destination_voyage (lib_destination, distance_destination) VALUES (?, ?)",
            [$lib, $distance]
        );
    }

    public function updateByHash(string $hash, string $lib, int $distance): bool
    {
        return $this->exec(
            "UPDATE destination_voyage SET lib_destination = ?, distance_destination = ?
             WHERE SHA1(CONCAT(id_destination, lib_destination)) = ?",
            [$lib, $distance, $hash]
        );
    }

    public function deleteByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM destination_voyage WHERE SHA1(CONCAT(id_destination, lib_destination)) = ?",
            [$hash]
        );
    }
}
