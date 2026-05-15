<?php
class ConfigController extends BaseController
{
    private ConfigRepository $repo;

    public function __construct(ConfigRepository $repo) { $this->repo = $repo; }

    // -- Type permis --
    public function fetchTypePermis(): never {
        $row = $this->repo->findTypePermisById((int)$this->post('c-dl-s'));
        if (!$row) $this->jsonError('Introuvable', 404);
        unset($row[0], $row['id_type_permis']);
        $this->json(['data' => $row]);
    }
    public function updateTypePermis(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateTypePermisById((int)$this->post('id-type-permis'), $this->post('lib-type-upd'), $this->post('desc-type-upd') ?: null)
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function deleteTypePermis(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteTypePermisById((int)$this->post('dl-id'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function createTypePermis(): never {
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
        try { $this->repo->transactional(fn() =>
            $this->repo->updateDocumentById((int)$this->post('id-doc'), $this->post('nom-doc-upd'), (int)$this->post('valid-doc-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function createDocument(): never {
        $nom = trim($this->post('nom-doc'));
        if ($nom === '') $this->jsonError('La désignation est obligatoire');
        try { $this->repo->insertDocument($nom, (int)$this->post('valid-doc')); $this->json(); }
        catch (\mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) $this->jsonError('1062');
            $this->jsonError("Erreur lors de l'enregistrement");
        }
    }
    public function deleteDocument(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteDocumentById((int)$this->post('id-doc-del'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    // -- Folder --
    public function updateFolder(): never {
        try { $this->repo->transactional(function() {
            $this->repo->deleteFolderDocumentsByRef($this->post('ref-folder'));
            $ids = $this->post('doc-list-id', []);
            $names = $this->post('doc-list-name', []);
            $dts = $this->post('dt-list-name', []);
            $refDocs = $this->post('refd-list-name', []);
            $vh = $this->post('vh-folder-upd');
            $ref = $this->post('ref-folder');
            for ($i = 0; $i < count($names); $i++) {
                $this->repo->exec(
                    "INSERT INTO dossier_vehicule_document (id_document, date_expiration_document, id_vehicule, id_dossier_vehicule, ref_document)
                     VALUES (?, ?, (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?), (SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?), ?)",
                    [(int)$ids[$i], $dts[$i], (int)$vh, $ref, $refDocs[$i]]
                );
            }
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function createFolder(): never {
        try { $this->repo->transactional(function() {
            $ref = $this->post('ref-folder');
            $this->repo->ensureDossierVehicule($ref);
            $ids = $this->post('doc-list-id', []);
            $names = $this->post('doc-list-name', []);
            $dts = $this->post('dt-list-name', []);
            $refDocs = $this->post('refd-list-name', []);
            $vh = $this->post('vh-folder');
            for ($i = 0; $i < count($names); $i++) {
                $this->repo->exec(
                    "INSERT INTO dossier_vehicule_document (id_document, date_expiration_document, id_vehicule, id_dossier_vehicule, ref_document)
                     VALUES (?, ?, (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?), (SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?), ?)",
                    [(int)$ids[$i], $dts[$i], (int)$vh, $ref, $refDocs[$i]]
                );
            }
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    public function deleteFolder(): never {
        try { $this->repo->transactional(function() {
            $this->repo->deleteFolderDocumentsByRef($this->post('ref-folder-del'));
            $this->repo->deleteDossierByRef($this->post('ref-folder-del'));
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
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
            $configRights = [];
            $userRights = $_SESSION['usr-con']['users-rights'] ?? [];
            foreach ($userRights as $r) {
                if (($r['users_rights_objet'] ?? '') === 'config') {
                    $configRights = explode(',', $r['users_rights_valeur'] ?? '');
                    break;
                }
            }
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
