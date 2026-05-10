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
