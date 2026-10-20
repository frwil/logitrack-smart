-- Migration : table carburant_voyage — 1 voyage = N carburations.
-- Permet de saisir plusieurs carburations sur un même voyage (ex. complément à l'aller,
-- plein au retour) et de repérer les pleins complets (jauge estimée / méthode plein à plein).
-- voyage.qte_carburant reste le total du voyage, maintenu à la sauvegarde (somme des carburations).

CREATE TABLE IF NOT EXISTS carburant_voyage (
    id_carburant int(10) unsigned NOT NULL AUTO_INCREMENT,
    id_voyage int(10) unsigned NOT NULL,
    qte_carburant bigint(20) unsigned NOT NULL,
    plein_complet tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id_carburant),
    KEY id_voyage (id_voyage),
    CONSTRAINT carburant_voyage_ibfk_1 FOREIGN KEY (id_voyage) REFERENCES voyage (id_voyage) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Reprise de l'existant : une carburation par voyage dont le carburant est renseigné.
INSERT INTO carburant_voyage (id_voyage, qte_carburant, plein_complet)
SELECT id_voyage, qte_carburant, 0
FROM voyage
WHERE qte_carburant > 0;
