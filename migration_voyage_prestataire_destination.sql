-- Migration : destinations des voyages prestataires externes
-- A appliquer APRÈS migration_prestataire_transport.sql (table voyage_prestataire requise)
-- Sauvegarder la base avant application.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS voyage_prestataire_destination (
    id_voyage_prestataire_destination INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    id_voyage_prestataire INT(10) UNSIGNED NOT NULL,
    id_destination INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (id_voyage_prestataire_destination),
    KEY idx_vpd_voyage (id_voyage_prestataire),
    KEY idx_vpd_destination (id_destination),
    CONSTRAINT vpd_fk_voyage FOREIGN KEY (id_voyage_prestataire) REFERENCES voyage_prestataire (id_voyage_prestataire) ON DELETE CASCADE,
    CONSTRAINT vpd_fk_destination FOREIGN KEY (id_destination) REFERENCES destination_voyage (id_destination)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

COMMIT;
