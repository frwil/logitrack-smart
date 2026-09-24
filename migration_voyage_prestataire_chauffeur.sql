-- Migration : chauffeur saisi par voyage prestataire externe
-- A appliquer APRÈS migration_prestataire_transport.sql (table voyage_prestataire requise)
-- Ne pas exécuter deux fois. Sauvegarder la base avant application.

START TRANSACTION;

ALTER TABLE voyage_prestataire
    ADD COLUMN nom_chauffeur VARCHAR(255) DEFAULT NULL AFTER id_prestataire_transport;

COMMIT;
