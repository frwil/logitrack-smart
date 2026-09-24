-- ============================================================================
-- MIGRATION: Prestataires de transport externes (voyages)
-- Tables en latin1 pour compatibilité des FK avec entite/region/type_chargement_voyage.
-- Application manuelle (phpMyAdmin ou CLI mysql) — faire un backup avant.
-- ============================================================================
START TRANSACTION;

CREATE TABLE IF NOT EXISTS prestataire_transport (
    id_prestataire_transport INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    nom_societe              VARCHAR(255) NOT NULL,
    adresse_societe          VARCHAR(255) DEFAULT NULL,
    telephone_societe        VARCHAR(50)  DEFAULT NULL,
    immatriculation          VARCHAR(50)  NOT NULL,
    nom_chauffeur            VARCHAR(255) NOT NULL,
    nom_copilote             VARCHAR(255) DEFAULT NULL,
    capacite_transport       DECIMAL(10,2) NOT NULL DEFAULT 0,
    unite_mesure             VARCHAR(50)  NOT NULL DEFAULT '',
    is_deleted               TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id_prestataire_transport),
    UNIQUE KEY uq_pt_immat (immatriculation)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS voyage_prestataire (
    id_voyage_prestataire  INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    date_voyage            DATE NOT NULL,
    id_prestataire_transport INT(10) UNSIGNED NOT NULL,
    id_entite              INT(10) UNSIGNED NOT NULL,
    id_region              INT(10) UNSIGNED NOT NULL,
    convoyeur              VARCHAR(255) DEFAULT NULL,
    id_type_chargement     INT(10) UNSIGNED NOT NULL,
    qte_chargement         DECIMAL(10,2) NOT NULL DEFAULT 0,
    numero_scelle          VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id_voyage_prestataire),
    KEY idx_vp_prestataire (id_prestataire_transport),
    KEY idx_vp_entite (id_entite),
    KEY idx_vp_region (id_region),
    KEY idx_vp_type (id_type_chargement),
    KEY idx_vp_date (date_voyage),
    CONSTRAINT vp_fk_prestataire FOREIGN KEY (id_prestataire_transport) REFERENCES prestataire_transport (id_prestataire_transport) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT vp_fk_entite FOREIGN KEY (id_entite) REFERENCES entite (id_entite) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT vp_fk_region FOREIGN KEY (id_region) REFERENCES region (id_region) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT vp_fk_type FOREIGN KEY (id_type_chargement) REFERENCES type_chargement_voyage (id_type_chargement) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

COMMIT;
