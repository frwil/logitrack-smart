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
               WHERE id_vehicule = ? AND is_ferme = 0 AND is_deleted = 0
               ORDER BY date_affectation LIMIT 1
             )
             AND date_voyage = ?",
            [$vehiculeId, $date]
        );
    }

    /** All voyages in a date range filtered by context — single query for the matrix table. */
    public function findBatchByDateRange(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$where, $ctxParams] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge($ctxParams, [$dateFrom, $dateTo]);
        return $this->select(
            "SELECT v.id_voyage, v.date_voyage, v.id_affectation, v.qte_carburant, v.qte_chargement,
                    v.numero_scelle,
                    vv.id_destination, dv.lib_destination, dv.distance_destination,
                    tcv.lib_type_chargement,
                    affectation_vehicule.id_vehicule
             FROM voyage v
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = v.id_affectation
             LEFT JOIN voyage_vehicule vv ON vv.id_voyage = v.id_voyage
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             LEFT JOIN type_chargement_voyage tcv ON tcv.id_type_chargement = v.id_type_chargement
             WHERE affectation_vehicule.is_deleted = 0 AND affectation_vehicule.is_ferme = 0 AND $where
             AND v.date_voyage BETWEEN ? AND ?
             ORDER BY affectation_vehicule.id_vehicule, v.date_voyage, v.id_voyage",
            $params
        );
    }

    /** All voyage_vehicule rows with destination details for a date range — replaces N×M queries in matrix views. */
    public function findBatchVoyagesVehicules(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$where, $ctxParams] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge($ctxParams, [$dateFrom, $dateTo]);
        return $this->select(
            "SELECT v.id_voyage, v.date_voyage, v.qte_carburant, vv.id_destination,
                    dv.lib_destination, dv.distance_destination,
                    affectation_vehicule.id_vehicule
             FROM voyage_vehicule vv
             LEFT JOIN voyage v ON v.id_voyage = vv.id_voyage
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = v.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND v.date_voyage BETWEEN ? AND ?
             ORDER BY v.date_voyage, affectation_vehicule.id_vehicule, vv.id_destination",
            $params
        );
    }

    /** Count voyages and sum distances per date per region/entity — replaces D×R queries in evaluation view. */
    public function countBatchByDateAndRegions(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$phR, $pR] = db_in($regionIds);
        [$phE, $pE] = db_in($entiteIds);
        $params = array_merge($pR, $pE, [$dateFrom, $dateTo]);
        $rows = $this->select(
            "SELECT v.date_voyage, affectation_vehicule.id_region, affectation_vehicule.id_entite,
                    COUNT(DISTINCT v.id_voyage) AS nb_voyages,
                    COALESCE(SUM(dv.distance_destination), 0) AS total_dist
             FROM voyage v
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = v.id_affectation
             LEFT JOIN voyage_vehicule vv ON vv.id_voyage = v.id_voyage
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             WHERE affectation_vehicule.is_deleted = 0
             AND affectation_vehicule.id_region IN ($phR)
             AND affectation_vehicule.id_entite IN ($phE)
             AND v.date_voyage BETWEEN ? AND ?
             GROUP BY v.date_voyage, affectation_vehicule.id_region, affectation_vehicule.id_entite",
            $params
        );

        // Merge external-carrier voyages (linked directly to entite/region).
        $vpRepo = new VoyagePrestataireRepository($this->con);
        $map = [];
        foreach ($rows as $r) {
            $map[$r['date_voyage'] . '|' . $r['id_region'] . '|' . $r['id_entite']] = $r;
        }
        foreach ($vpRepo->countByDateAndRegion($dateFrom, $dateTo, $regionIds, $entiteIds) as $r) {
            $k = $r['date_voyage'] . '|' . $r['id_region'] . '|' . $r['id_entite'];
            if (isset($map[$k])) {
                $map[$k]['nb_voyages'] += (int)$r['nb_voyages'];
                $map[$k]['total_dist'] += (float)$r['total_dist'];
            } else {
                $map[$k] = $r;
            }
        }
        return array_values($map);
    }

    /**
     * Counts per date + region split by voyage type (flotte vs prestataires externes),
     * aggregated across entities — used by the evaluation view to color the two types.
     * Returns a map date|region => [nb_flotte, dist_flotte, nb_externe, dist_externe].
     */
    public function countBatchByDateAndRegionsByType(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$phR, $pR] = db_in($regionIds);
        [$phE, $pE] = db_in($entiteIds);
        $params = array_merge($pR, $pE, [$dateFrom, $dateTo]);
        $rows = $this->select(
            "SELECT v.date_voyage, affectation_vehicule.id_region,
                    COUNT(DISTINCT v.id_voyage) AS nb_voyages,
                    COALESCE(SUM(dv.distance_destination), 0) AS total_dist
             FROM voyage v
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = v.id_affectation
             LEFT JOIN voyage_vehicule vv ON vv.id_voyage = v.id_voyage
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             WHERE affectation_vehicule.is_deleted = 0
             AND affectation_vehicule.id_region IN ($phR)
             AND affectation_vehicule.id_entite IN ($phE)
             AND v.date_voyage BETWEEN ? AND ?
             GROUP BY v.date_voyage, affectation_vehicule.id_region",
            $params
        );

        $map = [];
        foreach ($rows as $r) {
            $map[$r['date_voyage'] . '|' . $r['id_region']] = [
                'nb_flotte' => (int)$r['nb_voyages'],
                'dist_flotte' => (float)$r['total_dist'],
                'nb_externe' => 0,
                'dist_externe' => 0.0,
            ];
        }
        $vpRepo = new VoyagePrestataireRepository($this->con);
        foreach ($vpRepo->countByDateAndRegion($dateFrom, $dateTo, $regionIds, $entiteIds) as $r) {
            $k = $r['date_voyage'] . '|' . $r['id_region'];
            if (!isset($map[$k])) {
                $map[$k] = ['nb_flotte' => 0, 'dist_flotte' => 0.0, 'nb_externe' => 0, 'dist_externe' => 0.0];
            }
            $map[$k]['nb_externe'] += (int)$r['nb_voyages'];
            $map[$k]['dist_externe'] += (float)$r['total_dist'];
        }
        return $map;
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
                    SELECT id_affectation FROM affectation_vehicule WHERE id_vehicule = ? AND is_deleted = 0
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
        float $qteChargement,
        ?string $numeroScelle = null
    ): bool {
        return $this->exec(
            "UPDATE voyage SET
             date_voyage = ?,
             qte_carburant = ?,
             convoyeur = ?,
             id_type_chargement = ?,
             qte_chargement = ?,
             numero_scelle = ?
             WHERE id_voyage = ?",
            [$date, $qteCarburant, $convoyeur, $typeChargementId, $qteChargement, $numeroScelle, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM voyage WHERE id_voyage = ?",
            [$id]
        );
    }

    public function insertVoyage(
        string $titre,
        string $date,
        int $affectationId,
        float $qteCarburant,
        string $convoyeur,
        int $typeChargementId,
        float $qteChargement,
        ?string $numeroScelle = null
    ): int|string {
        return $this->insertGetId(
            "INSERT INTO voyage (titre_voyage, date_voyage, id_affectation, qte_carburant, convoyeur, id_type_chargement, qte_chargement, numero_scelle)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$titre, $date, $affectationId, $qteCarburant, $convoyeur, $typeChargementId, $qteChargement, $numeroScelle]
        );
    }

    public function insertVoyageVehicule(int $voyageId, int $destinationId): int|string
    {
        return $this->insertGetId(
            "INSERT INTO voyage_vehicule (id_voyage, id_destination) VALUES (?, ?)",
            [$voyageId, $destinationId]
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
             WHERE id_vehicule = ? AND date_voyage BETWEEN ? AND ? AND is_ferme = 0 AND affectation_vehicule.is_deleted = 0 AND affectation_vehicule.id_region = ?
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
             WHERE id_vehicule = ? AND date_voyage BETWEEN ? AND ? AND is_ferme = 0 AND affectation_vehicule.is_deleted = 0 AND $where
             ORDER BY type_chargement_voyage.id_type_chargement, date_voyage",
            $params
        );
    }

    /** Batch reporting query — one query for all vehicles instead of N+1 per-vehicle calls. */
    public function findAllForReportingByContext(string $dateFrom, string $dateTo, array $regionIds, array $entiteIds): array
    {
        [$where, $ctxParams] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge([$dateFrom, $dateTo], $ctxParams);
        return $this->select(
            "SELECT affectation_vehicule.id_vehicule,
                    vehicule.immatriculation_vehicule,
                    voyage.date_voyage,
                    type_chargement_voyage.lib_type_chargement,
                    type_chargement_voyage.id_type_chargement,
                    voyage.qte_chargement,
                    destination_voyage.distance_destination,
                    destination_voyage.lib_destination,
                    voyage.qte_carburant
             FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN type_chargement_voyage ON type_chargement_voyage.id_type_chargement = voyage.id_type_chargement
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE date_voyage BETWEEN ? AND ?
             AND is_ferme = 0
             AND affectation_vehicule.is_deleted = 0
             AND $where
             ORDER BY affectation_vehicule.id_vehicule, type_chargement_voyage.id_type_chargement, date_voyage",
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
                 SELECT id_affectation FROM affectation_vehicule WHERE id_region = ? AND is_deleted = 0
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
                 SELECT id_affectation FROM affectation_vehicule WHERE is_deleted = 0 AND $where
               )
             )",
            $params
        );
    }

    // ---- Dashboard KPI ----

    /** Fleet voyages of the current month (context-filtered). */
    private function countFleetVoyagesThisMonth(array $regionIds, array $entiteIds): int
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return count($this->select(
            "SELECT voyage.id_voyage FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())",
            $params
        ));
    }

    /** Voyages of the current month (fleet + external), scoped: tout | flotte | externe. */
    public function countVoyagesThisMonth(array $regionIds, array $entiteIds, string $scope = 'tout'): int
    {
        $fleet = $this->countFleetVoyagesThisMonth($regionIds, $entiteIds);
        $vpRepo = new VoyagePrestataireRepository($this->con);
        $ext = $vpRepo->statsExternes($regionIds, $entiteIds, null)['nb_voyages'];
        if ($scope === 'flotte') return $fleet;
        if ($scope === 'externe') return $ext;
        return $fleet + $ext;
    }

    /** Fleet voyages in a date range (context-filtered). */
    private function countFleetVoyagesBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): int
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo]);
        return count($this->select(
            "SELECT voyage.id_voyage FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_voyage BETWEEN ? AND ?",
            $params
        ));
    }

    /** Voyages in a date range (fleet + external), scoped: tout | flotte | externe. */
    public function countVoyagesBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo, string $scope = 'tout'): int
    {
        $fleet = $this->countFleetVoyagesBetween($regionIds, $entiteIds, $dateFrom, $dateTo);
        $vpRepo = new VoyagePrestataireRepository($this->con);
        $ext = $vpRepo->statsExternesBetween($regionIds, $entiteIds, null, $dateFrom, $dateTo)['nb_voyages'];
        if ($scope === 'flotte') return $fleet;
        if ($scope === 'externe') return $ext;
        return $fleet + $ext;
    }

    /** Quantités transportées par la flotte sur une plage de dates (contexte filtré) — même forme que statsExternesBetween. */
    public function statsQteFlotteBetween(array $regionIds, array $entiteIds, ?int $typeChargementId, string $dateFrom, string $dateTo): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo]);
        $sql = "SELECT COUNT(*) AS nb_voyages, COALESCE(SUM(qte_chargement), 0) AS total_qte
                FROM voyage
                LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
                WHERE affectation_vehicule.is_deleted = 0 AND $where
                  AND date_voyage BETWEEN ? AND ?";
        if ($typeChargementId) {
            $sql .= " AND voyage.id_type_chargement = ?";
            $params[] = $typeChargementId;
        }
        $row = $this->selectOne($sql, $params);
        $unite = '';
        if ($typeChargementId) {
            $t = $this->selectOne("SELECT unite_mesure FROM type_chargement_voyage WHERE id_type_chargement = ?", [$typeChargementId]);
            $unite = $t['unite_mesure'] ?? '';
        }
        return [
            'nb_voyages' => (int)($row['nb_voyages'] ?? 0),
            'total_qte' => (float)($row['total_qte'] ?? 0),
            'total_qte_fmt' => number_format((float)($row['total_qte'] ?? 0), 0, ',', ' '),
            'unite' => $unite,
        ];
    }

    /** Objectives realisation rate over a date range, scoped: tout | flotte | externe. */
    public function tauxRealisationBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo, string $scope = 'tout'): float
    {
        $voyages = $this->countVoyagesBetween($regionIds, $entiteIds, $dateFrom, $dateTo, $scope);

        $objWhere = '';
        $objParams = [];
        if (!empty($regionIds)) {
            [$ph, $p] = db_in($regionIds);
            $objWhere .= " AND id_region IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        if (!empty($entiteIds)) {
            [$ph, $p] = db_in($entiteIds);
            $objWhere .= " AND id_entite IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        // Les dates sont les premiers placeholders du SQL (BETWEEN avant les IN).
        $objParams = array_merge([$dateFrom, $dateTo], $objParams);
        $row = $this->selectOne(
            "SELECT SUM(objectif) AS total FROM objectif_periode_region
             WHERE date_objectif_periode BETWEEN ? AND ?
             $objWhere",
            $objParams
        );
        $objectif = (float)($row['total'] ?? 0);
        if ($objectif <= 0) return 0;
        return round($voyages / $objectif * 100, 1);
    }
    public function statsQteFlotte(array $regionIds, array $entiteIds, ?int $typeChargementId = null): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $sql = "SELECT COUNT(*) AS nb_voyages, COALESCE(SUM(qte_chargement), 0) AS total_qte
                FROM voyage
                LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
                WHERE affectation_vehicule.is_deleted = 0 AND $where
                  AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())";
        if ($typeChargementId) {
            $sql .= " AND voyage.id_type_chargement = ?";
            $params[] = $typeChargementId;
        }
        $row = $this->selectOne($sql, $params);
        $unite = '';
        if ($typeChargementId) {
            $t = $this->selectOne("SELECT unite_mesure FROM type_chargement_voyage WHERE id_type_chargement = ?", [$typeChargementId]);
            $unite = $t['unite_mesure'] ?? '';
        }
        return [
            'nb_voyages' => (int)($row['nb_voyages'] ?? 0),
            'total_qte' => (float)($row['total_qte'] ?? 0),
            'total_qte_fmt' => number_format((float)($row['total_qte'] ?? 0), 0, ',', ' '),
            'unite' => $unite,
        ];
    }

    /** Objectives realisation rate of the current month, scoped: tout | flotte | externe. */
    public function tauxRealisation(array $regionIds, array $entiteIds, string $scope = 'tout'): float
    {
        $voyages = $this->countVoyagesThisMonth($regionIds, $entiteIds, $scope);

        $objWhere = '';
        $objParams = [];
        if (!empty($regionIds)) {
            [$ph, $p] = db_in($regionIds);
            $objWhere .= " AND id_region IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        if (!empty($entiteIds)) {
            [$ph, $p] = db_in($entiteIds);
            $objWhere .= " AND id_entite IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        $row = $this->selectOne(
            "SELECT SUM(objectif) AS total FROM objectif_periode_region
             WHERE MONTH(date_objectif_periode) = MONTH(CURDATE()) AND YEAR(date_objectif_periode) = YEAR(CURDATE())
             $objWhere",
            $objParams
        );
        $objectif = (float)($row['total'] ?? 0);
        if ($objectif <= 0) return 0;
        return round($voyages / $objectif * 100, 1);
    }

    /** Fleet km of the current month (context-filtered). */
    private function sumKmFleetThisMonth(array $regionIds, array $entiteIds): float
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $row = $this->selectOne(
            "SELECT SUM(distance_destination) AS total FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())",
            $params
        );
        return (float)($row['total'] ?? 0);
    }

    /** Km of the current month (fleet + external), scoped: tout | flotte | externe. */
    public function sumKmThisMonth(array $regionIds, array $entiteIds, string $scope = 'tout'): float
    {
        $fleet = $this->sumKmFleetThisMonth($regionIds, $entiteIds);
        $ext = (new VoyagePrestataireRepository($this->con))->sumKmThisMonth($regionIds, $entiteIds);
        if ($scope === 'flotte') return $fleet;
        if ($scope === 'externe') return $ext;
        return $fleet + $ext;
    }

    /** Fleet km in a date range (context-filtered). */
    private function sumKmFleetBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): float
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo]);
        $row = $this->selectOne(
            "SELECT SUM(distance_destination) AS total FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_voyage BETWEEN ? AND ?",
            $params
        );
        return (float)($row['total'] ?? 0);
    }

    /** Km in a date range (fleet + external), scoped: tout | flotte | externe. */
    public function sumKmBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo, string $scope = 'tout'): float
    {
        $fleet = $this->sumKmFleetBetween($regionIds, $entiteIds, $dateFrom, $dateTo);
        $ext = (new VoyagePrestataireRepository($this->con))->sumKmBetween($regionIds, $entiteIds, $dateFrom, $dateTo);
        if ($scope === 'flotte') return $fleet;
        if ($scope === 'externe') return $ext;
        return $fleet + $ext;
    }

    public function countActiveVehicles(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $total = count($this->select(
            "SELECT id_affectation FROM affectation_vehicule
             WHERE is_deleted = 0 AND is_ferme = 0 AND $where",
            $params
        ));
        $actifs = count($this->select(
            "SELECT DISTINCT affectation_vehicule.id_vehicule FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND is_ferme = 0 AND $where
             AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())",
            $params
        ));
        return ['actifs' => $actifs, 'total' => $total];
    }

    public function avgConsumption(array $regionIds, array $entiteIds): ?float
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $carburant = $this->selectOne(
            "SELECT SUM(qte_carburant) AS total FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())",
            $params
        );
        $km = $this->selectOne(
            "SELECT SUM(distance_destination) AS total FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())",
            $params
        );
        $carb = (float)($carburant['total'] ?? 0);
        $dist = (float)($km['total'] ?? 0);
        if ($dist <= 0) return null;
        return round($carb / $dist * 100, 2);
    }

    /** Active vehicles on a date range : vehicles with at least one voyage in the period / total open affectations. */
    public function countActiveVehiclesBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $total = count($this->select(
            "SELECT id_affectation FROM affectation_vehicule
             WHERE is_deleted = 0 AND is_ferme = 0 AND $where",
            $params
        ));
        $actifs = count($this->select(
            "SELECT DISTINCT affectation_vehicule.id_vehicule FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND is_ferme = 0 AND $where
             AND date_voyage BETWEEN ? AND ?",
            array_merge($params, [$dateFrom, $dateTo])
        ));
        return ['actifs' => $actifs, 'total' => $total];
    }

    /** Fleet average consumption (L/100km) over a date range (context-filtered). */
    public function avgConsumptionBetween(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): ?float
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $carburant = $this->selectOne(
            "SELECT SUM(qte_carburant) AS total FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_voyage BETWEEN ? AND ?",
            array_merge($params, [$dateFrom, $dateTo])
        );
        $km = $this->selectOne(
            "SELECT SUM(distance_destination) AS total FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             LEFT JOIN voyage_vehicule ON voyage_vehicule.id_voyage = voyage.id_voyage
             LEFT JOIN destination_voyage ON destination_voyage.id_destination = voyage_vehicule.id_destination
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_voyage BETWEEN ? AND ?",
            array_merge($params, [$dateFrom, $dateTo])
        );
        $carb = (float)($carburant['total'] ?? 0);
        $dist = (float)($km['total'] ?? 0);
        if ($dist <= 0) return null;
        return round($carb / $dist * 100, 2);
    }

    // ---- N2: Dashboard charts ----

    public function dailyVoyagesVsObjectives(int $days, array $regionIds, array $entiteIds, string $scope = 'tout', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        if ($dateFrom !== null && $dateTo !== null && strtotime($dateFrom) !== false && strtotime($dateTo) !== false) {
            $dateFrom = date('Y-m-d', strtotime($dateFrom));
            $dateTo = date('Y-m-d', strtotime($dateTo));
            if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        } else {
            $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
            $dateTo = date('Y-m-d');
        }

        $voyages = $this->select(
            "SELECT date_voyage AS date, COUNT(*) AS nb FROM voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = voyage.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_voyage BETWEEN ? AND ?
             GROUP BY date_voyage",
            array_merge($params, [$dateFrom, $dateTo])
        );

        $vpRepo = new VoyagePrestataireRepository($this->con);
        $externes = $vpRepo->countByDate($dateFrom, $dateTo, $regionIds, $entiteIds);

        $objWhere = '';
        $objParams = [];
        if (!empty($regionIds)) {
            [$ph, $p] = db_in($regionIds);
            $objWhere .= " AND id_region IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        if (!empty($entiteIds)) {
            [$ph, $p] = db_in($entiteIds);
            $objWhere .= " AND id_entite IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        $objectifs = $this->select(
            "SELECT date_objectif_periode AS date, SUM(objectif) AS total FROM objectif_periode_region
             WHERE date_objectif_periode BETWEEN ? AND ? $objWhere
             GROUP BY date_objectif_periode",
            array_merge([$dateFrom, $dateTo], $objParams)
        );

        $byDate = [];
        foreach ($voyages as $r) {
            $byDate[$r['date']] = ['date' => $r['date'], 'voyages_flotte' => (int)$r['nb']];
        }
        foreach ($externes as $r) {
            $d = $r['date'];
            if (!isset($byDate[$d])) $byDate[$d] = ['date' => $d, 'voyages_flotte' => 0];
            $byDate[$d]['voyages_externe'] = (int)$r['nb'];
        }
        foreach ($objectifs as $r) {
            $d = $r['date'];
            if (!isset($byDate[$d])) $byDate[$d] = ['date' => $d, 'voyages_flotte' => 0, 'voyages_externe' => 0];
            $byDate[$d]['objectif'] = (int)$r['total'];
        }

        $result = [];
        // Garde-fou : borne la boucle à 400 jours pour éviter une plage pathologique.
        $d = $dateFrom;
        for ($i = 0; $i < 400 && $d <= $dateTo; $i++) {
            $row = $byDate[$d] ?? ['date' => $d, 'voyages_flotte' => 0, 'voyages_externe' => 0, 'objectif' => 0];
            $f = (int)($row['voyages_flotte'] ?? 0);
            $e = (int)($row['voyages_externe'] ?? 0);
            if ($scope === 'comparaison') {
                $row['voyages_flotte'] = $f;
                $row['voyages_externe'] = $e;
                $row['voyages'] = $f + $e;
            } else {
                $row['voyages'] = $scope === 'flotte' ? $f : ($scope === 'externe' ? $e : $f + $e);
                unset($row['voyages_flotte'], $row['voyages_externe']);
            }
            $result[] = $row;
            $d = date('Y-m-d', strtotime($d . ' +1 day'));
        }
        return $result;
    }

    /** Fleet-only top destinations (context-filtered), optionally restricted to a date range. */
    private function topDestinationsFlotte(int $limit, array $regionIds, array $entiteIds, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        if ($dateFrom !== null && $dateTo !== null) {
            $where .= ' AND v.date_voyage BETWEEN ? AND ?';
            $params[] = $dateFrom;
            $params[] = $dateTo;
        }
        $params[] = $limit;
        return $this->select(
            "SELECT dv.lib_destination, COUNT(*) AS nb_voyages, SUM(dv.distance_destination) AS total_km
             FROM voyage_vehicule vv
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             LEFT JOIN voyage v ON v.id_voyage = vv.id_voyage
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = v.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             GROUP BY vv.id_destination, dv.lib_destination
             ORDER BY nb_voyages DESC
             LIMIT ?",
            $params
        );
    }

    /**
     * Top destinations, scoped: tout | flotte | externe | comparaison.
     * In comparaison mode each row carries nb_voyages_flotte/nb_voyages_externe instead of the merged totals.
     * Optional date range filters both sources.
     */
    public function topDestinations(int $limit, array $regionIds, array $entiteIds, string $scope = 'tout', ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $vpRepo = new VoyagePrestataireRepository($this->con);
        if ($scope === 'flotte') return $this->topDestinationsFlotte($limit, $regionIds, $entiteIds, $dateFrom, $dateTo);
        if ($scope === 'externe') return $vpRepo->topDestinationsExt($limit, $regionIds, $entiteIds, $dateFrom, $dateTo);

        // tout or comparaison: merge both sources on the destination label.
        $flotte = $this->topDestinationsFlotte($limit * 2, $regionIds, $entiteIds, $dateFrom, $dateTo);
        $externes = $vpRepo->topDestinationsExt($limit * 2, $regionIds, $entiteIds, $dateFrom, $dateTo);
        $map = [];
        foreach ($flotte as $r) {
            $map[$r['lib_destination']] = [
                'lib_destination' => $r['lib_destination'],
                'f_nb' => (int)$r['nb_voyages'], 'f_km' => (float)$r['total_km'],
                'e_nb' => 0, 'e_km' => 0.0,
            ];
        }
        foreach ($externes as $r) {
            $k = $r['lib_destination'];
            if (!isset($map[$k])) $map[$k] = ['lib_destination' => $k, 'f_nb' => 0, 'f_km' => 0.0, 'e_nb' => 0, 'e_km' => 0.0];
            $map[$k]['e_nb'] = (int)$r['nb_voyages'];
            $map[$k]['e_km'] = (float)$r['total_km'];
        }
        usort($map, function ($a, $b) {
            return ($b['f_nb'] + $b['e_nb']) <=> ($a['f_nb'] + $a['e_nb']);
        });
        $result = [];
        foreach ($map as $m) {
            if (count($result) >= $limit) break;
            // usort() a réindexé les clés : le libellé est conservé dans la valeur, pas dans la clé.
            if ($scope === 'comparaison') {
                $result[] = [
                    'lib_destination' => $m['lib_destination'],
                    'nb_voyages_flotte' => $m['f_nb'], 'total_km_flotte' => $m['f_km'],
                    'nb_voyages_externe' => $m['e_nb'], 'total_km_externe' => $m['e_km'],
                ];
            } else {
                $result[] = [
                    'lib_destination' => $m['lib_destination'],
                    'nb_voyages' => $m['f_nb'] + $m['e_nb'],
                    'total_km' => $m['f_km'] + $m['e_km'],
                ];
            }
        }
        return $result;
    }

    public function consoPerVehicle(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT veh.immatriculation_vehicule,
                    SUM(v.qte_carburant) AS total_carburant,
                    SUM(dv.distance_destination) AS total_km
             FROM voyage v
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = v.id_affectation
             LEFT JOIN vehicule veh ON veh.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN voyage_vehicule vv ON vv.id_voyage = v.id_voyage
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND v.date_voyage BETWEEN ? AND ?
             GROUP BY veh.id_vehicule, veh.immatriculation_vehicule
             ORDER BY total_km DESC",
            array_merge($params, [$dateFrom, $dateTo])
        );
    }

    public function vehiculesInactifs(int $days, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT veh.immatriculation_vehicule,
                    COALESCE(ch.nom_chauffeur, '—') AS nom_chauffeur,
                    MAX(v.date_voyage) AS derniere_date_voyage
             FROM affectation_vehicule
             LEFT JOIN vehicule veh ON veh.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ch ON ch.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN voyage v ON v.id_affectation = affectation_vehicule.id_affectation
             WHERE affectation_vehicule.is_deleted = 0 AND affectation_vehicule.is_ferme = 0 AND $where
             GROUP BY veh.id_vehicule, veh.immatriculation_vehicule, ch.nom_chauffeur
             HAVING MAX(v.date_voyage) IS NULL OR MAX(v.date_voyage) < DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY derniere_date_voyage ASC",
            array_merge($params, [$days])
        );
    }

    // ---- N3: Proactive ----

    public function anomaliesObjectifs(int $days, array $regionIds, array $entiteIds): array
    {
        $daily = $this->dailyVoyagesVsObjectives($days, $regionIds, $entiteIds);
        $anomalies = [];
        foreach ($daily as $d) {
            if ($d['objectif'] > 0 && $d['voyages'] < $d['objectif'] * 0.5) {
                $d['taux'] = $d['objectif'] > 0 ? round($d['voyages'] / $d['objectif'] * 100, 1) : 0;
                $anomalies[] = $d;
            }
        }
        return $anomalies;
    }

    public function vehicleActivityScores(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $rows = $this->select(
            "SELECT veh.id_vehicule, veh.immatriculation_vehicule,
                    COALESCE(ch.nom_chauffeur, '—') AS nom_chauffeur,
                    COUNT(DISTINCT v.date_voyage) AS jours_avec_voyage,
                    COALESCE(SUM(dv.distance_destination), 0) AS total_km,
                    COALESCE(SUM(v.qte_carburant), 0) AS total_carburant
             FROM affectation_vehicule
             LEFT JOIN vehicule veh ON veh.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ch ON ch.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN voyage v ON v.id_affectation = affectation_vehicule.id_affectation AND v.date_voyage BETWEEN ? AND ?
             LEFT JOIN voyage_vehicule vv ON vv.id_voyage = v.id_voyage
             LEFT JOIN destination_voyage dv ON dv.id_destination = vv.id_destination
             WHERE affectation_vehicule.is_deleted = 0 AND affectation_vehicule.is_ferme = 0 AND $where
             GROUP BY veh.id_vehicule, veh.immatriculation_vehicule, ch.nom_chauffeur",
            // Les dates du BETWEEN sont dans la clause ON du JOIN (avant le WHERE dans le SQL) : elles se lient en premier.
            array_merge([$dateFrom, $dateTo], $params)
        );

        $totalKm = 0; $totalCarb = 0;
        foreach ($rows as $r) {
            $totalKm += (float)$r['total_km'];
            $totalCarb += (float)$r['total_carburant'];
        }
        $fleetConso = $totalKm > 0 ? $totalCarb / $totalKm * 100 : 0;

        $start = new \DateTime($dateFrom);
        $end = new \DateTime($dateTo);
        $workingDays = 0;
        $d = clone $start;
        while ($d <= $end) {
            if ((int)$d->format('N') < 6) $workingDays++;
            $d->modify('+1 day');
        }
        if ($workingDays < 1) $workingDays = 1;

        $maxKm = 0;
        foreach ($rows as $r) {
            if ((float)$r['total_km'] > $maxKm) $maxKm = (float)$r['total_km'];
        }
        if ($maxKm < 1) $maxKm = 1;

        $result = [];
        foreach ($rows as $r) {
            $jours = (int)$r['jours_avec_voyage'];
            $km = (float)$r['total_km'];
            $carb = (float)$r['total_carburant'];
            $vehConso = $km > 0 ? $carb / $km * 100 : 0;

            $regularite = round(min($jours / $workingDays, 1) * 40);
            $contribution = round(min($km / $maxKm, 1) * 30);
            $scoreConso = $vehConso > 0 && $fleetConso > 0 ? round(min($fleetConso / $vehConso, 1.5) * 20) : 20;

            $score = $regularite + $contribution + $scoreConso;
            if ($score > 100) $score = 100;

            $result[] = [
                'immatriculation_vehicule' => $r['immatriculation_vehicule'],
                'nom_chauffeur' => $r['nom_chauffeur'],
                'jours_avec_voyage' => $jours,
                'total_km' => $km,
                'conso_100km' => $vehConso > 0 ? round($vehConso, 1) : null,
                'regularite' => $regularite,
                'contribution' => $contribution,
                'score_conso' => $scoreConso,
                'score' => $score,
            ];
        }

        usort($result, fn($a, $b) => $a['score'] <=> $b['score']);
        return $result;
    }

    public function projectionFinMois(array $regionIds, array $entiteIds): array
    {
        $voyages = $this->countVoyagesThisMonth($regionIds, $entiteIds);

        $objWhere = '';
        $objParams = [];
        if (!empty($regionIds)) {
            [$ph, $p] = db_in($regionIds);
            $objWhere .= " AND id_region IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        if (!empty($entiteIds)) {
            [$ph, $p] = db_in($entiteIds);
            $objWhere .= " AND id_entite IN ($ph)";
            $objParams = array_merge($objParams, $p);
        }
        $row = $this->selectOne(
            "SELECT SUM(objectif) AS total FROM objectif_periode_region
             WHERE MONTH(date_objectif_periode) = MONTH(CURDATE()) AND YEAR(date_objectif_periode) = YEAR(CURDATE())
             $objWhere",
            $objParams
        );
        $objectifTotal = (float)($row['total'] ?? 0);

        $today = (int)date('d');
        $totalDays = (int)date('t');
        $joursEcoules = min($today, $totalDays);
        $rythme = $joursEcoules > 0 ? $voyages / $joursEcoules : 0;
        $joursRestants = $totalDays - $joursEcoules;
        $projection = round($voyages + $rythme * $joursRestants);
        $tauxProjection = $objectifTotal > 0 ? round($projection / $objectifTotal * 100, 1) : 0;

        return [
            'realise' => $voyages,
            'jours_ecoules' => $joursEcoules,
            'jours_total' => $totalDays,
            'rythme_jour' => round($rythme, 1),
            'projection' => $projection,
            'objectif_total' => (int)$objectifTotal,
            'taux_projection' => $tauxProjection,
        ];
    }
}
