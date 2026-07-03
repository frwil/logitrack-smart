<?php
/**
 * Config repository — type_permis, document_vehicule, dossier_vehicule.
 */
class ConfigRepository extends BaseRepository
{
    // ---- Type permis (Drive Licence) ----

    public function findAllTypePermis(): array
    {
        return $this->select("SELECT * FROM type_permis_vehicule", []);
    }

    public function findTypePermisById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM type_permis_vehicule
             WHERE id_type_permis = ?",
            [$id]
        );
    }

    public function updateTypePermisById(int $id, string $lib, ?string $desc): bool
    {
        return $this->exec(
            "UPDATE type_permis_vehicule SET lib_type_permis = ?, desc_type_permis = ?
             WHERE id_type_permis = ?",
            [$lib, $desc, $id]
        );
    }

    public function deleteTypePermisById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM type_permis_vehicule WHERE id_type_permis = ?",
            [$id]
        );
    }

    public function insertTypePermis(string $lib, ?string $desc): int|string
    {
        return $this->insertGetId(
            "INSERT INTO type_permis_vehicule (lib_type_permis, desc_type_permis) VALUES (?, ?)",
            [$lib, $desc]
        );
    }

    /** Permis for a specific vehicle. */
    public function findPermisByVehiculeId(int $vehiculeId): array
    {
        return $this->select(
            "SELECT * FROM type_permis_vehicule
             INNER JOIN qualification_permis_vehicule
             ON qualification_permis_vehicule.id_type_permis = type_permis_vehicule.id_type_permis
             AND id_vehicule = ?",
            [$vehiculeId]
        );
    }

    // ---- Document véhicule ----

    public function findAllDocuments(): array
    {
        return $this->select("SELECT * FROM document_vehicule", []);
    }

    public function findDocumentById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM document_vehicule WHERE id_document = ?",
            [$id]
        );
    }

    public function findDocumentByHash(string $hash): ?array
    {
        return $this->findDocumentById((int)$hash);
    }

    public function updateDocumentById(int $id, string $nom, int $validite): bool
    {
        return $this->exec(
            "UPDATE document_vehicule SET nom_document = ?, validite_document = ?
             WHERE id_document = ?",
            [$nom, $validite, $id]
        );
    }

    public function insertDocument(string $nom, int $validite): int|string
    {
        return $this->insertGetId(
            "INSERT INTO document_vehicule (nom_document, validite_document) VALUES (?, ?)",
            [$nom, $validite]
        );
    }

    public function deleteDocumentById(int $id): bool
    {
        return $this->exec(
            "DELETE FROM document_vehicule WHERE id_document = ?",
            [$id]
        );
    }

    // ---- Dossier véhicule ----

    /** All active vehicle folders with documents for a region. */
    public function findAllFoldersByRegion(int $regionId): array
    {
        return $this->select(
            "SELECT *,
             (SELECT id_dossier_vehicule_document FROM dossier_vehicule_document
              WHERE dossier_vehicule_document.id_vehicule = vehicule.id_vehicule LIMIT 1) AS id_v
             FROM vehicule
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_vehicule = vehicule.id_vehicule
             LEFT JOIN chauffeur c ON c.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN marque_vehicule ON marque_vehicule.id_marque = vehicule.id_marque
             LEFT JOIN entite ON entite.id_entite = vehicule.id_entite
             WHERE is_ferme = 0 AND affectation_vehicule.is_deleted = 0 AND affectation_vehicule.id_region = ?
             ORDER BY immatriculation_vehicule",
            [$regionId]
        );
    }

    /** All active vehicle folders filtered by region + entity context. */
    public function findAllFoldersByContext(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        return $this->select(
            "SELECT *,
             (SELECT id_dossier_vehicule_document FROM dossier_vehicule_document
              WHERE dossier_vehicule_document.id_vehicule = vehicule.id_vehicule LIMIT 1) AS id_v
             FROM vehicule
             LEFT JOIN affectation_vehicule ON affectation_vehicule.id_vehicule = vehicule.id_vehicule
             LEFT JOIN chauffeur c ON c.id_chauffeur = affectation_vehicule.id_chauffeur
             LEFT JOIN marque_vehicule ON marque_vehicule.id_marque = vehicule.id_marque
             LEFT JOIN entite ON entite.id_entite = vehicule.id_entite
             WHERE is_ferme = 0 AND affectation_vehicule.is_deleted = 0 AND $where
             ORDER BY immatriculation_vehicule",
            $params
        );
    }

    /** Single folder document for a vehicle. */
    public function findFolderDocument(int $vehiculeId, int $documentId): ?array
    {
        return $this->selectOne(
            "SELECT * FROM dossier_vehicule_document
             LEFT JOIN dossier_vehicule ON dossier_vehicule.id_dossier_vehicule = dossier_vehicule_document.id_dossier_vehicule
             WHERE id_vehicule = ? AND id_document = ? AND is_active = 1
             LIMIT 1",
            [$vehiculeId, $documentId]
        );
    }

    /** Find vehicles that have documents from multiple active dossiers. */
    public function findVehiclesWithMultipleActiveDossiers(): array
    {
        return $this->select(
            "SELECT dvd.id_vehicule, v.immatriculation_vehicule,
                    COUNT(DISTINCT dvd.id_dossier_vehicule) as nb_dossiers,
                    COUNT(*) as nb_docs
             FROM dossier_vehicule_document dvd
             JOIN vehicule v ON v.id_vehicule = dvd.id_vehicule
             WHERE dvd.is_active = 1
             GROUP BY dvd.id_vehicule, v.immatriculation_vehicule
             HAVING nb_dossiers > 1
             ORDER BY nb_dossiers DESC",
            []
        );
    }

    /** All documents in a folder by ref_dossier. */
    public function findFolderByRef(string $refDossier): array
    {
        return $this->select(
            "SELECT *,
             (SELECT dv.id_document FROM document_vehicule dv
              WHERE dv.id_document = dossier_vehicule_document.id_document) AS iddoc
             FROM dossier_vehicule_document
             LEFT JOIN dossier_vehicule ON dossier_vehicule.id_dossier_vehicule = dossier_vehicule_document.id_dossier_vehicule
             LEFT JOIN document_vehicule ON document_vehicule.id_document = dossier_vehicule_document.id_document
             WHERE ref_dossier = ?",
            [$refDossier]
        );
    }

    public function deleteFolderDocumentsByRef(string $refDossier): bool
    {
        return $this->exec(
            "DELETE FROM dossier_vehicule_document
             WHERE id_dossier_vehicule = (SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?)",
            [$refDossier]
        );
    }

    public function ensureDossierVehicule(string $refDossier): void
    {
        $exists = $this->selectOne(
            "SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?",
            [$refDossier]
        );
        if (!$exists) {
            $this->insertGetId(
                "INSERT INTO dossier_vehicule (ref_dossier) VALUES (?)",
                [$refDossier]
            );
        }
    }

    public function deleteDossierByRef(string $refDossier): bool
    {
        return $this->exec(
            "DELETE FROM dossier_vehicule WHERE ref_dossier = ?",
            [$refDossier]
        );
    }

    // ---- Dossier history & file management ----

    /** All dossiers (active + inactive) for a vehicle with their documents. */
    public function findAllDossiersByVehicule(int $vehiculeId): array
    {
        return $this->select(
            "SELECT dv.id_dossier_vehicule, dv.ref_dossier,
                    dvd.id_dossier_vehicule_document, dvd.id_document,
                    dvd.date_expiration_document, dvd.ref_document,
                    dvd.is_active, dvd.fichier,
                    doc.nom_document, doc.validite_document
             FROM dossier_vehicule dv
             INNER JOIN dossier_vehicule_document dvd
                 ON dvd.id_dossier_vehicule = dv.id_dossier_vehicule
             LEFT JOIN document_vehicule doc
                 ON doc.id_document = dvd.id_document
             WHERE dvd.id_vehicule = ?
             ORDER BY dv.id_dossier_vehicule DESC, dvd.id_document",
            [$vehiculeId]
        );
    }

    /** Vehicle info for dossier history page header. */
    public function findVehiculeInfo(int $vehiculeId): ?array
    {
        return $this->selectOne(
            "SELECT v.id_vehicule, v.immatriculation_vehicule, v.chassis_vehicule,
                    v.premiere_utilisation, v.nb_place, v.type_carburant,
                    m.nom_marque, e.nom_entite,
                    (SELECT nom_chauffeur FROM chauffeur WHERE id_chauffeur = (
                        SELECT id_chauffeur FROM affectation_vehicule
                        WHERE id_vehicule = v.id_vehicule AND is_ferme = 0 AND is_deleted = 0
                        LIMIT 1
                    )) AS nom_chauffeur
             FROM vehicule v
             LEFT JOIN marque_vehicule m ON m.id_marque = v.id_marque
             LEFT JOIN entite e ON e.id_entite = v.id_entite
             WHERE v.id_vehicule = ?",
            [$vehiculeId]
        );
    }

    /** Find the PK of a dossier document by ref_dossier + id_document. */
    public function findDossierDocumentId(string $refDossier, int $idDocument): ?int
    {
        $row = $this->selectOne(
            "SELECT dvd.id_dossier_vehicule_document
             FROM dossier_vehicule_document dvd
             JOIN dossier_vehicule dv ON dv.id_dossier_vehicule = dvd.id_dossier_vehicule
             WHERE dv.ref_dossier = ? AND dvd.id_document = ?
             ORDER BY dvd.id_dossier_vehicule_document DESC
             LIMIT 1",
            [$refDossier, $idDocument]
        );
        return $row ? (int)$row['id_dossier_vehicule_document'] : null;
    }

    /** Update the fichier path for a dossier document. */
    public function updateDocumentFichier(int $docId, ?string $fichier): bool
    {
        return $this->exec(
            "UPDATE dossier_vehicule_document SET fichier = ? WHERE id_dossier_vehicule_document = ?",
            [$fichier, $docId]
        );
    }

    /** Get the current fichier path for a document (used before deletion). */
    public function findDocumentFichier(int $docId): ?string
    {
        $row = $this->selectOne(
            "SELECT fichier FROM dossier_vehicule_document WHERE id_dossier_vehicule_document = ?",
            [$docId]
        );
        return $row ? $row['fichier'] : null;
    }

    /** Folder statistics for dashboard cards. */
    public function getFolderStats(array $regionIds, array $entiteIds): array
    {
        [$where, $params] = db_context_filter($regionIds, $entiteIds);
        $sql = "SELECT
                    COUNT(DISTINCT v.id_vehicule) as total_vehicules,
                    COUNT(DISTINCT CASE WHEN dvd_active.id_vehicule IS NOT NULL THEN v.id_vehicule END) as avec_dossier,
                    COUNT(DISTINCT dv_active.id_dossier_vehicule) as total_dossiers
                FROM vehicule v
                INNER JOIN affectation_vehicule av ON av.id_vehicule = v.id_vehicule AND av.is_ferme = 0 AND av.is_deleted = 0
                LEFT JOIN (SELECT DISTINCT id_vehicule FROM dossier_vehicule_document WHERE is_active = 1) dvd_active ON dvd_active.id_vehicule = v.id_vehicule
                LEFT JOIN (SELECT DISTINCT id_dossier_vehicule, id_vehicule FROM dossier_vehicule_document WHERE is_active = 1) dv_active ON dv_active.id_vehicule = v.id_vehicule
                WHERE $where";
        $row = $this->selectOne($sql, $params);
        $total = (int)($row['total_vehicules'] ?? 0);
        $avec  = (int)($row['avec_dossier'] ?? 0);
        $dossiers = (int)($row['total_dossiers'] ?? 0);
        return [
            'total_vehicules' => $total,
            'avec_dossier'    => $avec,
            'sans_dossier'    => $total - $avec,
            'total_dossiers'  => $dossiers,
        ];
    }

    // ---- Paramètres globaux (key-value) ----

    public function getParametre(string $cle, string $default = ''): string
    {
        $row = $this->selectOne(
            "SELECT valeur FROM parametres WHERE cle = ?",
            [$cle]
        );
        return $row ? $row['valeur'] : $default;
    }

    public function setParametre(string $cle, string $valeur): bool
    {
        return $this->exec(
            "INSERT INTO parametres (cle, valeur) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)",
            [$cle, $valeur]
        );
    }
}
