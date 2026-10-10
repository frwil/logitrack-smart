<?php

/**
 * Catalogue unique des droits d'accès de l'application.
 *
 * Source de vérité partagée par :
 *  - la grille des droits (_users.php),
 *  - la liste complète accordée au superadmin au login (AuthController),
 *  - l'attribution automatique à l'utilisateur admin (synchronisée au login).
 *
 * Ajouter un droit ici suffit à le rendre disponible partout
 * (grille + superadmin), aucune autre modification n'est nécessaire.
 */
class RightsCatalog
{
    /** @var string[] Droits de base communs à tous les modules. */
    public const BASE_RIGHTS = ['view', 'save', 'upd', 'del'];

    /**
     * Modules => [label, sous-lignes, codes supplémentaires non affichés dans la grille].
     *
     * Une sous-ligne = [libellé, caseVoir, caseAjouter, caseModifier, caseSupprimer]
     * (une case vide ne produit pas de case à cocher).
     */
    public static function modules(): array
    {
        return [
            'vehicules' => [
                'label'  => 'Véhicules',
                'subs'   => [],
                'extras' => ['print'],
            ],
            'voyages' => [
                'label'  => 'Voyages',
                'subs'   => [
                    ['Rapports', 'report', '', '', ''],
                    ['Trajets', 'viewtrajet', 'savetrajet', 'updtrajet', 'deltrajet'],
                    ['Prestataires de transport', 'viewPrestataireTransport', 'savePrestataireTransport', 'updPrestataireTransport', 'delPrestataireTransport'],
                ],
                'extras' => [],
            ],
            'affectationVehicules' => [
                'label'  => 'Affectations',
                'subs'   => [],
                'extras' => ['print'],
            ],
            'maintenances' => [
                'label'  => 'Maintenance',
                'subs'   => [
                    ['Relevés kilométriques', 'viewReleveKms', 'saveReleveKms', '', ''],
                    ['Suivi vidanges', 'viewVidange', 'saveVidange', 'updVidange', 'delVidange'],
                    ['Prestataires', 'viewPrestataire', 'savePrestataire', 'updPrestataire', 'delPrestataire'],
                    ['Centre de coûts', 'viewCentreCout', 'saveCentreCout', 'updCentreCout', 'delCentreCout'],
                    ['Bons de réparation', 'viewBonsReparation', 'saveBonsReparation', 'updBonsReparation', 'delBonsReparation'],
                    ['Exercices budgétaires', 'viewExercice', 'saveExercice', 'updExercice', 'delExercice'],
                    ['Lignes budgétaires', 'viewLigneBudgetaire', 'saveLigneBudgetaire', 'updLigneBudgetaire', 'delLigneBudgetaire'],
                    ['Bons — Lier budget', 'linkBudget', '', '', ''],
                ],
                'extras' => ['print', 'historyVidange'],
            ],
            'users' => [
                'label'  => 'Utilisateurs',
                'subs'   => [],
                'extras' => [],
            ],
            'config' => [
                'label'  => 'Configuration',
                'subs'   => [
                    ['Sauvegarde DB', 'backup', '', '', ''],
                    ['Permis de conduire', 'viewPermis', 'savePermis', 'updPermis', 'delPermis'],
                    ['Documents', 'viewDocs', 'saveDocs', 'updDocs', 'delDocs'],
                    ['Dossiers véhicules', 'viewFolders', 'saveFolders', 'updFolders', 'delFolders'],
                ],
                'extras' => [],
            ],
            'report' => [
                'label'  => 'Rapports',
                'subs'   => [],
                'extras' => [],
            ],
        ];
    }

    /**
     * Tous les codes d'un module : droits de base + sous-codes + codes supplémentaires.
     */
    public static function codesFor(string $module): array
    {
        $def = self::modules()[$module] ?? null;
        if ($def === null) {
            return [];
        }
        $codes = self::BASE_RIGHTS;
        foreach ($def['subs'] as $sub) {
            for ($i = 1; $i < count($sub); $i++) {
                if ($sub[$i] !== '') {
                    $codes[] = $sub[$i];
                }
            }
        }
        return array_merge($codes, $def['extras']);
    }

    /**
     * CSV complet d'un module (format users_rights.users_rights_valeur).
     */
    public static function csvFor(string $module): string
    {
        return implode(',', self::codesFor($module));
    }

    /**
     * Carte module => CSV complet, prête pour UserRepository::replaceUserRights().
     */
    public static function rightsMap(): array
    {
        $map = [];
        foreach (array_keys(self::modules()) as $module) {
            $map[$module] = self::csvFor($module);
        }
        return $map;
    }
}
