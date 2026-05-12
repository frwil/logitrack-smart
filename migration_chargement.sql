-- Migration: Add unite_mesure to type_chargement_voyage + create capacite_vehicule_unite
ALTER TABLE type_chargement_voyage ADD COLUMN unite_mesure VARCHAR(50) NOT NULL DEFAULT '' AFTER lib_type_chargement;

CREATE TABLE IF NOT EXISTS capacite_vehicule_unite (
    id_capacite_vehicule_unite INT AUTO_INCREMENT PRIMARY KEY,
    id_vehicule INT NOT NULL,
    unite_mesure VARCHAR(50) NOT NULL,
    capacite_max DECIMAL(10,2) NOT NULL,
    UNIQUE KEY uq_vehicule_unite (id_vehicule, unite_mesure),
    FOREIGN KEY (id_vehicule) REFERENCES vehicule(id_vehicule) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
