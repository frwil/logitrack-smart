<?php
/**
 * Region repository.
 */
class RegionRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->select("SELECT * FROM region WHERE 1", []);
    }

    /** Find region by its SHA1(id_region + nom_region) token. */
    public function findByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT * FROM region WHERE SHA1(CONCAT(id_region, nom_region)) = ?",
            [$hash]
        );
    }

    /** Find region by numeric ID. */
    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM region WHERE id_region = ?",
            [$id]
        );
    }

    /** Active regions. */
    public function findActive(): array
    {
        return $this->select("SELECT * FROM region WHERE is_active = 1", []);
    }

    /** Find non-admin regions by a list of IDs. */
    public function findNonAdminByIds(array $ids): array
    {
        [$placeholders, $params] = db_in($ids);
        return $this->select(
            "SELECT * FROM region WHERE is_admin < 1 AND id_region IN ($placeholders)",
            $params
        );
    }
}
