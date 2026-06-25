<?php
/**
 * One-time migration: Add id_entite to objectif_periode_region.
 * DELETE THIS FILE after running once.
 *
 * Trigger: GET /run_migration_objectif.php?run=1&token=MIGRATE_OBJ_ENTITE_2026
 */
require_once __DIR__ . '/env_loader.php';

if (($_GET['run'] ?? '') !== '1' || ($_GET['token'] ?? '') !== 'MIGRATE_OBJ_ENTITE_2026') {
    header('HTTP/1.1 403 Forbidden');
    die('Forbidden. Add ?run=1&token=MIGRATE_OBJ_ENTITE_2026 to execute.');
}

header('Content-Type: text/plain; charset=utf-8');

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');

$con = mysqli_init();
if (!$con) { die("mysqli_init failed\n"); }
mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
if (!@mysqli_real_connect($con, $host, $user, $pass, $name)) {
    die("Connection failed: " . mysqli_connect_error() . "\n");
}

echo "Connected to $host/$name\n\n";

// Check if column already exists
$res = mysqli_query($con, "SHOW COLUMNS FROM objectif_periode_region LIKE 'id_entite'");
$colExists = mysqli_num_rows($res) > 0;
mysqli_free_result($res);

if ($colExists) {
    echo "Column id_entite already exists. Checking if migration was completed...\n";

    // Check if FK exists
    $res = mysqli_query($con, "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = '$name' AND TABLE_NAME = 'objectif_periode_region'
        AND CONSTRAINT_NAME = 'fk_opr_entite'");
    $fkExists = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);

    // Check unique constraint
    $res = mysqli_query($con, "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = '$name' AND TABLE_NAME = 'objectif_periode_region'
        AND CONSTRAINT_NAME = 'uk_date_region_entite'");
    $ukExists = mysqli_num_rows($res) > 0;
    mysqli_free_result($res);

    if ($fkExists && $ukExists) {
        echo "Migration already completed. Nothing to do.\n";
        exit(0);
    }

    echo "Partial migration detected. Continuing...\n";
}

echo "Starting migration...\n\n";

mysqli_begin_transaction($con);

try {
    // Step 1: Add column if not exists
    if (!$colExists) {
        echo "Step 1: Adding id_entite column...\n";
        if (!mysqli_query($con, "ALTER TABLE objectif_periode_region ADD COLUMN id_entite INT UNSIGNED NULL AFTER id_region")) {
            throw new Exception("Step 1 failed: " . mysqli_error($con));
        }
        echo "  OK\n";
    }

    // Step 2: Assign to lowest entity ID
    echo "Step 2: Assigning existing rows to entity...\n";
    $res = mysqli_query($con, "SELECT MIN(id_entite) AS min_id FROM entite");
    $row = mysqli_fetch_assoc($res);
    $minEntityId = (int)$row['min_id'];
    mysqli_free_result($res);
    echo "  Lowest entity ID: $minEntityId\n";

    if (!mysqli_query($con, "UPDATE objectif_periode_region SET id_entite = $minEntityId WHERE id_entite IS NULL")) {
        throw new Exception("Step 2 failed: " . mysqli_error($con));
    }
    echo "  OK\n";

    // Step 3: Make NOT NULL
    echo "Step 3: Making id_entite NOT NULL...\n";
    if (!mysqli_query($con, "ALTER TABLE objectif_periode_region MODIFY COLUMN id_entite INT UNSIGNED NOT NULL")) {
        throw new Exception("Step 3 failed: " . mysqli_error($con));
    }
    echo "  OK\n";

    // Step 4: Add FK
    echo "Step 4: Adding foreign key...\n";
    if (!isset($fkExists) || !$fkExists) {
        if (!mysqli_query($con, "ALTER TABLE objectif_periode_region ADD CONSTRAINT fk_opr_entite FOREIGN KEY (id_entite) REFERENCES entite(id_entite) ON DELETE CASCADE ON UPDATE CASCADE")) {
            throw new Exception("Step 4 failed: " . mysqli_error($con));
        }
        echo "  OK\n";
    } else {
        echo "  FK already exists, skipping.\n";
    }

    // Step 5: Update unique constraint
    echo "Step 5: Updating unique constraint...\n";
    if (!isset($ukExists) || !$ukExists) {
        // Find and drop old unique constraint
        $res = mysqli_query($con, "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = '$name' AND TABLE_NAME = 'objectif_periode_region'
            AND CONSTRAINT_TYPE = 'UNIQUE' AND CONSTRAINT_NAME NOT IN ('PRIMARY')");
        while ($oldUk = mysqli_fetch_assoc($res)) {
            $oldName = $oldUk['CONSTRAINT_NAME'];
            echo "  Dropping old constraint: $oldName\n";
            if (!mysqli_query($con, "ALTER TABLE objectif_periode_region DROP INDEX `$oldName`")) {
                throw new Exception("Step 5a failed (drop $oldName): " . mysqli_error($con));
            }
        }
        mysqli_free_result($res);

        // Add new constraint
        echo "  Adding new constraint: uk_date_region_entite\n";
        if (!mysqli_query($con, "ALTER TABLE objectif_periode_region ADD UNIQUE KEY uk_date_region_entite (date_objectif_periode, id_region, id_entite)")) {
            throw new Exception("Step 5b failed: " . mysqli_error($con));
        }
        echo "  OK\n";
    } else {
        echo "  UK already exists, skipping.\n";
    }

    mysqli_commit($con);
    echo "\n=== Migration completed successfully! ===\n";

} catch (Exception $e) {
    mysqli_rollback($con);
    echo "\n=== Migration FAILED ===\n";
    echo $e->getMessage() . "\n";
    echo "All changes have been rolled back.\n";
}

mysqli_close($con);
