-- Migration: Add id_entite to objectif_periode_region
-- Run this manually against the production database.
-- Usage: mysql -h HOST -u USER -p DB_NAME < migration_add_entite_objectif.sql

START TRANSACTION;

-- Step 1: Add id_entite column (nullable first for data migration)
ALTER TABLE objectif_periode_region
  ADD COLUMN id_entite INT UNSIGNED NULL AFTER id_region;

-- Step 2: Assign existing rows to the entity with the lowest ID
-- WARNING: Review this. If you have multiple entities and want different
-- assignments per region, update the WHERE clause accordingly.
UPDATE objectif_periode_region
SET id_entite = (SELECT MIN(id_entite) FROM entite)
WHERE id_entite IS NULL;

-- Step 3: Make NOT NULL
ALTER TABLE objectif_periode_region
  MODIFY COLUMN id_entite INT UNSIGNED NOT NULL;

-- Step 4: Add foreign key
ALTER TABLE objectif_periode_region
  ADD CONSTRAINT fk_opr_entite
  FOREIGN KEY (id_entite) REFERENCES entite(id_entite)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Step 5: Drop old unique constraint and add new one
-- The old constraint name is 'date_objectif_periode' (from schema dump)
ALTER TABLE objectif_periode_region
  DROP INDEX `date_objectif_periode`;

ALTER TABLE objectif_periode_region
  ADD UNIQUE KEY `uk_date_region_entite` (date_objectif_periode, id_region, id_entite);

COMMIT;
