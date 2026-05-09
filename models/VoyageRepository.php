<?php
/**
 * Voyage repository — voyage, voyage_vehicule, destination_voyage, type_chargement_voyage.
 */
class VoyageRepository extends BaseRepository
{
    /** Destinations. */
    public function findAllDestinations(): array
    {
        return $this->select("SELECT * FROM destination_voyage WHERE 1", []);
    }

    public function findDestinationById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM destination_voyage WHERE id_destination = ?",
            [$id]
        );
    }

    /** Type chargement. */
    public function findAllTypesChargement(): array
    {
        return $this->select("SELECT * FROM type_chargement_voyage WHERE 1", []);
    }

    /** Voyages for a vehicle on a single day, with destination and chargement details. */
    public function findByVehiculeAndDate(int $vehiculeId, string $date): array
    {
        return $this->select(
            "SELECT * FROM voyage, voyage_vehicule, destination_voyage, type_chargement_voyage
             WHERE voyage.id_voyage = voyage_vehicule.id_voyage
             AND destination_voyage.id_destination = voyage_vehicule.id_destination
             AND voyage.id_type_chargement = type_chargement_voyage.id_type_chargement
             AND id_affectation = (
               SELECT id_affectation FROM affectation_vehicule
               WHERE id_vehicule = ? AND is_ferme = 0
               ORDER BY date_affectation LIMIT 1
             )
             AND date_voyage = ?",
            [$vehiculeId, $date]
        );
    }

    /** Voyage vehicles by destination with optional date range. */
    public function findVoyageVehiculesByDestination(int $destinationId, int $vehiculeId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT id_voyage_vehicule, voyage_vehicule.id_voyage, voyage.*,
                (SELECT distance_destination FROM destination_voyage WHERE id_destination = ?) AS dist_trajet
                FROM voyage_vehicule
                LEFT JOIN voyage ON voyage.id_voyage = voyage_vehicule.id_voyage
                WHERE id_destination = ?
                AND voyage.id_voyage IN (
                  SELECT id_voyage FROM voyage WHERE id_affectation IN (
                    SELECT id_affectation FROM affectation_vehicule WHERE id_vehicule = ?
                  )";
        $params = [$destinationId, $destinationId, $vehiculeId];

        if ($dateFrom !== null && $dateTo !== null) {
            $sql .= " AND date_voyage BETWEEN ? AND ?";
            $params[] = $dateFrom;
            $params[] = $dateTo;
        } else {
            $sql .= " AND date_voyage BETWEEN ? AND ?";
            $params[] = date('Y-m-01');
            $params[] = date('Y-m-t');
        }
        $sql .= ")";

        return $this->select($sql, $params);
    }

    /** Voyages by date and destination (for period view). */
    public function findVoyageVehiculesByDateDestination(int $destinationId, string $date): array
    {
        return $this->select(
            "SELECT id_voyage_vehicule, voyage_vehicule.id_voyage, voyage.*,
             (SELECT distance_destination FROM destination_voyage WHERE id_destination = ?) AS dist_trajet
             FROM voyage_vehicule
             LEFT JOIN voyage ON voyage.id_voyage = voyage_vehicule.id_voyage
             WHERE id_destination = ?
             AND voyage_vehicule.id_voyage IN (
               SELECT id_voyage FROM voyage WHERE date_voyage = ?
             )",
            [$destinationId, $destinationId, $date]
        );
    }

    /** Single voyage by ID with full details. */
    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT *
             FROM voyage, voyage_vehicule, destination_voyage, type_chargement_voyage
             WHERE voyage.id_voyage = voyage_vehicule.id_voyage
             AND destination_voyage.id_destination = voyage_vehicule.id_destination
             AND voyage.id_type_chargement = type_chargement_voyage.id_type_chargement
             AND voyage.id_voyage = ?",
            [$id]
        );
    }

    public function updateById(
        int $id,
        string $date,
        float $qteCarburant,
        string $convoyeur,
        int $typeChargementId,
        float $qteChargement
    ): bool {
        return $this->exec(
            "UPDATE voyage SET
             date_voyage = ?,
             qte_carburant = ?,
             convoyeur = ?,
             id_type_chargement = ?,
             qte_chargement = ?
             WHERE id_voyage = ?",
            [$date, $qteCarburant, $convoyeur, $typeChargementId, $qteChargement, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM voyage WHERE id_voyage = ?",
            [$id]
        );
    }

    /** Reporting: voyages for a vehicle in date range, with all joins. */
    public function findForReporting(int $vehiculeId, string $dateFrom, string $dateTo, int $regionId): array
    {
        return $this->select(
            "SELECT * FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN type_chargement_voyage ON type_chargement_voyage.id_type_chargement = voyage.id_type_chargement
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE id_vehicule = ? AND date_voyage BETWEEN ? AND ? AND is_ferme = 0 AND affectation_vehicule.id_region = ?
             ORDER BY type_chargement_voyage.id_type_chargement, date_voyage",
            [$vehiculeId, $dateFrom, $dateTo, $regionId]
        );
    }

    /** Reporting: voyages for a vehicle filtered by region + entity context. */
    public function findForReportingByContext(int $vehiculeId, string $dateFrom, string $dateTo, array $regionIds, array $entiteIds): array
    {
        [$where, $ctxParams] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge([$vehiculeId, $dateFrom, $dateTo], $ctxParams);
        return $this->select(
            "SELECT * FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN type_chargement_voyage ON type_chargement_voyage.id_type_chargement = voyage.id_type_chargement
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE id_vehicule = ? AND date_voyage BETWEEN ? AND ? AND is_ferme = 0 AND $where
             ORDER BY type_chargement_voyage.id_type_chargement, date_voyage",
            $params
        );
    }

    /** Voyages by date and region (for evaluation). */
    public function countByDateAndRegion(string $date, int $regionId): array
    {
        return $this->select(
            "SELECT *,
             (SELECT SUM(distance_destination) FROM destination_voyage WHERE id_destination IN (
               SELECT id_destination FROM voyage_vehicule WHERE id_voyage = voyage.id_voyage
             )) AS total_dest
             FROM voyage
             WHERE date_voyage = ?
             AND id_voyage IN (
               SELECT id_voyage FROM voyage_vehicule WHERE id_affectation IN (
                 SELECT id_affectation FROM affectation_vehicule WHERE id_region = ?
               )
             )",
            [$date, $regionId]
        );
    }

    /** Voyages by date and context (for evaluation). */
    public function countByDateAndContext(string $date, array $regionIds, array $entiteIds): array
    {
        [$where, $ctxParams] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge([$date], $ctxParams);
        return $this->select(
            "SELECT *,
             (SELECT SUM(distance_destination) FROM destination_voyage WHERE id_destination IN (
               SELECT id_destination FROM voyage_vehicule WHERE id_voyage = voyage.id_voyage
             )) AS total_dest
             FROM voyage
             WHERE date_voyage = ?
             AND id_voyage IN (
               SELECT id_voyage FROM voyage_vehicule WHERE id_affectation IN (
                 SELECT id_affectation FROM affectation_vehicule WHERE $where
               )
             )",
            $params
        );
    }
}
