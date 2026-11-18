-- Migration : le principe de carburation appliqué à un voyage est figé à sa création.
-- Le paramètre global (parametres.principe-carburation) ne s'applique qu'aux NOUVEAUX voyages :
-- un voyage déjà effectué garde son principe (et ses marquages plein) même si la
-- configuration globale change ensuite.
-- Valeurs : 'plein' (chaque carburation fait le plein), 'partiel' (jamais), 'mixte' (libre).
-- Les voyages existants passent à 'mixte' : leurs marquages plein stockés sont conservés
-- et restent modifiables à la modification du voyage.

-- Usage: mysql -h HOST -u USER -p DB_NAME < migration_voyage_principe_carburation.sql

ALTER TABLE voyage
    ADD COLUMN principe_carburation varchar(16) NOT NULL DEFAULT 'mixte'
    AFTER commentaire_voyage;
