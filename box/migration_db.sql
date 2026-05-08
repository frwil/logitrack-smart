-- ============================================================================
-- LogiTrack DB Migration: Améliorations structurelles
-- Date: 2026-05-08
-- Compatible: MariaDB 10.11+
-- ============================================================================
-- PRÉREQUIS: Faire un backup complet avant d'exécuter ce script
--   mysqldump -h mysql-responsablelogistiquenjs.alwaysdata.net \
--     -u 390253 -p responsablelogistiquenjs_logistiquenjs > backup_$(date +%Y%m%d).sql
-- ============================================================================

START TRANSACTION;

-- ============================================================================
-- ÉTAPE 1: Ajouts sans impact (colonnes NULLABLE + nouvelles tables)
-- Ces opérations sont sûres et n'affectent pas les données existantes
-- ============================================================================

SELECT 'Étape 1: Ajout des colonnes et nouvelles tables...' AS Status;

-- 1a. Enrichissement de la table chauffeur
ALTER TABLE chauffeur
  ADD COLUMN telephone_chauffeur VARCHAR(30) NULL AFTER nom_chauffeur,
  ADD COLUMN no_permis_chauffeur VARCHAR(50) NULL AFTER telephone_chauffeur,
  ADD COLUMN date_expiration_permis DATE NULL AFTER no_permis_chauffeur,
  ADD COLUMN no_cni_chauffeur VARCHAR(50) NULL AFTER date_expiration_permis,
  ADD COLUMN date_expiration_cni DATE NULL AFTER no_cni_chauffeur,
  ADD COLUMN statut_chauffeur ENUM('Actif','Inactif','Suspendu') NOT NULL DEFAULT 'Actif' AFTER date_expiration_cni,
  ADD COLUMN id_entite INT UNSIGNED NULL AFTER statut_chauffeur,
  ADD FOREIGN KEY fk_chauffeur_entite (id_entite) REFERENCES entite(id_entite) ON DELETE SET NULL ON UPDATE CASCADE;

SELECT '  -> Table chauffeur enrichie (7 colonnes ajoutées)' AS Status;

-- 1b. Table M2M pour les multi-permis des chauffeurs
-- Remplace la relation 1:1 actuelle id_type_permis dans chauffeur
CREATE TABLE IF NOT EXISTS chauffeur_permis (
  id_chauffeur_permis INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_chauffeur INT UNSIGNED NOT NULL,
  id_type_permis INT UNSIGNED NOT NULL,
  is_principal TINYINT(1) NOT NULL DEFAULT 0,
  date_obtention DATE NULL,
  date_expiration DATE NULL,
  FOREIGN KEY fk_cp_chauffeur (id_chauffeur) REFERENCES chauffeur(id_chauffeur) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY fk_cp_permis (id_type_permis) REFERENCES type_permis_vehicule(id_type_permis) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uk_chauffeur_permis (id_chauffeur, id_type_permis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '  -> Table chauffeur_permis créée' AS Status;

-- 1c. Ajout chauffeur assistant + statut dans affectation_vehicule
ALTER TABLE affectation_vehicule
  ADD COLUMN id_chauffeur_assistant INT UNSIGNED NULL AFTER id_chauffeur,
  ADD COLUMN statut_affectation ENUM('En cours','Terminé','Planifié') NOT NULL DEFAULT 'En cours' AFTER is_ferme,
  ADD FOREIGN KEY fk_affectation_assistant (id_chauffeur_assistant) REFERENCES chauffeur(id_chauffeur) ON DELETE SET NULL ON UPDATE CASCADE;

SELECT '  -> Table affectation_vehicule enrichie (assistant + statut)' AS Status;

-- 1d. Ajout statut, observation, année dans vehicule
ALTER TABLE vehicule
  ADD COLUMN statut_vehicule ENUM('FONCTIONNEL','EN PANNE','EN RÉPARATION','REFORMÉ') NOT NULL DEFAULT 'FONCTIONNEL' AFTER capacite_consommation_vehicule,
  ADD COLUMN observation_vehicule TEXT NULL AFTER statut_vehicule,
  ADD COLUMN annee_vehicule SMALLINT UNSIGNED NULL AFTER observation_vehicule;

SELECT '  -> Table vehicule enrichie (statut + observation + année)' AS Status;

-- 1e. Table de liaison User <-> Entité
-- Un utilisateur peut gérer plusieurs entités (ex: SPC + AGROCAM)
CREATE TABLE IF NOT EXISTS users_entite (
  id_user_entite INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_user INT UNSIGNED NOT NULL,
  id_entite INT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY fk_ue_user (id_user) REFERENCES users(id_user) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY fk_ue_entite (id_entite) REFERENCES entite(id_entite) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uk_user_entite (id_user, id_entite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '  -> Table users_entite créée' AS Status;

-- ============================================================================
-- ÉTAPE 2: Migration des données existantes
-- Déplacer les données vers la nouvelle structure sans perte
-- ============================================================================

SELECT 'Étape 2: Migration des données...' AS Status;

-- 2a. Migrer id_type_permis de chauffeur vers chauffeur_permis
-- Chaque permis existant devient le permis principal du chauffeur
INSERT INTO chauffeur_permis (id_chauffeur, id_type_permis, is_principal)
SELECT c.id_chauffeur, c.id_type_permis, 1
FROM chauffeur c
WHERE c.id_type_permis IS NOT NULL AND c.id_type_permis > 0
  AND NOT EXISTS (
    SELECT 1 FROM chauffeur_permis cp
    WHERE cp.id_chauffeur = c.id_chauffeur AND cp.id_type_permis = c.id_type_permis
  );

SELECT CONCAT('  -> ', ROW_COUNT(), ' permis migrés vers chauffeur_permis') AS Status;

-- 2b. Peupler users_entite à partir des affectations existantes
-- Si un utilisateur gère des véhicules d'une entité, on crée le lien
-- Note: cette migration est partielle car la relation user-entité n'existe pas encore.
-- Chaque utilisateur existant sera lié aux entités pour lesquelles il a des droits.
INSERT INTO users_entite (id_user, id_entite, is_active)
SELECT DISTINCT u.id_user, e.id_entite, 1
FROM users u
CROSS JOIN entite e
WHERE e.nom_entite != '' AND e.nom_entite IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM users_entite ue
    WHERE ue.id_user = u.id_user AND ue.id_entite = e.id_entite
  );

SELECT CONCAT('  -> ', ROW_COUNT(), ' liens users_entite créés') AS Status;

-- 2c. Mettre à jour les statuts d'affectation: si is_ferme=1 alors 'Terminé', sinon 'En cours'
UPDATE affectation_vehicule SET statut_affectation = 'Terminé' WHERE is_ferme = 1;
UPDATE affectation_vehicule SET statut_affectation = 'En cours' WHERE is_ferme = 0 AND statut_affectation = 'En cours';

SELECT CONCAT('  -> ', ROW_COUNT(), ' statuts d''affectation mis à jour') AS Status;

-- 2d. Extraire l'année de première_utilisation pour annee_vehicule
UPDATE vehicule SET annee_vehicule = YEAR(premiere_utilisation)
WHERE premiere_utilisation IS NOT NULL AND annee_vehicule IS NULL;

SELECT CONCAT('  -> ', ROW_COUNT(), ' années véhicule déduites') AS Status;

-- 2e. Synchroniser users_region depuis users.users_region (valeurs CSV)
-- On insère dans users_region ce qui existe en CSV mais pas encore dans la table normalisée
-- Note: dépend du format actuel "id_region,id_region,..." ou "nom_region,nom_region,..."
-- Cette étape nécessite une vérification manuelle du format de users.users_region
SELECT '  -> ATTENTION: Vérifier le format de users.users_region avant migration CSV -> users_region' AS Status;

-- ============================================================================
-- ÉTAPE 3: Nettoyage et optimisation
-- ============================================================================

SELECT 'Étape 3: Nettoyage...' AS Status;

-- 3a. Supprimer l'index unique sur nom_chauffeur (les homonymes sont possibles)
-- On le remplace par un index non-unique
ALTER TABLE chauffeur DROP INDEX nom_chauffeur;
ALTER TABLE chauffeur ADD INDEX idx_nom_chauffeur (nom_chauffeur);

SELECT '  -> Index unique nom_chauffeur remplacé par index simple' AS Status;

-- 3b. Ajouter index manquants pour les performances
ALTER TABLE affectation_vehicule ADD INDEX idx_date_debut (date_debut_affectation);
ALTER TABLE affectation_vehicule ADD INDEX idx_is_ferme_statut (is_ferme, statut_affectation);
ALTER TABLE releve_kms_vehicule ADD INDEX idx_date_releve (date_releve);
ALTER TABLE voyage ADD INDEX idx_date_voyage (date_voyage);

SELECT '  -> Index de performance ajoutés' AS Status;

-- ============================================================================
-- ÉTAPE 4: Conversion charset latin1 -> utf8mb4
-- ============================================================================
-- ATTENTION: Cette étape est à exécuter avec précaution.
-- Si des erreurs surviennent (doublons de clés après conversion), traiter table par table.
--
-- Pour convertir avec vérification:
--   1. Faire le backup
--   2. Exécuter les ALTER TABLE ci-dessous sur une copie de test d'abord
--   3. Vérifier que les accents sont correctement conservés
-- ============================================================================

SELECT 'Étape 4: Conversion charset (à exécuter table par table)...' AS Status;

-- Les tables principales à convertir (décommenter pour exécuter):
-- ALTER TABLE vehicule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE chauffeur CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE chauffeur_permis CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE affectation_vehicule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE region CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE entite CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE users_region CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE users_entite CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE users_rights CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE voyage CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE marque_vehicule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE modele_vehicule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE type_permis_vehicule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE prestataire_intervention CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE bons_reparation CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE vidange_vehicule CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ALTER TABLE destination_voyage CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT '  -> Instructions de conversion charset prêtes (décommenter pour exécuter)' AS Status;

-- ============================================================================
-- ÉTAPE 5: Optionnel - Nettoyage ultérieur (après validation)
-- ============================================================================

SELECT 'Étape 5: Instructions pour nettoyage ultérieur...' AS Status;

-- 5a. Après avoir vérifié que users_region contient toutes les données de users.users_region:
-- ALTER TABLE users DROP COLUMN users_region;

-- 5b. Renommer l'index unique sur releve_kms_vehicule si gênant:
-- ALTER TABLE releve_kms_vehicule DROP INDEX id_vehicule;
-- ALTER TABLE releve_kms_vehicule ADD UNIQUE INDEX uk_affectation_periode_semaine (id_affectation_vehicule, periode_releve, semaine_annee);

SELECT '  -> Instructions de nettoyage post-validation prêtes' AS Status;

-- ============================================================================
-- VÉRIFICATION FINALE
-- ============================================================================

SELECT '========================================' AS '';
SELECT 'Vérification de la migration' AS Status;
SELECT '========================================' AS '';

-- Check: nombre de chauffeurs avec permis migrés
SELECT CONCAT('Chauffeurs avec permis: ',
  (SELECT COUNT(DISTINCT id_chauffeur) FROM chauffeur_permis), ' / ',
  (SELECT COUNT(*) FROM chauffeur WHERE id_type_permis > 0)
) AS Verification;

-- Check: nouvelles colonnes existent bien
SELECT COLUMN_NAME, COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'responsablelogistiquenjs_logistiquenjs'
  AND TABLE_NAME = 'chauffeur'
  AND COLUMN_NAME IN ('telephone_chauffeur', 'no_permis_chauffeur', 'date_expiration_permis',
                       'no_cni_chauffeur', 'date_expiration_cni', 'statut_chauffeur', 'id_entite');

-- Check: nouvelles tables
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'responsablelogistiquenjs_logistiquenjs'
  AND TABLE_NAME IN ('chauffeur_permis', 'users_entite');

COMMIT;

SELECT '========================================' AS '';
SELECT 'Migration terminée avec succès.' AS Status;
SELECT 'Pensez à exécuter la conversion charset utf8mb4 après validation.' AS Rappel;
SELECT '========================================' AS '';
