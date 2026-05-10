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

    public function findByDateRangeAndRegions(string $dateFrom, string $dateTo, array $regionIds): array
    {
        [$ph, $p] = db_in($regionIds);
        $params = array_merge([$dateFrom, $dateTo], $p);
        return $this->select(
            "SELECT * FROM objectif_periode_region
             WHERE date_objectif_periode BETWEEN ? AND ? AND id_region IN ($ph)",
            $params
        );
    }

    public function findByDateAndRegion(string $date, int $regionId): array
    {
        return $this->select(
            "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ? AND id_region = ?",
            [$date, $regionId]
        );
    }

    public function findByDateAndRegions(string $date, array $regionIds): array
    {
        [$ph, $p] = db_in($regionIds);
        $params = array_merge([$date], $p);
        return $this->select(
            "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ? AND id_region IN ($ph)",
            $params
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

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM objectif_periode_region WHERE id_objectif_periode = ?",
            [$id]
        );
    }

    public function updateById(int $id, string $date, int $objectif): bool
    {
        return $this->exec(
            "UPDATE objectif_periode_region SET date_objectif_periode = ?, objectif = ?
             WHERE id_objectif_periode = ?",
            [$date, $objectif, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM objectif_periode_region WHERE id_objectif_periode = ?",
            [$id]
        );
    }

    public function insert(string $date, int $objectif, int $regionId): int|string
    {
        return $this->insertGetId(
            "INSERT INTO objectif_periode_region (date_objectif_periode, objectif, id_region) VALUES (?, ?, ?)",
            [$date, $objectif, $regionId]
        );
    }
}
