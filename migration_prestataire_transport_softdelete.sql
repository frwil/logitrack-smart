-- Migration : modification et soft-delete des prestataires de transport
-- À appliquer APRÈS migration_voyage_prestataire_immatriculation.sql.
-- Ne pas exécuter deux fois. Sauvegarder la base avant application.

START TRANSACTION;

-- Le soft-delete (is_deleted = 1) rend l'index unique sur nom_societe
-- inadapté : un prestataire supprimé resterait en base et bloquerait la
-- ré-création d'un prestataire portant le même nom.
-- L'unicité du nom est désormais vérifiée par l'application sur les
-- prestataires actifs uniquement (comparaison insensible à la casse,
-- aux accents et aux espaces multiples).
-- L'instruction est conditionnelle : si la migration précédente n'a pas
-- été appliquée (ou l'a déjà été avec suppression de l'index), elle est ignorée.
SET @idx_exists = (
    SELECT COUNT(*) FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'prestataire_transport'
      AND index_name = 'uq_pt_societe'
);
SET @sql = IF(@idx_exists > 0, 'ALTER TABLE prestataire_transport DROP INDEX uq_pt_societe', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- La colonne is_deleted existe déjà (migration_prestataire_transport.sql) :
-- le soft-delete passe simplement par UPDATE prestataire_transport SET is_deleted = 1.

COMMIT;
