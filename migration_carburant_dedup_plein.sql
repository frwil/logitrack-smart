-- Migration : nettoyage carburant_voyage + principe du plein
--
-- Contexte :
-- 1) La migration de reprise (migration_carburant_voyage.sql) a été exécutée deux fois :
--    2 381 voyages ont reçu 2 événements identiques = voyage.qte_carburant
--    (signature : événements identiques, somme > total du voyage). On déduplique :
--    un seul événement par voyage, le total du voyage fait foi.
-- 2) Principe du plein : la dernière carburation (qte > 0) de chaque voyage fait le
--    plein du réservoir et clôt la mesure plein à plein de la jauge virtuelle.
--    Les carburations à 0 (données aberrantes) ne sont jamais marquées « plein ».

-- Usage: mysql -h HOST -u USER -p DB_NAME < migration_carburant_dedup_plein.sql

-- Étape 1 — Déduplication des voyages dont tous les événements sont identiques
--          et dont la somme double le total du voyage.
DELETE cv FROM carburant_voyage cv
JOIN (
    SELECT v.id_voyage
    FROM voyage v
    JOIN carburant_voyage cv2 ON cv2.id_voyage = v.id_voyage
    GROUP BY v.id_voyage, v.qte_carburant
    HAVING COUNT(*) > 1
       AND SUM(cv2.qte_carburant) > v.qte_carburant
       AND MIN(cv2.qte_carburant) = MAX(cv2.qte_carburant)
) d ON d.id_voyage = cv.id_voyage;

-- Réinsérer un événement unique = total du voyage pour les voyages dédupliqués
-- (les voyages à un seul événement ou à événements authentiques ne sont pas touchés).
INSERT INTO carburant_voyage (id_voyage, qte_carburant, plein_complet)
SELECT v.id_voyage, v.qte_carburant, 0
FROM voyage v
WHERE v.qte_carburant > 0
  AND NOT EXISTS (SELECT 1 FROM carburant_voyage cv WHERE cv.id_voyage = v.id_voyage);

-- Étape 2 — Principe du plein : la dernière carburation (qte > 0) de chaque voyage
--          est le plein complet qui clôt la mesure plein à plein.
UPDATE carburant_voyage SET plein_complet = 0;

UPDATE carburant_voyage cv
JOIN (
    SELECT id_voyage, MAX(id_carburant) AS last_id
    FROM carburant_voyage
    WHERE qte_carburant > 0
    GROUP BY id_voyage
) t ON t.last_id = cv.id_carburant
SET cv.plein_complet = 1;
