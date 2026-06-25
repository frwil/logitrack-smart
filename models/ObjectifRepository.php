<?php
/**
 * Objectif periode region repository.
 */
class ObjectifRepository extends BaseRepository
{
    public function findByDateRange(string $dateFrom, string $dateTo, int $regionId, int $entityId): array
    {
        return $this->select(
            "SELECT * FROM objectif_periode_region
             WHERE date_objectif_periode BETWEEN ? AND ? AND id_region = ? AND id_entite = ?",
            [$dateFrom, $dateTo, $regionId, $entityId]
        );
    }

    public function findByDateRangeAndRegions(string $dateFrom, string $dateTo, array $regionIds, array $entityIds): array
    {
        [$phR, $pR] = db_in($regionIds);
        [$phE, $pE] = db_in($entityIds);
        $params = array_merge([$dateFrom, $dateTo], $pR, $pE);
        return $this->select(
            "SELECT * FROM objectif_periode_region
             WHERE date_objectif_periode BETWEEN ? AND ? AND id_region IN ($phR) AND id_entite IN ($phE)",
            $params
        );
    }

    /** Same as findByDateRangeAndRegions but LEFT JOINs region and entite for display names. */
    public function findByDateRangeAndRegionsWithNames(string $dateFrom, string $dateTo, array $regionIds, array $entityIds): array
    {
        [$phR, $pR] = db_in($regionIds);
        [$phE, $pE] = db_in($entityIds);
        $params = array_merge([$dateFrom, $dateTo], $pR, $pE);
        return $this->select(
            "SELECT opr.*, r.nom_region, e.nom_entite
             FROM objectif_periode_region opr
             LEFT JOIN region r ON r.id_region = opr.id_region
             LEFT JOIN entite e ON e.id_entite = opr.id_entite
             WHERE opr.date_objectif_periode BETWEEN ? AND ?
             AND opr.id_region IN ($phR) AND opr.id_entite IN ($phE)
             ORDER BY opr.date_objectif_periode, r.nom_region, e.nom_entite",
            $params
        );
    }

    public function findByDateAndRegion(string $date, int $regionId, int $entityId): array
    {
        return $this->select(
            "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ? AND id_region = ? AND id_entite = ?",
            [$date, $regionId, $entityId]
        );
    }

    public function findByDateAndRegions(string $date, array $regionIds, array $entityIds): array
    {
        [$phR, $pR] = db_in($regionIds);
        [$phE, $pE] = db_in($entityIds);
        $params = array_merge([$date], $pR, $pE);
        return $this->select(
            "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ? AND id_region IN ($phR) AND id_entite IN ($phE)",
            $params
        );
    }

    public function countByDate(?int $regionId, string $date, ?int $entityId = null): int
    {
        $sql = "SELECT * FROM objectif_periode_region WHERE date_objectif_periode = ?";
        $params = [$date];
        if ($regionId !== null) {
            $sql .= " AND id_region = ?";
            $params[] = $regionId;
        }
        if ($entityId !== null) {
            $sql .= " AND id_entite = ?";
            $params[] = $entityId;
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

    public function insert(string $date, int $objectif, int $regionId, int $entityId): int|string
    {
        return $this->insertGetId(
            "INSERT INTO objectif_periode_region (date_objectif_periode, objectif, id_region, id_entite) VALUES (?, ?, ?, ?)",
            [$date, $objectif, $regionId, $entityId]
        );
    }
}
