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

    public function findPeriodesReleve(string $start, string $end): array
    {
        return $this->select(
            "SELECT DISTINCT periode_releve FROM releve_kms_vehicule
             WHERE date_debut_periode_releve >= ? AND date_fin_periode_releve <= ?
             ORDER BY periode_releve",
            [$start, $end]
        );
    }

    public function findKmReleve(string $periodeHash, string $affectationHash): ?array
    {
        return $this->selectOne(
            "SELECT km_releve FROM releve_kms_vehicule
             WHERE SHA1(periode_releve) = ?
             AND id_affectation_vehicule = (
               SELECT id_affectation FROM affectation_vehicule WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?
             )",
            [$periodeHash, $affectationHash]
        );
    }

    public function updateReleveKms(int $kms, string $periodeHash, string $affectationHash): bool
    {
        return $this->exec(
            "UPDATE releve_kms_vehicule SET km_releve = ?
             WHERE SHA1(periode_releve) = ?
             AND id_affectation_vehicule = (
               SELECT id_affectation FROM affectation_vehicule WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?
             )",
            [$kms, $periodeHash, $affectationHash]
        );
    }

    public function findMaxKmByAffectationHash(string $affectationHash, string $dateFin): ?array
    {
        return $this->selectOne(
            "SELECT MAX(km_releve) AS km FROM releve_kms_vehicule
             WHERE id_affectation_vehicule = (
               SELECT id_affectation FROM affectation_vehicule WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?
             )
             AND date_fin_periode_releve <= ?
             ORDER BY date_fin_periode_releve DESC",
            [$affectationHash, $dateFin]
        );
    }

    public function countReleveByPeriode(string $periode, string $affectationHash, string $start, string $end): int
    {
        return count($this->select(
            "SELECT * FROM releve_kms_vehicule
             WHERE periode_releve = ?
             AND id_affectation_vehicule = (
               SELECT id_affectation FROM affectation_vehicule WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?
             )
             AND date_debut_periode_releve = ? AND date_fin_periode_releve = ?",
            [$periode, $affectationHash, $start, $end]
        ));
    }

    public function countRelevesByAffectationAndDateRange(string $affectationHash, string $start, string $end): int
    {
        return count($this->select(
            "SELECT * FROM releve_kms_vehicule
             WHERE id_affectation_vehicule = (
               SELECT id_affectation FROM affectation_vehicule WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?
             )
             AND date_debut_periode_releve >= ? AND date_fin_periode_releve <= ?",
            [$affectationHash, $start, $end]
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

    public function findVidangeByCode(string $code): ?array
    {
        return $this->selectOne(
            "SELECT * FROM vidange_vehicule WHERE code_vidange = ?",
            [$code]
        );
    }

    public function updateVidangeByCode(
        string $code,
        string $affectationHash,
        string $date,
        int $kmAvant,
        int $kmProchaine,
        string $prestataireHash,
        ?string $commentaire
    ): bool {
        return $this->exec(
            "UPDATE vidange_vehicule SET
             id_affectation_vehicule = (SELECT id_affectation FROM affectation_vehicule WHERE SHA1(CONCAT(id_affectation, id_vehicule)) = ?),
             date_vidange = ?,
             km_vidange = ?,
             km_prochaine_vidange = ?,
             id_prestataire = (SELECT id_prestataire FROM prestataire_intervention WHERE SHA1(CONCAT(id_prestataire, nom_prestataire)) = ?),
             commentaire_vidange = ?
             WHERE code_vidange = ?",
            [$affectationHash, $date, $kmAvant, $kmProchaine, $prestataireHash, $commentaire, $code]
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

    public function deleteVidangeByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM vidange_vehicule WHERE SHA1(CONCAT(id_vidange_vehicule, code_vidange)) = ?",
            [$hash]
        );
    }

    // ---- Prestataire ----

    public function findAllPrestataires(): array
    {
        return $this->select("SELECT * FROM prestataire_intervention", []);
    }

    public function findPrestataireByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT *, SHA1(CONCAT(id_prestataire, nom_prestataire)) AS id_pt
             FROM prestataire_intervention
             WHERE SHA1(CONCAT(id_prestataire, nom_prestataire)) = ?",
            [$hash]
        );
    }

    public function updatePrestataireByHash(string $hash, string $nom, ?string $contact, ?string $localisation): bool
    {
        return $this->exec(
            "UPDATE prestataire_intervention SET nom_prestataire = ?, contact_prestataire = ?, localisation_prestataire = ?
             WHERE SHA1(CONCAT(id_prestataire, nom_prestataire)) = ?",
            [$nom, $contact, $localisation, $hash]
        );
    }

    public function deletePrestataireByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM prestataire_intervention WHERE SHA1(CONCAT(id_prestataire, nom_prestataire)) = ?",
            [$hash]
        );
    }

    // ---- Centre de coûts ----

    public function findAllCentresCouts(): array
    {
        return $this->select("SELECT * FROM centre_couts", []);
    }

    public function findCentreCoutByHash(string $hash): ?array
    {
        return $this->selectOne(
            "SELECT *, SHA1(CONCAT(id_centre_cout, lib_centre_cout)) AS id_cc
             FROM centre_couts
             WHERE SHA1(CONCAT(id_centre_cout, lib_centre_cout)) = ?",
            [$hash]
        );
    }

    public function deleteCentreCoutByHash(string $hash): bool
    {
        return $this->exec(
            "DELETE FROM centre_couts WHERE SHA1(CONCAT(id_centre_cout, lib_centre_cout)) = ?",
            [$hash]
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

    // ---- Plus ou moins value ----

    public function findAllPlusOuMoinsValue(): array
    {
        return $this->select("SELECT * FROM plus_ou_moins_value", []);
    }
}
