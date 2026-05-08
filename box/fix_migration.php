<?php
require_once __DIR__ . '/../env_loader.php';
$con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Migrate existing permis to chauffeur_permis
$q = mysqli_query($con, "INSERT INTO chauffeur_permis (id_chauffeur, id_type_permis, is_principal)
    SELECT c.id_chauffeur, c.id_type_permis, 1
    FROM chauffeur c
    WHERE c.id_type_permis IS NOT NULL AND c.id_type_permis > 0
    AND NOT EXISTS (
        SELECT 1 FROM chauffeur_permis cp
        WHERE cp.id_chauffeur = c.id_chauffeur AND cp.id_type_permis = c.id_type_permis
    )");
echo "Permis migrés: " . mysqli_affected_rows($con) . PHP_EOL;

// Update annee_vehicule
mysqli_query($con, "UPDATE vehicule SET annee_vehicule = YEAR(premiere_utilisation)
    WHERE premiere_utilisation IS NOT NULL AND annee_vehicule IS NULL");
echo "Années véhicule: " . mysqli_affected_rows($con) . PHP_EOL;

// Update affectation statuts
mysqli_query($con, "UPDATE affectation_vehicule SET statut_affectation = 'Terminé' WHERE is_ferme = 1");
echo "Affectations terminées: " . mysqli_affected_rows($con) . PHP_EOL;

// Final verification
echo PHP_EOL . "=== FINAL STATE ===" . PHP_EOL;
$q = mysqli_query($con, "SELECT COUNT(*) FROM chauffeur_permis");
echo "chauffeur_permis: " . mysqli_fetch_row($q)[0] . " rows" . PHP_EOL;

$q = mysqli_query($con, "SHOW COLUMNS FROM chauffeur WHERE Field LIKE 'telephone%' OR Field LIKE 'no_permis%' OR Field LIKE 'statut%' OR Field = 'id_entite'");
echo "chauffeur new cols: " . mysqli_num_rows($q) . PHP_EOL;

$q = mysqli_query($con, "SHOW TABLES LIKE 'chauffeur_permis'");
echo "chauffeur_permis table: " . (mysqli_num_rows($q) ? 'YES' : 'NO') . PHP_EOL;

$q = mysqli_query($con, "SHOW TABLES LIKE 'users_entite'");
echo "users_entite table: " . (mysqli_num_rows($q) ? 'YES' : 'NO') . PHP_EOL;

$q = mysqli_query($con, "SELECT COUNT(*) FROM vehicule WHERE statut_vehicule = 'FONCTIONNEL'");
echo "Véhicules avec statut: " . mysqli_fetch_row($q)[0] . " (default FONCTIONNEL)" . PHP_EOL;

echo PHP_EOL . "Migration complète." . PHP_EOL;
