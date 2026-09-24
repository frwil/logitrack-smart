-- Migration : champs société pour les prestataires de transport externes
-- Uniquement si migration_prestataire_transport.sql a déjà été appliquée
-- (sinon la colonne nom_societe y est déjà incluse). Ne pas exécuter deux fois.
-- Sauvegarder la base avant application.

START TRANSACTION;

ALTER TABLE prestataire_transport
    ADD COLUMN nom_societe VARCHAR(255) NOT NULL DEFAULT '' AFTER id_prestataire_transport,
    ADD COLUMN adresse_societe VARCHAR(255) DEFAULT NULL AFTER nom_societe,
    ADD COLUMN telephone_societe VARCHAR(50) DEFAULT NULL AFTER adresse_societe;

COMMIT;
