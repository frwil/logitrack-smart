<?php
/**
 * Voyage prestataire repository — voyages made with external carriers.
 */
class VoyagePrestataireRepository extends BaseRepository
{
    public function insertVoyagePrestataire(string $date, int $prestataireId, int $entiteId, int $regionId, ?string $convoyeur, int $typeChargementId, float $qte, ?string $numeroScelle): int|string
    {
        return $this->insertGetId(
            "INSERT INTO voyage_prestataire (date_voyage, id_prestataire_transport, id_entite, id_region, convoyeur, id_type_chargement, qte_chargement, numero_scelle) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$date, $prestataireId, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $numeroScelle]
        );
    }

    public function updateById(int $id, string $date, int $entiteId, int $regionId, ?string $convoyeur, int $typeChargementId, float $qte, ?string $numeroScelle): bool
    {
        return $this->exec(
            "UPDATE voyage_prestataire SET date_voyage = ?, id_entite = ?, id_region = ?, convoyeur = ?, id_type_chargement = ?, qte_chargement = ?, numero_scelle = ? WHERE id_voyage_prestataire = ?",
            [$date, $entiteId, $regionId, $convoyeur, $typeChargementId, $qte, $numeroScelle, $id]
        );
    }

    public function deleteById(int $id): bool
    {
        return $this->exec("DELETE FROM voyage_prestataire WHERE id_voyage_prestataire = ?", [$id]);
    }

    /** Link a destination to an external-carrier voyage. */
    public function insertDestination(int $voyageId, int $destinationId): int|string
    {
        return $this->insertGetId(
            "INSERT INTO voyage_prestataire_destination (id_voyage_prestataire, id_destination) VALUES (?, ?)",
            [$voyageId, $destinationId]
        );
    }

    /** Remove all destinations of a voyage (used before re-inserting on update). */
    public function deleteDestinations(int $voyageId): bool
    {
        return $this->exec("DELETE FROM voyage_prestataire_destination WHERE id_voyage_prestataire = ?", [$voyageId]);
    }

    /** Destinations of a voyage, with label and distance (for the edit modal). */
    public function findDestinations(int $voyageId): array
    {
        return $this->select(
            "SELECT dv.id_destination, dv.lib_destination, dv.distance_destination
             FROM voyage_prestataire_destination vpd
             LEFT JOIN destination_voyage dv ON dv.id_destination = vpd.id_destination
             WHERE vpd.id_voyage_prestataire = ?
             ORDER BY vpd.id_voyage_prestataire_destination",
            [$voyageId]
        );
    }

    /** Daily voyage counts in a date range (context-filtered) — merged into the objectives comparison. */
    public function countByDate(string $dateFrom, string $dateTo, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = $this->contextFilter('vp', $regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo]);
        return $this->select(
            "SELECT vp.date_voyage AS date, COUNT(*) AS nb
             FROM voyage_prestataire vp
             WHERE $where AND vp.date_voyage BETWEEN ? AND ?
             GROUP BY vp.date_voyage",
            $params
        );
    }

    /** Counts per date + region + entity with total distance — merged into the objectives table. */
    public function countByDateAndRegion(string $dateFrom, string $dateTo, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = $this->contextFilter('vp', $regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo]);
        return $this->select(
            "SELECT vp.date_voyage, vp.id_region, vp.id_entite,
                    COUNT(DISTINCT vp.id_voyage_prestataire) AS nb_voyages,
                    COALESCE(SUM(dv.distance_destination), 0) AS total_dist
             FROM voyage_prestataire vp
             LEFT JOIN voyage_prestataire_destination vpd ON vpd.id_voyage_prestataire = vp.id_voyage_prestataire
             LEFT JOIN destination_voyage dv ON dv.id_destination = vpd.id_destination
             WHERE $where AND vp.date_voyage BETWEEN ? AND ?
             GROUP BY vp.date_voyage, vp.id_region, vp.id_entite",
            $params
        );
    }

    /** Single voyage with carrier/entite/region/type details (for the edit modal). */
    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT vp.*, pt.immatriculation, pt.nom_chauffeur, pt.nom_copilote,
                    pt.nom_societe, pt.adresse_societe, pt.telephone_societe,
                    e.nom_entite, r.nom_region, tcv.lib_type_chargement, tcv.unite_mesure
             FROM voyage_prestataire vp
             LEFT JOIN prestataire_transport pt ON pt.id_prestataire_transport = vp.id_prestataire_transport
             LEFT JOIN entite e ON e.id_entite = vp.id_entite
             LEFT JOIN region r ON r.id_region = vp.id_region
             LEFT JOIN type_chargement_voyage tcv ON tcv.id_type_chargement = vp.id_type_chargement
             WHERE vp.id_voyage_prestataire = ?",
            [$id]
        );
    }

    /** External-carrier voyages in a date range, filtered by the session entite/region context. */
    public function findBetween(string $dateFrom, string $dateTo, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = $this->contextFilter('vp', $regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo]);
        return $this->select(
            "SELECT vp.*, pt.immatriculation, pt.nom_chauffeur, pt.nom_copilote,
                    pt.nom_societe, pt.adresse_societe, pt.telephone_societe,
                    e.nom_entite, r.nom_region, tcv.lib_type_chargement, tcv.unite_mesure,
                    GROUP_CONCAT(CONCAT(dv.lib_destination, ' (', dv.distance_destination, 'km)')
                                 ORDER BY vpd.id_voyage_prestataire_destination SEPARATOR ' → ') AS trajets_display
             FROM voyage_prestataire vp
             LEFT JOIN prestataire_transport pt ON pt.id_prestataire_transport = vp.id_prestataire_transport
             LEFT JOIN entite e ON e.id_entite = vp.id_entite
             LEFT JOIN region r ON r.id_region = vp.id_region
             LEFT JOIN type_chargement_voyage tcv ON tcv.id_type_chargement = vp.id_type_chargement
             LEFT JOIN voyage_prestataire_destination vpd ON vpd.id_voyage_prestataire = vp.id_voyage_prestataire
             LEFT JOIN destination_voyage dv ON dv.id_destination = vpd.id_destination
             WHERE $where AND vp.date_voyage BETWEEN ? AND ?
             GROUP BY vp.id_voyage_prestataire
             ORDER BY vp.date_voyage DESC, vp.id_voyage_prestataire DESC",
            $params
        );
    }

    /** Sum of destination distances of external-carrier voyages for the current month (context-filtered). */
    public function sumKmThisMonth(array $regionIds, array $entiteIds): float
    {
        [$where, $params] = $this->contextFilter('vp', $regionIds, $entiteIds);
        $row = $this->selectOne(
            "SELECT COALESCE(SUM(dv.distance_destination), 0) AS total
             FROM voyage_prestataire vp
             LEFT JOIN voyage_prestataire_destination vpd ON vpd.id_voyage_prestataire = vp.id_voyage_prestataire
             LEFT JOIN destination_voyage dv ON dv.id_destination = vpd.id_destination
             WHERE $where
               AND MONTH(vp.date_voyage) = MONTH(CURDATE()) AND YEAR(vp.date_voyage) = YEAR(CURDATE())",
            $params
        );
        return (float)($row['total'] ?? 0);
    }

    /** Top destinations of external-carrier voyages (context-filtered). */
    public function topDestinationsExt(int $limit, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = $this->contextFilter('vp', $regionIds, $entiteIds);
        $params[] = $limit;
        return $this->select(
            "SELECT dv.lib_destination, COUNT(*) AS nb_voyages, COALESCE(SUM(dv.distance_destination), 0) AS total_km
             FROM voyage_prestataire_destination vpd
             LEFT JOIN destination_voyage dv ON dv.id_destination = vpd.id_destination
             LEFT JOIN voyage_prestataire vp ON vp.id_voyage_prestataire = vpd.id_voyage_prestataire
             WHERE $where
             GROUP BY vpd.id_destination, dv.lib_destination
             ORDER BY nb_voyages DESC
             LIMIT ?",
            $params
        );
    }

    /**
     * Stats of the current month: number of external-carrier voyages + sum of quantities,
     * optionally restricted to one loading type.
     */
    public function statsExternes(array $regionIds, array $entiteIds, ?int $typeChargementId = null): array
    {
        [$where, $params] = $this->contextFilter('voyage_prestataire', $regionIds, $entiteIds);
        $sql = "SELECT COUNT(*) AS nb_voyages, COALESCE(SUM(qte_chargement), 0) AS total_qte
                FROM voyage_prestataire
                WHERE $where
                  AND MONTH(date_voyage) = MONTH(CURDATE()) AND YEAR(date_voyage) = YEAR(CURDATE())";
        if ($typeChargementId) {
            $sql .= " AND id_type_chargement = ?";
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

    /** Context filter on the direct voyage_prestataire entite/region columns (db_context_filter is affectation-scoped). */
    public function contextFilter(string $prefix, array $regionIds, array $entiteIds): array
    {
        $parts = [];
        $params = [];
        if (!empty($regionIds)) {
            [$ph, $p] = db_in($regionIds);
            $parts[] = "$prefix.id_region IN ($ph)";
            $params = array_merge($params, $p);
        }
        if (!empty($entiteIds)) {
            [$ph, $p] = db_in($entiteIds);
            $parts[] = "$prefix.id_entite IN ($ph)";
            $params = array_merge($params, $p);
        }
        return [empty($parts) ? '1' : implode(' AND ', $parts), $params];
    }
}
