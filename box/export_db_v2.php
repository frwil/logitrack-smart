<?php
/**
 * Export DB data optimized for Excel template population.
 * Groups vehicles with permis, drivers with full details, assignments with dates.
 */
require_once __DIR__ . '/../env_loader.php';
require_once __DIR__ . '/../db.php';

$con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
if (!$con) die("Connection failed: " . mysqli_connect_error());
mysqli_set_charset($con, 'utf8');

$output = [];

// ---- VEHICULES with modele, marque, entite, permis_requis ----
$q = mysqli_query($con, "
    SELECT
        v.id_vehicule,
        v.immatriculation_vehicule,
        v.chassis_vehicule,
        v.premiere_utilisation,
        v.expiration_carte_grise,
        v.nb_place,
        v.type_carburant,
        v.puissance_vehicule,
        v.capacite_consommation_vehicule,
        m.nom_marque,
        mv.nom_modele_vehicule,
        e.nom_entite,
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
$output['vehicules'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

// ---- CHAUFFEURS with permis ----
$q = mysqli_query($con, "
    SELECT
        c.id_chauffeur,
        c.nom_chauffeur,
        tpv.lib_type_permis AS permis_lib,
        c.telephone_chauffeur,
        c.no_permis_chauffeur,
        c.date_expiration_permis,
        c.no_cni_chauffeur,
        c.date_expiration_cni,
        c.statut_chauffeur,
        e.nom_entite
    FROM chauffeur c
    LEFT JOIN type_permis_vehicule tpv ON c.id_type_permis = tpv.id_type_permis
    LEFT JOIN entite e ON c.id_entite = e.id_entite
    ORDER BY c.nom_chauffeur
");
$output['chauffeurs'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

// ---- AFFECTATIONS fully resolved ----
$q = mysqli_query($con, "
    SELECT
        av.id_affectation,
        av.date_debut_affectation,
        av.date_fin_affectation,
        av.objet_affectation,
        av.is_ferme,
        av.statut_affectation,
        v.immatriculation_vehicule,
        c.nom_chauffeur AS chauffeur_principal,
        ca.nom_chauffeur AS chauffeur_assistant,
        e.nom_entite,
        r.nom_region,
        tu.lib_type_utilisation,
        mu.nom_mode_utilisation
    FROM affectation_vehicule av
    LEFT JOIN vehicule v ON av.id_vehicule = v.id_vehicule
    LEFT JOIN chauffeur c ON av.id_chauffeur = c.id_chauffeur
    LEFT JOIN chauffeur ca ON av.id_chauffeur_assistant = ca.id_chauffeur
    LEFT JOIN entite e ON av.id_entite = e.id_entite
    LEFT JOIN region r ON av.id_region = r.id_region
    LEFT JOIN type_utilisation_vehicule tu ON av.id_type_utilisation = tu.id_type_utilisation
    LEFT JOIN mode_utilisation_vehicule mu ON av.id_mode_utilisation = mu.id_mode_utilisation
    ORDER BY av.date_debut_affectation DESC
");
$output['affectations'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

// ---- Driver entities inferred from affectations ----
$q = mysqli_query($con, "
    SELECT DISTINCT c.nom_chauffeur, e.nom_entite
    FROM chauffeur c
    JOIN affectation_vehicule av ON av.id_chauffeur = c.id_chauffeur
    JOIN entite e ON av.id_entite = e.id_entite
    WHERE e.nom_entite IS NOT NULL AND e.nom_entite != ''
");
$output['chauffeur_entites'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

// ---- Vehicle entities inferred from affectations (if id_entite is null on vehicule) ----
$q = mysqli_query($con, "
    SELECT DISTINCT v.immatriculation_vehicule, e.nom_entite
    FROM vehicule v
    JOIN affectation_vehicule av ON av.id_vehicule = v.id_vehicule
    JOIN entite e ON av.id_entite = e.id_entite
    WHERE (v.id_entite IS NULL OR e.nom_entite IS NOT NULL)
      AND e.nom_entite != ''
");
$output['vehicule_entites'] = mysqli_fetch_all($q, MYSQLI_ASSOC);

// Summary
$output['summary'] = [
    'vehicules' => count($output['vehicules']),
    'chauffeurs' => count($output['chauffeurs']),
    'affectations' => count($output['affectations']),
    'exported_at' => date('Y-m-d H:i:s'),
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
