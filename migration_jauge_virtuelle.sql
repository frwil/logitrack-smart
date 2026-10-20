-- Migration : jauge virtuelle carburant — capacité du réservoir par véhicule.
-- Permet d'estimer le niveau actuel du réservoir après un plein complet :
-- niveau = capacité + carburant ajouté depuis le dernier plein − consommation estimée.
-- 0 = non renseigné (jauge non calculable pour ce véhicule).

ALTER TABLE vehicule
    ADD COLUMN capacite_reservoir bigint(20) unsigned NOT NULL DEFAULT 0;
