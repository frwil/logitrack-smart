<?php
/**
 * Maintenance repository — vidange, prestataire, centre_couts, bons_reparation, releve_kms.
 */
class MaintenanceRepository extends BaseRepository
{
    // ---- Relevé KMS ----

    public function findReleveKms(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT * FROM releve_kms_vehicule, affectation_vehicule, vehicule, chauffeur, region
                WHERE affectation_vehicule.id_vehicule = vehicule.id_vehicule
                AND affectation_vehicule.id_affectation = releve_kms_vehicule.id_affectation_vehicule
                AND chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
                AND affectation_vehicule.id_region = region.id_region
                AND affectation_vehicule.is_deleted = 0";
        $params = [];

        if ($dateFrom !== null) {
            $sql .= "AND semaine_annee >= WEEKOFYEAR(?) AND semaine_annee <= WEEKOFYEAR(?) AND date_releve >= ?";
            $params[] = date('Y-m-01', strtotime($dateFrom));
            $params[] = date('Y-m-t', strtotime($dateTo));
            $params[] = date('Y-m-d', strtotime($dateFrom));
        } else {
            $sql .= "AND semaine_annee >= WEEKOFYEAR(?) AND date_releve >= ?";
            $params[] = date('Y-m-01');
            $params[] = date('Y-m-01');
        }
        $sql .= " ORDER BY vehicule.id_vehicule, date_releve, semaine_annee";

        return $this->select($sql, $params);
    }

    public function findReleveKmsByContext(array $regionIds, array $entiteIds, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        [$ctxWhere, $ctxParams] = db_context_filter($regionIds, $entiteIds);
        $sql = "SELECT * FROM releve_kms_vehicule, affectation_vehicule, vehicule, chauffeur, region
                WHERE affectation_vehicule.id_vehicule = vehicule.id_vehicule
                AND affectation_vehicule.id_affectation = releve_kms_vehicule.id_affectation_vehicule
                AND chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
                AND affectation_vehicule.id_region = region.id_region
                AND affectation_vehicule.is_deleted = 0
                AND $ctxWhere";
        $params = $ctxParams;

        if ($dateFrom !== null) {
            $sql .= "AND semaine_annee >= WEEKOFYEAR(?) AND semaine_annee <= WEEKOFYEAR(?) AND date_releve >= ?";
            $params[] = date('Y-m-01', strtotime($dateFrom));
            $params[] = date('Y-m-t', strtotime($dateTo));
            $params[] = date('Y-m-d', strtotime($dateFrom));
        } else {
            $sql .= "AND semaine_annee >= WEEKOFYEAR(?) AND date_releve >= ?";
            $params[] = date('Y-m-01');
            $params[] = date('Y-m-01');
        }
        $sql .= " ORDER BY vehicule.id_vehicule, date_releve, semaine_annee";

        return $this->select($sql, $params);
    }

    public function findPeriodesReleve(string $start, string $end): array
    {
        return $this->select(
            "SELECT DISTINCT periode_releve FROM releve_kms_vehicule
             WHERE date_debut_periode_releve >= ? AND date_fin_periode_releve <= ?
             ORDER BY periode_releve",
            [$start, $end]
        );
    }

    public function findKmReleve(string $periode, int $affectationId): ?array
    {
        return $this->selectOne(
            "SELECT km_releve FROM releve_kms_vehicule
             WHERE periode_releve = ?
             AND id_affectation_vehicule = ?",
            [$periode, $affectationId]
        );
    }

    public function updateReleveKms(int $kms, string $periode, int $affectationId): bool
    {
        return $this->exec(
            "UPDATE releve_kms_vehicule SET km_releve = ?
             WHERE periode_releve = ?
             AND id_affectation_vehicule = ?",
            [$kms, $periode, $affectationId]
        );
    }

    public function findMaxKmByAffectationId(int $affectationId, string $dateFin): ?array
    {
        return $this->selectOne(
            "SELECT MAX(km_releve) AS km FROM releve_kms_vehicule
             WHERE id_affectation_vehicule = ?
             AND date_fin_periode_releve <= ?
             ORDER BY date_fin_periode_releve DESC",
            [$affectationId, $dateFin]
        );
    }

    public function findMaxKmByAffectationHash(string $affectationId, string $dateFin): ?array
    {
        return $this->findMaxKmByAffectationId((int)$affectationId, $dateFin);
    }

    public function countReleveByPeriode(string $periode, int $affectationId, string $start, string $end): int
    {
        return count($this->select(
            "SELECT * FROM releve_kms_vehicule
             WHERE periode_releve = ?
             AND id_affectation_vehicule = ?
             AND date_debut_periode_releve = ? AND date_fin_periode_releve = ?",
            [$periode, $affectationId, $start, $end]
        ));
    }

    public function countRelevesByAffectationAndDateRange(int $affectationId, string $start, string $end): int
    {
        return count($this->select(
            "SELECT * FROM releve_kms_vehicule
             WHERE id_affectation_vehicule = ?
             AND date_debut_periode_releve >= ? AND date_fin_periode_releve <= ?",
            [$affectationId, $start, $end]
        ));
    }

    // ---- Vidange ----

    public function findVidangesByRegion(int $regionId): array
    {
        return $this->select(
            "SELECT *,
             (SELECT km_releve FROM releve_kms_vehicule
              WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              AND date_fin_periode_releve = (
                SELECT MAX(date_fin_periode_releve) FROM releve_kms_vehicule
                WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              )) AS kms_actuel
             FROM vidange_vehicule, affectation_vehicule, vehicule, chauffeur, region
             WHERE affectation_vehicule.id_vehicule = vehicule.id_vehicule
             AND affectation_vehicule.id_affectation = vidange_vehicule.id_affectation_vehicule
             AND chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             AND affectation_vehicule.id_region = region.id_region
             AND date_vidange = (
               SELECT MAX(date_vidange) FROM vidange_vehicule v
               WHERE v.id_affectation_vehicule = affectation_vehicule.id_affectation
             )
             AND affectation_vehicule.is_deleted = 0 AND region.id_region = ?
             ORDER BY vehicule.id_vehicule",
            [$regionId]
        );
    }

    public function findVidangesByContext(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT *,
             (SELECT km_releve FROM releve_kms_vehicule
              WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              AND date_fin_periode_releve = (
                SELECT MAX(date_fin_periode_releve) FROM releve_kms_vehicule
                WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              )) AS kms_actuel
             FROM vidange_vehicule, affectation_vehicule, vehicule, chauffeur, region
             WHERE affectation_vehicule.id_vehicule = vehicule.id_vehicule
             AND affectation_vehicule.id_affectation = vidange_vehicule.id_affectation_vehicule
             AND chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             AND affectation_vehicule.id_region = region.id_region
             AND affectation_vehicule.is_deleted = 0 AND $where
             AND date_vidange = (
               SELECT MAX(date_vidange) FROM vidange_vehicule v
               WHERE v.id_affectation_vehicule = affectation_vehicule.id_affectation
             )
             ORDER BY vehicule.id_vehicule",
            $params
        );
    }

    public function findVidangeByCode(string $code): ?array
    {
        return $this->selectOne(
            "SELECT * FROM vidange_vehicule WHERE code_vidange = ?",
            [$code]
        );
    }

    public function updateVidangeByCode(
        string $code,
        int $affectationId,
        string $date,
        int $kmAvant,
        int $kmProchaine,
        int $prestataireId,
        ?string $commentaire
    ): bool {
        return $this->exec(
            "UPDATE vidange_vehicule SET
             id_affectation_vehicule = ?,
             date_vidange = ?,
             km_vidange = ?,
             km_prochaine_vidange = ?,
             id_prestataire = ?,
             commentaire_vidange = ?
             WHERE code_vidange = ?",
            [$affectationId, $date, $kmAvant, $kmProchaine, $prestataireId, $commentaire, $code]
        );
    }

    public function findHistoriqueVidange(string $codeVidange, int $regionId): array
    {
        return $this->select(
            "SELECT *,
             (SELECT km_releve FROM releve_kms_vehicule
              WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              AND date_fin_periode_releve = (
                SELECT MAX(date_fin_periode_releve) FROM releve_kms_vehicule
                WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              )) AS kms_actuel
             FROM vidange_vehicule, affectation_vehicule, vehicule, chauffeur, region, prestataire_intervention
             WHERE prestataire_intervention.id_prestataire = vidange_vehicule.id_prestataire
             AND affectation_vehicule.id_vehicule = vehicule.id_vehicule
             AND affectation_vehicule.id_affectation = vidange_vehicule.id_affectation_vehicule
             AND chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             AND affectation_vehicule.id_region = region.id_region
             AND id_affectation_vehicule = (
               SELECT id_affectation_vehicule FROM vidange_vehicule vv WHERE vv.code_vidange = ?
             )
             AND affectation_vehicule.is_deleted = 0 AND region.id_region = ?
             ORDER BY vehicule.id_vehicule",
            [$codeVidange, $regionId]
        );
    }

    public function findHistoriqueVidangeByContext(string $codeVidange, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params[] = $codeVidange;
        return $this->select(
            "SELECT *,
             (SELECT km_releve FROM releve_kms_vehicule
              WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              AND date_fin_periode_releve = (
                SELECT MAX(date_fin_periode_releve) FROM releve_kms_vehicule
                WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              )) AS kms_actuel
             FROM vidange_vehicule, affectation_vehicule, vehicule, chauffeur, region, prestataire_intervention
             WHERE prestataire_intervention.id_prestataire = vidange_vehicule.id_prestataire
             AND affectation_vehicule.id_vehicule = vehicule.id_vehicule
             AND affectation_vehicule.id_affectation = vidange_vehicule.id_affectation_vehicule
             AND chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             AND affectation_vehicule.id_region = region.id_region
             AND affectation_vehicule.is_deleted = 0 AND $where
             AND id_affectation_vehicule = (
               SELECT id_affectation_vehicule FROM vidange_vehicule vv WHERE vv.code_vidange = ?
             )
             ORDER BY vehicule.id_vehicule",
            $params
        );
    }

    public function deleteVidangeById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM vidange_vehicule WHERE id_vidange_vehicule = ?",
            [$id]
        );
    }

    // ---- Prestataire ----

    public function findAllPrestataires(): array
    {
        return $this->select("SELECT * FROM prestataire_intervention", []);
    }

    public function findPrestataireById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM prestataire_intervention
             WHERE id_prestataire = ?",
            [$id]
        );
    }

    public function updatePrestataireById(int $id, string $nom, ?string $contact, ?string $localisation): bool
    {
        return $this->exec(
            "UPDATE prestataire_intervention SET nom_prestataire = ?, contact_prestataire = ?, localisation_prestataire = ?
             WHERE id_prestataire = ?",
            [$nom, $contact, $localisation, $id]
        );
    }

    public function deletePrestataireById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM prestataire_intervention WHERE id_prestataire = ?",
            [$id]
        );
    }

    // ---- Centre de coûts ----

    public function findAllCentresCouts(): array
    {
        return $this->select("SELECT * FROM centre_couts", []);
    }

    public function findCentreCoutById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM centre_couts
             WHERE id_centre_cout = ?",
            [$id]
        );
    }

    public function insertCentreCout(string $nom): int
    {
        return (int)$this->insertGetId(
            "INSERT INTO centre_couts (lib_centre_cout) VALUES (?)",
            [$nom]
        );
    }

    public function updateCentreCout(int $id, string $nom): bool
    {
        return $this->exec(
            "UPDATE centre_couts SET lib_centre_cout = ? WHERE id_centre_cout = ?",
            [$nom, $id]
        );
    }

    public function deleteCentreCoutById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM centre_couts WHERE id_centre_cout = ?",
            [$id]
        );
    }

    // ---- Bons de réparation ----

    public function findAllBonsReparation(): array
    {
        return $this->select(
            "SELECT * FROM bons_reparation
             LEFT JOIN affectation_vehicule ON id_affectation_vehicule = id_affectation
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN prestataire_intervention ON prestataire_intervention.id_prestataire = bons_reparation.id_prestataire
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             LEFT JOIN centre_couts ON centre_couts.id_centre_cout = bons_reparation.id_centre_cout
             WHERE affectation_vehicule.is_deleted = 0",
            []
        );
    }

    public function findAllBonsReparationByContext(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT * FROM bons_reparation
             LEFT JOIN affectation_vehicule ON id_affectation_vehicule = id_affectation
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN prestataire_intervention ON prestataire_intervention.id_prestataire = bons_reparation.id_prestataire
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             LEFT JOIN centre_couts ON centre_couts.id_centre_cout = bons_reparation.id_centre_cout
             WHERE affectation_vehicule.is_deleted = 0 AND $where",
            $params
        );
    }

    public function findAllPlusOuMoinsValue(): array
    {
        return $this->select("SELECT * FROM plus_ou_moins_value", []);
    }

    // ---- Prestataire insert ----

    public function insertPrestataire(string $nom, ?string $contact, ?string $localisation): int|string
    {
        return $this->insertGetId(
            "INSERT INTO prestataire_intervention (nom_prestataire, contact_prestataire, localisation_prestataire) VALUES (?, ?, ?)",
            [$nom, $contact, $localisation]
        );
    }

    // ---- Vidange insert ----

    public function insertVidange(int $affectationId, string $date, int $kmAvant, int $kmProchaine, int $prestataireId, ?string $commentaire): int|string
    {
        $code = bin2hex(random_bytes(8));
        return $this->insertGetId(
            "INSERT INTO vidange_vehicule (id_affectation_vehicule, date_vidange, km_vidange, km_prochaine_vidange, id_prestataire, commentaire_vidange, code_vidange) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$affectationId, $date, $kmAvant, $kmProchaine, $prestataireId, $commentaire, $code]
        );
    }

    // ---- Relevé KMS insert ----

    public function insertReleveKms(int $affectationId, string $periode, int $km, string $dateDebut, string $dateFin): int|string
    {
        return $this->insertGetId(
            "INSERT INTO releve_kms_vehicule (id_affectation_vehicule, periode_releve, km_releve, date_debut_periode_releve, date_fin_periode_releve, semaine_annee, date_releve) VALUES (?, ?, ?, ?, ?, WEEKOFYEAR(?), ?)",
            [$affectationId, $periode, $km, $dateDebut, $dateFin, $dateDebut, $dateDebut]
        );
    }

    // ---- Bons de réparation ----

    public function insertBonReparation(
        string $num,
        int $affectationId,
        string $dateEntree,
        string $diagnostic,
        string $typeExecution,
        int $prestataireId,
        float $montant,
        ?int $plusMoinsId,
        ?float $plusMoinsVal,
        int $destinationId,
        int $duree,
        string $dateJustif,
        int $centreCoutId,
        string $datePrevue,
        string $dateFin,
        string $observation
    ): int|string {
        return $this->insertGetId(
            "INSERT INTO bons_reparation (num_bon_reparation, id_affectation_vehicule, date_entree, diagnostic, type_execution, id_prestataire, montant_reparation, id_plus_ou_moins_value, plus_ou_moins_value_valeur, destination_bon, duree_reparation, date_justification, id_centre_cout, date_prevue_sortie, date_fin_reparation, observations)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$num, $affectationId, $dateEntree, $diagnostic, $typeExecution, $prestataireId, $montant, $plusMoinsId, $plusMoinsVal, $destinationId, $duree, $dateJustif, $centreCoutId, $datePrevue, $dateFin, $observation]
        );
    }

    // ---- Dashboard KPI ----

    public function countVidangeAlerts(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $rows = $this->select(
            "SELECT km_prochaine_vidange,
             (SELECT km_releve FROM releve_kms_vehicule
              WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              AND date_fin_periode_releve = (
                SELECT MAX(date_fin_periode_releve) FROM releve_kms_vehicule
                WHERE id_affectation_vehicule = vidange_vehicule.id_affectation_vehicule
              )) AS kms_actuel
             FROM vidange_vehicule, affectation_vehicule, region
             WHERE affectation_vehicule.id_affectation = vidange_vehicule.id_affectation_vehicule
             AND affectation_vehicule.id_region = region.id_region
             AND affectation_vehicule.is_deleted = 0 AND is_ferme = 0 AND $where
             AND date_vidange = (
               SELECT MAX(date_vidange) FROM vidange_vehicule v
               WHERE v.id_affectation_vehicule = affectation_vehicule.id_affectation
             )",
            $params
        );
        $total = count($rows);
        $alertes = 0;
        foreach ($rows as $r) {
            $kmActuel = (int)($r['kms_actuel'] ?? 0);
            $kmProchaine = (int)($r['km_prochaine_vidange'] ?? 0);
            if ($kmProchaine > 0 && $kmActuel > $kmProchaine - 1500) {
                $alertes++;
            }
        }
        return ['total' => $total, 'alertes' => $alertes];
    }

    public function countActiveRepairs(array $regionIds, array $entiteIds): int
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return count($this->select(
            "SELECT bons_reparation.id_bon_reparation FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND (date_fin_reparation IS NULL OR date_fin_reparation = '0000-00-00')",
            $params
        ));
    }

    public function sumMonthlyCost(array $regionIds, array $entiteIds): float
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $row = $this->selectOne(
            "SELECT SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND MONTH(date_entree) = MONTH(CURDATE()) AND YEAR(date_entree) = YEAR(CURDATE())",
            $params
        );
        return (float)($row['total'] ?? 0);
    }

    public function countImmobilizedVehicles(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $immobilises = count($this->select(
            "SELECT DISTINCT affectation_vehicule.id_affectation FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             WHERE affectation_vehicule.is_deleted = 0 AND is_ferme = 0 AND $where
             AND (vehicule.statut_vehicule IN ('EN PANNE', 'EN RÉPARATION')
                  OR EXISTS (
                    SELECT 1 FROM bons_reparation br
                    WHERE br.id_affectation_vehicule = affectation_vehicule.id_affectation
                    AND (br.date_fin_reparation IS NULL OR br.date_fin_reparation = '0000-00-00')
                  ))",
            $params
        ));
        $total = count($this->select(
            "SELECT id_affectation FROM affectation_vehicule
             WHERE is_deleted = 0 AND is_ferme = 0 AND $where",
            $params
        ));
        return ['immobilises' => $immobilises, 'total' => $total];
    }

    // ---- Dashboard Analytics ----

    public function monthlyCostHistory(int $months, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params[] = $months;
        return $this->select(
            "SELECT DATE_FORMAT(date_entree, '%Y-%m') AS mois,
                    SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_entree >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY YEAR(date_entree), MONTH(date_entree)
             ORDER BY mois ASC",
            $params
        );
    }

    public function providerComparison(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT prestataire_intervention.nom_prestataire,
                    COUNT(bons_reparation.id_bon_reparation) AS nb_reparations,
                    AVG(duree_reparation) AS duree_moyenne,
                    AVG(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS cout_moyen
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN prestataire_intervention ON prestataire_intervention.id_prestataire = bons_reparation.id_prestataire
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             GROUP BY prestataire_intervention.id_prestataire, prestataire_intervention.nom_prestataire
             ORDER BY nb_reparations DESC",
            $params
        );
    }

    public function costPerKm(array $regionIds, array $entiteIds, string $dateFrom, string $dateTo): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $params = array_merge($params, [$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
        return $this->select(
            "SELECT vehicule.immatriculation_vehicule,
                    SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total_cout,
                    (SELECT MAX(km_releve) FROM releve_kms_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_releve BETWEEN ? AND ?) AS km_max,
                    (SELECT MIN(km_releve) FROM releve_kms_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_releve BETWEEN ? AND ?) AS km_min
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             AND date_entree BETWEEN ? AND ?
             GROUP BY vehicule.id_vehicule, vehicule.immatriculation_vehicule, affectation_vehicule.id_affectation
             HAVING km_max > km_min
             ORDER BY total_cout DESC",
            $params
        );
    }

    public function costByCentreCout(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT centre_couts.lib_centre_cout,
                    COUNT(bons_reparation.id_bon_reparation) AS nb_bons,
                    SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total_cout
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN centre_couts ON centre_couts.id_centre_cout = bons_reparation.id_centre_cout
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             GROUP BY centre_couts.id_centre_cout, centre_couts.lib_centre_cout
             ORDER BY total_cout DESC",
            $params
        );
    }

    public function recurrenceByVehicle(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT vehicule.immatriculation_vehicule,
                    COUNT(bons_reparation.id_bon_reparation) AS nb_pannes,
                    SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total_cout,
                    AVG(duree_reparation) AS duree_moyenne,
                    MAX(date_entree) AS derniere_panne
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
               AND date_entree >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY vehicule.id_vehicule, vehicule.immatriculation_vehicule
             HAVING nb_pannes >= 2
             ORDER BY nb_pannes DESC, total_cout DESC",
            $params
        );
    }

    public function avgDurationByDiagnostic(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT diagnostic,
                    COUNT(id_bon_reparation) AS nb_bons,
                    AVG(duree_reparation) AS duree_moyenne,
                    SUM(duree_reparation) AS duree_totale
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             WHERE affectation_vehicule.is_deleted = 0 AND $where
               AND duree_reparation > 0
             GROUP BY diagnostic
             ORDER BY duree_totale DESC
             LIMIT 10",
            $params
        );
    }

    public function costByExecutionType(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT type_execution,
                    COUNT(id_bon_reparation) AS nb_bons,
                    SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total_cout
             FROM bons_reparation
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             GROUP BY type_execution
             ORDER BY total_cout DESC",
            $params
        );
    }

    // ---- Vehicle Health & Prediction ----

    public function vehicleHealthScores(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $rows = $this->select(
            "SELECT vehicule.immatriculation_vehicule, chauffeur.nom_chauffeur,
                    vehicule.annee_vehicule,
                    (SELECT km_releve FROM releve_kms_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     ORDER BY date_fin_periode_releve DESC LIMIT 1) AS km_actuel,
                    (SELECT km_prochaine_vidange FROM vidange_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_vidange = (
                       SELECT MAX(date_vidange) FROM vidange_vehicule v
                       WHERE v.id_affectation_vehicule = affectation_vehicule.id_affectation
                     ) LIMIT 1) AS km_prochaine_vidange,
                    (SELECT COUNT(*) FROM bons_reparation
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_entree >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) AS nb_pannes_6mois,
                    (SELECT SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0))
                     FROM bons_reparation br2
                     LEFT JOIN plus_ou_moins_value pmv ON pmv.id_plus_ou_moins_value = br2.id_plus_ou_moins_value
                     WHERE br2.id_affectation_vehicule = affectation_vehicule.id_affectation) AS total_cout
             FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             WHERE affectation_vehicule.is_deleted = 0 AND is_ferme = 0 AND $where",
            $params
        );

        $totalCosts = array_column($rows, 'total_cout');
        $avgCost = count($totalCosts) > 0 ? array_sum($totalCosts) / count($totalCosts) : 0;
        $currentYear = (int)date('Y');

        foreach ($rows as &$r) {
            $score = 0;

            $kmActuel = (int)($r['km_actuel'] ?? 0);
            $kmProchaine = (int)($r['km_prochaine_vidange'] ?? 0);
            if ($kmProchaine > 0) {
                $kmRestant = $kmProchaine - $kmActuel;
                if ($kmRestant <= 0) $score += 0;
                elseif ($kmRestant >= 3000) $score += 40;
                else $score += (int)round(40 * $kmRestant / 3000);
            } else {
                $score += 20;
            }

            $nbPannes = (int)($r['nb_pannes_6mois'] ?? 0);
            $score += max(0, 30 - $nbPannes * 10);

            $totalCout = (float)($r['total_cout'] ?? 0);
            if ($avgCost > 0 && $totalCout > 0) {
                if ($totalCout <= $avgCost) $score += 20;
                elseif ($totalCout <= $avgCost * 1.5) $score += 10;
                else $score += 0;
            } else {
                $score += 15;
            }

            $annee = (int)($r['annee_vehicule'] ?? $currentYear);
            $age = $currentYear - $annee;
            if ($age <= 3) $score += 10;
            elseif ($age <= 5) $score += 7;
            elseif ($age <= 8) $score += 4;
            else $score += 1;

            $r['score'] = $score;
        }

        return $rows;
    }

    public function upcomingVidanges(int $days, array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $rows = $this->select(
            "SELECT vehicule.immatriculation_vehicule, chauffeur.nom_chauffeur,
                    (SELECT km_releve FROM releve_kms_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     ORDER BY date_fin_periode_releve DESC LIMIT 1) AS km_actuel,
                    (SELECT km_prochaine_vidange FROM vidange_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_vidange = (
                       SELECT MAX(date_vidange) FROM vidange_vehicule v
                       WHERE v.id_affectation_vehicule = affectation_vehicule.id_affectation
                     ) LIMIT 1) AS km_prochaine_vidange,
                    (SELECT km_releve FROM releve_kms_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_fin_periode_releve = (
                       SELECT MAX(date_fin_periode_releve) FROM releve_kms_vehicule
                       WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     )) AS km_max,
                    (SELECT MIN(km_releve) FROM releve_kms_vehicule
                     WHERE id_affectation_vehicule = affectation_vehicule.id_affectation
                     AND date_debut_periode_releve >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS km_30j_min
             FROM affectation_vehicule
             LEFT JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             WHERE affectation_vehicule.is_deleted = 0 AND is_ferme = 0 AND $where
             HAVING km_actuel IS NOT NULL AND km_prochaine_vidange IS NOT NULL",
            $params
        );

        $result = [];
        foreach ($rows as $r) {
            $kmActuel = (int)($r['km_actuel'] ?? 0);
            $kmProchaine = (int)($r['km_prochaine_vidange'] ?? 0);
            $kmMax = (int)($r['km_max'] ?? 0);
            $km30jMin = isset($r['km_30j_min']) ? (int)$r['km_30j_min'] : null;

            if ($kmProchaine <= 0 || $kmActuel <= 0) continue;

            $kmRestant = $kmProchaine - $kmActuel;
            if ($kmRestant <= 0) {
                $r['km_restant'] = $kmRestant;
                $r['jours_estimes'] = 0;
                $r['urgence'] = 'Dépassée';
                $result[] = $r;
                continue;
            }

            $kmParcourus30j = $km30jMin !== null ? $kmMax - $km30jMin : 0;
            $kmMoyenJour = $kmParcourus30j > 0 ? $kmParcourus30j / 30 : 0;
            if ($kmMoyenJour <= 0) continue;

            $joursEstimes = (int)round($kmRestant / $kmMoyenJour);
            if ($joursEstimes <= $days) {
                $r['km_restant'] = $kmRestant;
                $r['jours_estimes'] = $joursEstimes;
                $r['km_moyen_jour'] = round($kmMoyenJour, 1);
                $r['urgence'] = $joursEstimes <= 15 ? 'Urgent' : ($joursEstimes <= 30 ? 'Bientôt' : 'À prévoir');
                $result[] = $r;
            }
        }

        usort($result, function ($a, $b) {
            return $a['jours_estimes'] - $b['jours_estimes'];
        });

        return $result;
    }

    public function findBonReparationById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM bons_reparation WHERE id_bon_reparation = ?",
            [$id]
        );
    }

    public function deleteBonReparationById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM bons_reparation WHERE id_bon_reparation = ?",
            [$id]
        );
    }

    public function updateBonReparation(
        int $id,
        string $num,
        int $affectationId,
        string $dateEntree,
        string $diagnostic,
        string $typeExecution,
        int $prestataireId,
        float $montant,
        ?int $plusMoinsId,
        ?float $plusMoinsVal,
        int $destinationId,
        int $duree,
        string $dateJustif,
        int $centreCoutId,
        string $datePrevue,
        string $dateFin,
        string $observation
    ): bool {
        return $this->exec(
            "UPDATE bons_reparation SET
             num_bon_reparation = ?,
             id_affectation_vehicule = ?,
             date_entree = ?,
             diagnostic = ?,
             type_execution = ?,
             id_prestataire = ?,
             montant_reparation = ?,
             id_plus_ou_moins_value = ?,
             plus_ou_moins_value_valeur = ?,
             destination_bon = ?,
             duree_reparation = ?,
             date_justification = ?,
             id_centre_cout = ?,
             date_prevue_sortie = ?,
             date_fin_reparation = ?,
             observations = ?
             WHERE id_bon_reparation = ?",
            [$num, $affectationId, $dateEntree, $diagnostic, $typeExecution, $prestataireId, $montant, $plusMoinsId, $plusMoinsVal, $destinationId, $duree, $dateJustif, $centreCoutId, $datePrevue, $dateFin, $observation, $id]
        );
    }

    // ---- Cross-domain Analytics ----

    public function documentsExpiration(array $regionIds, array $entiteIds, int $jours = 30): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT vehicule.immatriculation_vehicule,
                    document_vehicule.nom_document,
                    dossier_vehicule_document.date_expiration_document,
                    DATEDIFF(dossier_vehicule_document.date_expiration_document, CURDATE()) AS jours_restants,
                    dossier_vehicule_document.ref_document
             FROM dossier_vehicule_document
             JOIN document_vehicule ON document_vehicule.id_document = dossier_vehicule_document.id_document
             JOIN vehicule ON vehicule.id_vehicule = dossier_vehicule_document.id_vehicule
             WHERE dossier_vehicule_document.is_active = 1
               AND dossier_vehicule_document.date_expiration_document >= CURDATE()
               AND dossier_vehicule_document.date_expiration_document <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND dossier_vehicule_document.id_vehicule IN (
                   SELECT affectation_vehicule.id_vehicule FROM affectation_vehicule
                   WHERE affectation_vehicule.is_deleted = 0 AND $where
               )
             ORDER BY dossier_vehicule_document.date_expiration_document ASC
             LIMIT 50",
            array_merge($params, [$jours])
        );
    }

    public function chauffeurMaintenanceImpact(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT chauffeur.nom_chauffeur,
                    COUNT(bons_reparation.id_bon_reparation) AS nb_pannes,
                    SUM(montant_reparation + IFNULL(IF(type_plus_ou_moins_value = 0, plus_ou_moins_value_valeur, -plus_ou_moins_value_valeur), 0)) AS total_cout,
                    ROUND(AVG(duree_reparation), 1) AS duree_moyenne,
                    MAX(bons_reparation.date_entree) AS derniere_panne
             FROM bons_reparation
             JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN plus_ou_moins_value ON plus_ou_moins_value.id_plus_ou_moins_value = bons_reparation.id_plus_ou_moins_value
             WHERE affectation_vehicule.is_deleted = 0 AND $where
             GROUP BY chauffeur.id_chauffeur, chauffeur.nom_chauffeur
             HAVING nb_pannes > 0
             ORDER BY total_cout DESC",
            $params
        );
    }

    public function repairVoyageConflicts(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT vehicule.immatriculation_vehicule,
                    chauffeur.nom_chauffeur,
                    bons_reparation.num_bon_reparation,
                    bons_reparation.date_prevue_sortie,
                    voyage.titre_voyage,
                    voyage.date_voyage,
                    DATEDIFF(bons_reparation.date_prevue_sortie, voyage.date_voyage) AS decalage_jours
             FROM bons_reparation
             JOIN affectation_vehicule ON affectation_vehicule.id_affectation = bons_reparation.id_affectation_vehicule
             JOIN vehicule ON vehicule.id_vehicule = affectation_vehicule.id_vehicule
             LEFT JOIN chauffeur ON chauffeur.id_chauffeur = affectation_vehicule.id_chauffeur
             JOIN voyage ON voyage.id_affectation = affectation_vehicule.id_affectation
             WHERE bons_reparation.cloture_reparation = 0
               AND voyage.date_voyage BETWEEN CURDATE() AND bons_reparation.date_prevue_sortie
               AND affectation_vehicule.is_deleted = 0 AND $where
             ORDER BY voyage.date_voyage ASC",
            $params
        );
    }
}
