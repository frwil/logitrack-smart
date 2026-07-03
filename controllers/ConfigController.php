<?php
class ConfigController extends BaseController
{
    private ConfigRepository $repo;

    public function __construct(ConfigRepository $repo) { $this->repo = $repo; }

    /** Check a config sub-right with backward-compat fallback. */
    private function hasConfigSubRight(string $specific, string $fallback): bool
    {
        $rights = getUserRightsFor('config');
        $allConfigSpecifics = [
            'backup',
            'viewPermis','savePermis','updPermis','delPermis',
            'viewDocs','saveDocs','updDocs','delDocs',
            'viewFolders','saveFolders','updFolders','delFolders',
        ];
        return hasSubRight($specific, $fallback, $rights, $allConfigSpecifics);
    }

    /** Require a config sub-right — die with 403 if missing. */
    private function requireConfigSubRight(string $specific, string $fallback): void
    {
        if (!$this->hasConfigSubRight($specific, $fallback)) {
            $this->jsonError('Accès non autorisé', 403);
        }
    }

    // -- Type permis --
    public function fetchTypePermis(): never {
        $row = $this->repo->findTypePermisById((int)$this->post('c-dl-s'));
        if (!$row) $this->jsonError('Introuvable', 404);
        unset($row[0], $row['id_type_permis']);
        $this->json(['data' => $row]);
    }
    public function updateTypePermis(): never {
        $this->requireConfigSubRight('updPermis', 'upd');
        try { $this->repo->transactional(fn() =>
            $this->repo->updateTypePermisById((int)$this->post('id-type-permis'), $this->post('lib-type-upd'), $this->post('desc-type-upd') ?: null)
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function deleteTypePermis(): never {
        $this->requireConfigSubRight('delPermis', 'del');
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteTypePermisById((int)$this->post('dl-id'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function createTypePermis(): never {
        $this->requireConfigSubRight('savePermis', 'save');
        $lib = trim($this->post('lib-type'));
        if ($lib === '') $this->jsonError('Le libellé est obligatoire');
        try { $this->repo->insertTypePermis($lib, $this->post('desc-type') ?: null); $this->json(); }
        catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('1062');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }

    // -- Document --
    public function updateDocument(): never {
        $this->requireConfigSubRight('updDocs', 'upd');
        try { $this->repo->transactional(fn() =>
            $this->repo->updateDocumentById((int)$this->post('id-doc'), $this->post('nom-doc-upd'), (int)$this->post('valid-doc-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function createDocument(): never {
        $this->requireConfigSubRight('saveDocs', 'save');
        $nom = trim($this->post('nom-doc'));
        if ($nom === '') $this->jsonError('La désignation est obligatoire');
        try { $this->repo->insertDocument($nom, (int)$this->post('valid-doc')); $this->json(); }
        catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('1062');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }
    public function deleteDocument(): never {
        $this->requireConfigSubRight('delDocs', 'del');
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteDocumentById((int)$this->post('id-doc-del'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    // -- Folder --
    public function updateFolder(): never {
        $this->requireConfigSubRight('updFolders', 'upd');
        try { $this->repo->transactional(function() {
            $vh = $this->post('vh-folder-upd');
            $ref = $this->post('ref-folder');
            // Capture existing fichier values before deletion, keyed by id_document
            $existingDocs = $this->repo->findFolderByRef($ref);
            $fichierMap = [];
            foreach ($existingDocs as $ed) {
                if (!empty($ed['fichier'])) {
                    $fichierMap[(int)$ed['id_document']] = $ed['fichier'];
                }
            }
            // Remove old documents for this dossier
            $this->repo->deleteFolderDocumentsByRef($ref);
            // Deactivate any remaining active docs for this vehicle (other dossiers)
            $this->repo->exec(
                "UPDATE dossier_vehicule_document SET is_active = 0
                 WHERE id_vehicule = (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?)
                 AND is_active = 1",
                [(int)$vh]
            );
            $ids = $this->post('doc-list-id', []);
            $names = $this->post('doc-list-name', []);
            $dts = $this->post('dt-list-name', []);
            $refDocs = $this->post('refd-list-name', []);
            for ($i = 0; $i < count($names); $i++) {
                $fichier = $fichierMap[(int)$ids[$i]] ?? null;
                $this->repo->exec(
                    "INSERT INTO dossier_vehicule_document (id_document, date_expiration_document, id_vehicule, id_dossier_vehicule, ref_document, is_active, fichier)
                     VALUES (?, ?, (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?), (SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?), ?, 1, ?)",
                    [(int)$ids[$i], $dts[$i], (int)$vh, $ref, $refDocs[$i], $fichier]
                );
            }
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function createFolder(): never {
        $this->requireConfigSubRight('saveFolders', 'save');
        try { $this->repo->transactional(function() {
            $ref = $this->post('ref-folder');
            $this->repo->ensureDossierVehicule($ref);
            $vh = $this->post('vh-folder');
            // Deactivate any existing active docs for this vehicle
            $this->repo->exec(
                "UPDATE dossier_vehicule_document SET is_active = 0
                 WHERE id_vehicule = (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?)
                 AND is_active = 1",
                [(int)$vh]
            );
            $ids = $this->post('doc-list-id', []);
            $names = $this->post('doc-list-name', []);
            $dts = $this->post('dt-list-name', []);
            $refDocs = $this->post('refd-list-name', []);
            for ($i = 0; $i < count($names); $i++) {
                $this->repo->exec(
                    "INSERT INTO dossier_vehicule_document (id_document, date_expiration_document, id_vehicule, id_dossier_vehicule, ref_document, is_active)
                     VALUES (?, ?, (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?), (SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?), ?, 1)",
                    [(int)$ids[$i], $dts[$i], (int)$vh, $ref, $refDocs[$i]]
                );
            }
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function deleteFolder(): never {
        $this->requireConfigSubRight('delFolders', 'del');
        try { $this->repo->transactional(function() {
            $this->repo->deleteFolderDocumentsByRef($this->post('ref-folder-del'));
            $this->repo->deleteDossierByRef($this->post('ref-folder-del'));
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    // -- Folder file upload / delete --

    public function uploadFolderFile(): never
    {
        $this->requireConfigSubRight('updFolders', 'upd');

        $refDossier = $this->post('ref-dossier');
        $idDocument = (int)$this->post('id-document');
        if (!$refDossier || !$idDocument) {
            $this->jsonError('Paramètres manquants');
        }

        // Validate file presence
        if (!isset($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError('Aucun fichier valide reçu');
        }

        $file = $_FILES['fichier'];

        // Validate file size (max 10 MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $this->jsonError('Le fichier ne doit pas dépasser 10 Mo');
        }

        // Validate file extension
        $allowedExtensions = ['pdf', 'png', 'jpg', 'jpeg', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            $this->jsonError('Type de fichier non autorisé. Types acceptés : PDF, PNG, JPG, GIF');
        }

        // Validate MIME type server-side (additional security)
        $allowedMimes = ['application/pdf', 'image/png', 'image/jpeg', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedMimes, true)) {
            $this->jsonError('Type MIME du fichier non autorisé');
        }

        // Create dossier upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../uploads/dossiers/' . $refDossier;
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0755, true)) {
                $this->jsonError("Impossible de créer le répertoire de destination");
            }
        }

        // Generate unique filename: {id_document}_{timestamp}.{ext}
        $filename = $idDocument . '_' . time() . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->jsonError("Erreur lors de l'enregistrement du fichier");
        }

        // Delete old file for this document if replacing
        $docId = $this->repo->findDossierDocumentId($refDossier, $idDocument);
        if ($docId) {
            $oldFile = $this->repo->findDocumentFichier($docId);
            if ($oldFile) {
                $oldPath = __DIR__ . '/../uploads/dossiers/' . $oldFile;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            // Update DB: store relative path "ref_dossier/filename"
            $relativePath = $refDossier . '/' . $filename;
            $this->repo->updateDocumentFichier($docId, $relativePath);
        }

        $this->json([
            'fichier' => $relativePath ?? ($refDossier . '/' . $filename),
            'url' => 'uploads/dossiers/' . ($relativePath ?? ($refDossier . '/' . $filename)),
        ]);
    }

    public function deleteFolderFile(): never
    {
        $this->requireConfigSubRight('updFolders', 'upd');

        $refDossier = $this->post('ref-dossier');
        $idDocument = (int)$this->post('id-document');
        if (!$refDossier || !$idDocument) {
            $this->jsonError('Paramètres manquants');
        }

        $docId = $this->repo->findDossierDocumentId($refDossier, $idDocument);
        if (!$docId) {
            $this->jsonError('Document introuvable');
        }

        // Get current file path from DB
        $relativePath = $this->repo->findDocumentFichier($docId);
        if (!$relativePath) {
            $this->jsonError('Aucun fichier associé à ce document');
        }

        // Delete file from disk
        $fullPath = __DIR__ . '/../uploads/dossiers/' . $relativePath;
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }

        // Remove empty dossier directory if no more files
        $dirPath = __DIR__ . '/../uploads/dossiers/' . dirname($relativePath);
        if (is_dir($dirPath) && count(scandir($dirPath)) <= 2) {
            @rmdir($dirPath);
        }

        // Update DB
        $this->repo->updateDocumentFichier($docId, null);

        $this->json();
    }

    // -- Paramètres --
    public function updateDevise(): never
    {
        $v = trim($this->post('update-devise'));
        if ($v === '') $this->jsonError('La devise est obligatoire');
        $this->repo->setParametre('devise', $v);
        $this->json();
    }

    public function backupDatabase(): never
    {
        // Only superadmin or users with explicit 'backup' right on config
        $isSuperadmin = $_SESSION['usr-con']['is-superadmin'] ?? false;
        if (!$isSuperadmin) {
            $configRights = getUserRightsFor('config');
            if (!in_array('backup', $configRights)) {
                http_response_code(403);
                $this->sendJson(['success' => false, 'error' => 'Accès non autorisé']);
            }
        }

        $con = $GLOBALS['con'] ?? null;
        if (!$con) $this->jsonError('Base de données indisponible');

        ob_start();

        echo "-- LogiTrack Database Backup\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "-- Database: " . getenv('DB_NAME') . "\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n";
        echo "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
        echo "SET NAMES utf8mb4;\n\n";

        $tables = [];
        $result = mysqli_query($con, "SHOW TABLES");
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            $row2 = mysqli_fetch_row(mysqli_query($con, "SHOW CREATE TABLE `$table`"));
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $row2[1] . ";\n\n";

            $dataResult = mysqli_query($con, "SELECT * FROM `$table`");
            if ($dataResult && mysqli_num_rows($dataResult) > 0) {
                echo "INSERT INTO `$table` VALUES\n";
                $firstRow = true;
                while ($dataRow = mysqli_fetch_row($dataResult)) {
                    $vals = [];
                    foreach ($dataRow as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = "'" . mysqli_real_escape_string($con, $val) . "'";
                        }
                    }
                    if (!$firstRow) echo ",\n";
                    echo "(" . implode(',', $vals) . ")";
                    $firstRow = false;
                }
                echo ";\n\n";
            }
        }

        echo "SET FOREIGN_KEY_CHECKS=1;\n";

        $sql = ob_get_clean();
        $filename = 'logitrack_backup_' . date('Ymd_His') . '.sql';

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        die($sql);
    }
}
