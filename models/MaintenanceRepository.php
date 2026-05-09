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
                AND affectation_vehicule.id_region = region.id_region ";
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
                AND $ctxWhere ";
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
             AND region.id_region = ?
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
             AND $where
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
             AND region.id_region = ?
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
             AND $where
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
             LEFT JOIN centre_couts ON centre_couts.id_centre_cout = bons_reparation.id_centre_cout",
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
             WHERE $where",
            $params
        );
    }

    // ---- Plus ou moins value ----

    public function findAllPlusOuMoinsValue(): array
    {
        return $this->select("SELECT * FROM plus_ou_moins_value", []);
    }
}
