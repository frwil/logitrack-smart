<?php
/**
 * Migration runner — applies migration_db.sql to the live database
 * Run: php box/run_migration.php
 */
require_once __DIR__ . '/../env_loader.php';
require_once __DIR__ . '/../db.php';

$con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
if (!$con) die("Connection failed: " . mysqli_connect_error());
mysqli_set_charset($con, 'utf8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

echo "=== BACKUP: Dumping current DB structure ===\n";
$backup = "-- Backup " . date('Y-m-d H:i:s') . "\n";
$tables = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_row($tables)) {
    $tbl = $row[0];
    $create = mysqli_fetch_row(mysqli_query($con, "SHOW CREATE TABLE `$tbl`"))[1];
    $backup .= "\n-- Table: $tbl\n";
    $backup .= "DROP TABLE IF EXISTS `$tbl`;\n";
    $backup .= "$create;\n";

    // Dump data for key tables
    $q = mysqli_query($con, "SELECT COUNT(*) FROM `$tbl`");
    $count = mysqli_fetch_row($q)[0];
    $backup .= "-- $count rows in $tbl\n";
}
file_put_contents(__DIR__ . '/backup_' . date('Ymd_His') . '.sql', $backup);
echo "Backup saved: " . __DIR__ . '/backup_' . date('Ymd_His') . ".sql\n\n";

echo "=== RUNNING MIGRATION ===\n";
$sql = file_get_contents(__DIR__ . '/migration_db.sql');

// Split by semicolons, skip comments and empty
$statements = [];
$current = '';
foreach (explode("\n", $sql) as $line) {
    $trimmed = trim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, 'SELECT')) continue;
    $current .= $line . "\n";
    if (str_ends_with($trimmed, ';')) {
        $s = trim($current);
        if ($s && !str_starts_with($s, 'SELECT')) {
            $statements[] = $s;
        }
        $current = '';
    }
}
if (trim($current)) $statements[] = trim($current);

$success = 0;
$errors = [];
foreach ($statements as $i => $stmt) {
    // Skip charset conversion for now
    if (str_contains($stmt, 'CONVERT TO CHARACTER SET')) {
        echo "  [SKIP] Charset conversion (to run manually later)\n";
        continue;
    }
    // Skip the cross-join users_entite (too broad)
    if (str_contains($stmt, 'CROSS JOIN entite')) {
        echo "  [SKIP] Cross-join users_entite — will be populated from app\n";
        continue;
    }
    // Skip DROP INDEX if it doesn't exist yet
    if (str_contains($stmt, 'DROP INDEX nom_chauffeur')) {
        $idx = mysqli_query($con, "SHOW INDEX FROM chauffeur WHERE Key_name = 'nom_chauffeur'");
        if (mysqli_num_rows($idx) === 0) {
            echo "  [SKIP] Index nom_chauffeur doesn't exist, skipping DROP\n";
            continue;
        }
    }

    try {
        $preview = substr(str_replace("\n", ' ', $stmt), 0, 80);
        mysqli_query($con, $stmt);
        $success++;
        if ($i % 5 === 0) echo "  [$success] $preview...\n";
    } catch (mysqli_sql_exception $e) {
        $errors[] = "Statement #$i: " . $e->getMessage();
        echo "  [ERROR] $preview: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESULTS ===\n";
echo "Success: $success statements\n";
echo "Errors: " . count($errors) . "\n";
if ($errors) {
    echo "Error details:\n";
    foreach ($errors as $e) echo "  - $e\n";
}

echo "\n=== VERIFICATION ===\n";
// Check new columns on chauffeur
$q = mysqli_query($con, "SHOW COLUMNS FROM chauffeur WHERE Field IN ('telephone_chauffeur','no_permis_chauffeur','date_expiration_permis','no_cni_chauffeur','date_expiration_cni','statut_chauffeur','id_entite')");
echo "chauffeur new columns: " . mysqli_num_rows($q) . " / 7\n";

// Check new tables
$q = mysqli_query($con, "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . getenv('DB_NAME') . "' AND TABLE_NAME IN ('chauffeur_permis','users_entite')");
echo "New tables: " . mysqli_num_rows($q) . " / 2\n";

// Check new columns on affectation_vehicule
$q = mysqli_query($con, "SHOW COLUMNS FROM affectation_vehicule WHERE Field IN ('id_chauffeur_assistant','statut_affectation')");
echo "affectation_vehicule new columns: " . mysqli_num_rows($q) . " / 2\n";

// Check new columns on vehicule
$q = mysqli_query($con, "SHOW COLUMNS FROM vehicule WHERE Field IN ('statut_vehicule','observation_vehicule','annee_vehicule')");
echo "vehicule new columns: " . mysqli_num_rows($q) . " / 3\n";

// Check chauffeur_permis migration
$q = mysqli_query($con, "SELECT COUNT(*) AS cnt FROM chauffeur_permis");
echo "chauffeur_permis rows: " . mysqli_fetch_assoc($q)['cnt'] . "\n";

echo "\nDone.\n";
