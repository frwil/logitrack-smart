-- Migration : immatriculation saisie par voyage prestataire externe
-- À appliquer APRÈS migration_voyage_prestataire_chauffeur.sql (table voyage_prestataire requise)
-- Ne pas exécuter deux fois. Sauvegarder la base avant application.

START TRANSACTION;

-- L'immatriculation est désormais saisie au niveau de chaque voyage :
-- le véhicule du prestataire n'étant pas maîtrisé, elle n'est plus un
-- attribut du prestataire lui-même.
ALTER TABLE voyage_prestataire
    ADD COLUMN immatriculation VARCHAR(50) DEFAULT NULL AFTER id_prestataire_transport;

-- La colonne immatriculation du prestataire est conservée pour l'historique
-- mais devient facultative, et la contrainte d'unicité qui en faisait
-- l'identifiant du prestataire est retirée.
ALTER TABLE prestataire_transport
    MODIFY immatriculation VARCHAR(50) DEFAULT NULL,
    DROP INDEX uq_pt_immat;

-- Le nom de la société devient l'identifiant unique du prestataire.
-- La collation latin1_*_ci rend la comparaison insensible à la casse.
-- Si des doublons (casse, espaces) existent déjà, les supprimer AVANT
-- d'exécuter l'instruction suivante, sinon l'ALTER échoue :
--
--   DELETE p2 FROM prestataire_transport p1
--   JOIN prestataire_transport p2
--     ON p2.id_prestataire_transport > p1.id_prestataire_transport
--    AND LOWER(TRIM(p2.nom_societe)) = LOWER(TRIM(p1.nom_societe));
ALTER TABLE prestataire_transport
    ADD UNIQUE KEY uq_pt_societe (nom_societe);

COMMIT;
