<?php
/**
 * Export DB data to JSON for Excel template enrichment.
 * Run: php box/export_db.php > box/db_data.json
 */
require_once __DIR__ . '/../env_loader.php';
require_once __DIR__ . '/../db.php';

$con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($con, 'utf8');

$output = [];

// ---- VEHICULES ----
$q = mysqli_query($con, "
    SELECT v.*, m.nom_marque, mv.nom_modele_vehicule, e.nom_entite,
           GROUP_CONCAT(DISTINCT tpv.lib_type_permis SEPARATOR ', ') AS permis_requis
    FROM vehicule v
    LEFT JOIN marque_vehicule m ON v.id_marque = m.id_marque
    LEFT JOIN modele_vehicule mv ON v.id_modele_vehicule = mv.id_modele_vehicule
    LEFT JOIN entite e ON v.id_entite = e.id_entite
    LEFT JOIN qualification_permis_vehicule qpv ON v.id_vehicule = qpv.id_vehicule
    LEFT JOIN type_permis_vehicule tpv ON qpv.id_type_permis = tpv.id_type_permis
    GROUP BY v.id_vehicule
    ORDER BY v.immatriculation_vehicule
");
$vehicules = [];
while ($r = mysqli_fetch_assoc($q)) {
    $vehicules[] = $r;
}
$output['vehicules'] = $vehicules;
$output['vehicules_count'] = count($vehicules);

// ---- CHAUFFEURS ----
$q = mysqli_query($con, "
    SELECT c.*, tpv.lib_type_permis AS permis_lib
    FROM chauffeur c
    LEFT JOIN type_permis_vehicule tpv ON c.id_type_permis = tpv.id_type_permis
    ORDER BY c.nom_chauffeur
");
$chauffeurs = [];
while ($r = mysqli_fetch_assoc($q)) {
    $chauffeurs[] = $r;
}
$output['chauffeurs'] = $chauffeurs;
$output['chauffeurs_count'] = count($chauffeurs);

// ---- AFFECTATIONS ----
$q = mysqli_query($con, "
    SELECT av.*, v.immatriculation_vehicule, c.nom_chauffeur,
           e.nom_entite, r.nom_region,
           tu.lib_type_utilisation, mu.nom_mode_utilisation
    FROM affectation_vehicule av
    LEFT JOIN vehicule v ON av.id_vehicule = v.id_vehicule
    LEFT JOIN chauffeur c ON av.id_chauffeur = c.id_chauffeur
    LEFT JOIN entite e ON av.id_entite = e.id_entite
    LEFT JOIN region r ON av.id_region = r.id_region
    LEFT JOIN type_utilisation_vehicule tu ON av.id_type_utilisation = tu.id_type_utilisation
    LEFT JOIN mode_utilisation_vehicule mu ON av.id_mode_utilisation = mu.id_mode_utilisation
    ORDER BY av.date_debut_affectation DESC
");
$affectations = [];
while ($r = mysqli_fetch_assoc($q)) {
    $affectations[] = $r;
}
$output['affectations'] = $affectations;
$output['affectations_count'] = count($affectations);

// ---- Lookup tables ----
$q = mysqli_query($con, "SELECT * FROM region ORDER BY nom_region");
$output['regions'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

$q = mysqli_query($con, "SELECT * FROM entite ORDER BY nom_entite");
$output['entites'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

$q = mysqli_query($con, "SELECT * FROM type_permis_vehicule ORDER BY lib_type_permis");
$output['type_permis'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

$q = mysqli_query($con, "SELECT * FROM marque_vehicule ORDER BY nom_marque");
$output['marques'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

$q = mysqli_query($con, "SELECT * FROM modele_vehicule ORDER BY nom_modele_vehicule");
$output['modeles'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

$q = mysqli_query($con, "SELECT * FROM type_utilisation_vehicule ORDER BY lib_type_utilisation");
$output['types_utilisation'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

$q = mysqli_query($con, "SELECT * FROM mode_utilisation_vehicule ORDER BY nom_mode_utilisation");
$output['modes_utilisation'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

// ---- Summary ----
$output['summary'] = [
    'vehicules' => count($vehicules),
    'chauffeurs' => count($chauffeurs),
    'affectations' => count($affectations),
    'regions' => count($output['regions']),
    'entites' => count($output['entites']),
    'exported_at' => date('Y-m-d H:i:s'),
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
