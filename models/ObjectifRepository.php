<?php
/**
 * Objectif periode region repository.
 */
class ObjectifRepository extends BaseRepository
{
    public function findByDateRange(string $dateFrom, string $dateTo, int $regionId): array
    {
        return $this->select(
            "SELECT * FROM objectif_periode_region
             WHERE date_objectif_periode BETWEEN ? AND ? AND id_region = ?",
            [$dateFrom, $dateTo, $regionId]
        );
    }

    public function findByDateAndRegion(string $date, int $regionId): array
    {
        return $this->select(
            "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ? AND id_region = ?",
            [$date, $regionId]
        );
    }

    public function countByDate(?int $regionId, string $date): int
    {
        $sql = "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ?";
        $params = [$date];
        if ($regionId !== null) {
            $sql .= " AND id_region = ?";
            $params[] = $regionId;
        }
        return count($this->select($sql, $params));
    }

    public function findByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT * FROM objectif_periode_region WHERE SHA1(CONCAT(id_objectif_periode, date_objectif_periode)) = ?",
            [$hash]
        );
    }

    public function updateByHash(string $hash, string $date, int $objectif): bool
    {
        return $this->exec(
            "UPDATE objectif_periode_region SET date_objectif_periode = ?, objectif = ?
             WHERE SHA1(CONCAT(id_objectif_periode, date_objectif_periode)) = ?",
            [$date, $objectif, $hash]
        );
    }

    public function deleteByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM objectif_periode_region WHERE SHA1(CONCAT(id_objectif_periode, date_objectif_periode)) = ?",
            [$hash]
        );
    }
}
