-- ============================================================================
-- MIGRATION: Colonne numero_scelle dans voyage (optionnel, unique)
-- ============================================================================

START TRANSACTION;

SELECT 'Migration: numero_scelle dans voyage...' AS Status;

ALTER TABLE voyage ADD COLUMN numero_scelle VARCHAR(255) DEFAULT NULL;
ALTER TABLE voyage ADD UNIQUE INDEX idx_numero_scelle (numero_scelle);

SELECT '  -> Colonne numero_scelle ajoutée dans voyage' AS Status;

COMMIT;
