-- ============================================================================
-- MIGRATION: Refonte bons de réparation + module budget
--  - centre_couts.montant_budget
--  - table exercice_budgetaire (exercices budgétaires)
--  - bons_reparation : montant_paye, duree_prevue, id_ligne_budgetaire ;
--    plus/moins et centre de coût deviennent NULL (legacy conservé)
--  - table ligne_budgetaire (libellé + centre de coût + exercice)
-- ============================================================================

SET NAMES utf8mb4;

START TRANSACTION;

SELECT 'Migration: refonte bons de réparation + budget...' AS Status;

-- 1. Montant budget sur les centres de coûts
ALTER TABLE centre_couts
  ADD COLUMN montant_budget bigint(20) unsigned NOT NULL DEFAULT 0 AFTER lib_centre_cout;
SELECT '  -> centre_couts.montant_budget ajouté' AS Status;

-- 2. Exercices budgétaires
CREATE TABLE IF NOT EXISTS exercice_budgetaire (
    id_exercice_budgetaire int(10) unsigned NOT NULL AUTO_INCREMENT,
    lib_exercice_budgetaire varchar(255) NOT NULL,
    date_debut_exercice date NOT NULL,
    date_fin_exercice date NOT NULL,
    statut_exercice enum('Ouvert','Clôturé') NOT NULL DEFAULT 'Ouvert',
    PRIMARY KEY (id_exercice_budgetaire),
    UNIQUE KEY lib_exercice_budgetaire (lib_exercice_budgetaire)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SELECT '  -> table exercice_budgetaire créée' AS Status;

-- 3. Colonnes plus/moins → NULL (l''app écrit NULL désormais)
ALTER TABLE bons_reparation DROP FOREIGN KEY bons_reparation_ibfk_2;
ALTER TABLE bons_reparation
  MODIFY COLUMN id_plus_ou_moins_value int(10) unsigned NULL DEFAULT NULL,
  MODIFY COLUMN plus_ou_moins_value_valeur bigint(20) unsigned NULL DEFAULT NULL;
SELECT '  -> plus/moins rendus nullables (FK supprimée)' AS Status;

-- 4. Montant payé (NULL = pas encore payé) + durée prévue
ALTER TABLE bons_reparation
  ADD COLUMN montant_paye bigint(20) unsigned NULL DEFAULT NULL AFTER montant_reparation,
  ADD COLUMN duree_prevue int(10) unsigned NOT NULL DEFAULT 0 AFTER duree_reparation;
SELECT '  -> montant_paye et duree_prevue ajoutés' AS Status;

-- 5. Centre de coûts retiré des bons : FK CASCADE supprimée (supprimer un centre
--    de coûts ne supprime plus des bons) ; colonne conservée en héritage, NULL pour les nouveaux
ALTER TABLE bons_reparation DROP FOREIGN KEY bons_reparation_ibfk_1;
ALTER TABLE bons_reparation
  MODIFY COLUMN id_centre_cout int(10) unsigned NULL DEFAULT NULL;
SELECT '  -> id_centre_cout rendu nullable (FK CASCADE supprimée)' AS Status;

-- 6. Lignes budgétaires (pas de montant propre — le budget vit sur centre_couts)
CREATE TABLE IF NOT EXISTS ligne_budgetaire (
    id_ligne_budgetaire int(10) unsigned NOT NULL AUTO_INCREMENT,
    lib_ligne_budgetaire varchar(255) NOT NULL,
    id_centre_cout int(10) unsigned NOT NULL,
    id_exercice_budgetaire int(10) unsigned NOT NULL,
    PRIMARY KEY (id_ligne_budgetaire),
    UNIQUE KEY uk_lb (lib_ligne_budgetaire, id_centre_cout, id_exercice_budgetaire),
    KEY idx_lb_centre (id_centre_cout),
    KEY idx_lb_exercice (id_exercice_budgetaire),
    CONSTRAINT lb_fk_centre FOREIGN KEY (id_centre_cout)
      REFERENCES centre_couts (id_centre_cout) ON DELETE CASCADE,
    CONSTRAINT lb_fk_exercice FOREIGN KEY (id_exercice_budgetaire)
      REFERENCES exercice_budgetaire (id_exercice_budgetaire) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
SELECT '  -> table ligne_budgetaire créée' AS Status;

-- 7. Lien bon ↔ ligne (SET NULL : supprimer une ligne ne supprime pas les bons)
ALTER TABLE bons_reparation
  ADD COLUMN id_ligne_budgetaire int(10) unsigned NULL DEFAULT NULL AFTER id_centre_cout,
  ADD KEY idx_br_ligne_budgetaire (id_ligne_budgetaire),
  ADD CONSTRAINT br_fk_ligne_budgetaire FOREIGN KEY (id_ligne_budgetaire)
    REFERENCES ligne_budgetaire (id_ligne_budgetaire) ON DELETE SET NULL;
SELECT '  -> bons_reparation.id_ligne_budgetaire ajouté' AS Status;

-- 8. Recalcul des durées des lignes existantes
UPDATE bons_reparation SET
  duree_reparation = CASE
      WHEN date_entree <> '0000-00-00' AND date_fin_reparation <> '0000-00-00'
      THEN GREATEST(DATEDIFF(date_fin_reparation, date_entree), 0) ELSE 0 END,
  duree_prevue = CASE
      WHEN date_entree <> '0000-00-00' AND date_prevue_sortie <> '0000-00-00'
      THEN GREATEST(DATEDIFF(date_prevue_sortie, date_entree), 0) ELSE 0 END;
SELECT '  -> durées recalculées' AS Status;

-- 9. Neutralisation du legacy plus/moins
UPDATE bons_reparation SET id_plus_ou_moins_value = NULL, plus_ou_moins_value_valeur = NULL;
SELECT '  -> plus/moins legacy neutralisé' AS Status;

COMMIT;
