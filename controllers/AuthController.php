<?php
/**
 * Auth controller — login/logout/session management.
 */
class AuthController extends BaseController
{
    private UserRepository $userRepo;
    private RegionRepository $regionRepo;
    private EntiteRepository $entiteRepo;

    public function __construct(UserRepository $userRepo, RegionRepository $regionRepo, EntiteRepository $entiteRepo)
    {
        $this->userRepo = $userRepo;
        $this->regionRepo = $regionRepo;
        $this->entiteRepo = $entiteRepo;
    }

    /**
     * Étape 1 de la connexion : identifiant + mot de passe uniquement.
     * En cas de succès, prépare le choix du contexte (écran 2) et renvoie
     * les régions/entités auxquelles l'utilisateur authentifié a accès.
     */
    public function login(): never
    {
        $nameUser = $this->post('name-user');
        $passUser = $this->post('pass-user');

        if (!$nameUser || !$passUser) {
            $this->jsonError('Identifiant et mot de passe sont obligatoires');
        }

        $user = $this->userRepo->findByName($nameUser);
        if (!$user || !password_verify($passUser, $user['pass_user'])) {
            $this->jsonError('Nom d\'utilisateur ou mot de passe incorrect', 401);
        }

        // Check if the account is active (admin may have deactivated it).
        if (empty($user['is_active'])) {
            $this->jsonError('Compte désactivé. Veuillez contacter l\'administrateur.', 403);
        }

        $role = $user['role'] ?? 'user';
        $isAdmin = $role === 'admin' || $role === 'superadmin';
        $isSuperadmin = $role === 'superadmin';

        // Régions autorisées : superadmin → tout ; sinon users_region du compte.
        $regions = [];
        if ($isSuperadmin) {
            $regions = $this->regionRepo->findAll();
        } else {
            $userRegions = array_filter(array_map('intval', explode(',', $user['users_region'])));
            foreach ($userRegions as $rid) {
                $reg = $this->regionRepo->findById($rid);
                if ($reg) $regions[] = $reg;
            }
        }

        // Entités autorisées : admin/superadmin → tout ; sinon users_entite du compte.
        if ($isAdmin) {
            $entites = $this->entiteRepo->findAll();
        } else {
            $entites = $this->entiteRepo->findByUser((int)$user['id_user']);
        }

        unset($user['pass_user']);
        // Connexion en attente du choix du contexte (écran 2), protégée par un délai.
        $_SESSION['pending-login'] = [
            'user'          => $user,
            'is-admin'      => $isAdmin,
            'is-superadmin' => $isSuperadmin,
            'users-entite'  => array_map('intval', array_column($entites, 'id_entite')),
            'regions'       => array_values($regions),
            'entites'       => array_values($entites),
            'ts'            => time(),
        ];
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->json([
            'step'         => 2,
            'csrf_token'   => $_SESSION['csrf_token'],
            'display_name' => trim($user['fullname_user'] ?? '') !== '' ? $user['fullname_user'] : $nameUser,
            'regions'      => array_map(fn($r) => ['id' => (int)$r['id_region'], 'nom' => $r['nom_region']], $regions),
            'entites'      => array_map(fn($e) => ['id' => (int)$e['id_entite'], 'nom' => $e['nom_entite']], $entites),
        ]);
    }

    /**
     * Étape 2 de la connexion : choix des régions/entités après authentification.
     * Finalise la session usr-con avec des sélections validées contre les droits
     * de l'utilisateur authentifié (stockés dans pending-login).
     */
    public function loginContext(): never
    {
        $pending = $_SESSION['pending-login'] ?? null;
        if (!$pending || time() - (int)$pending['ts'] > 900) {
            unset($_SESSION['pending-login']);
            $this->jsonError('Connexion expirée, veuillez vous identifier à nouveau', 401);
        }

        $user = $pending['user'];
        $isAdmin = !empty($pending['is-admin']);
        $isSuperadmin = !empty($pending['is-superadmin']);

        $regionIds = $this->post('region-user');
        $entiteIds = $this->post('entite-user');

        if (!$regionIds) {
            $this->jsonError('Tous les champs sont obligatoires');
        }

        if (!is_array($regionIds)) $regionIds = [$regionIds];
        if (!is_array($entiteIds)) $entiteIds = [$entiteIds];

        // Régions : superadmin → toutes ; sinon validées contre users_region.
        $userRegions = explode(',', $user['users_region']);
        $regionNames = [];
        if ($isSuperadmin) {
            $allRegions = $this->regionRepo->findAll();
            $regionIds = array_map('intval', array_column($allRegions, 'id_region'));
            $regionNames = array_column($allRegions, 'nom_region');
        } else {
            foreach ($regionIds as $rid) {
                if (!in_array((string)$rid, $userRegions)) {
                    $this->jsonError('Région non autorisée', 403);
                }
                $reg = $this->regionRepo->findById((int)$rid);
                if ($reg) $regionNames[] = $reg['nom_region'];
            }
        }

        // Entités : admin/superadmin → toutes (comme l'ancien login) ; sinon validées.
        $entiteNames = [];
        if ($isAdmin) {
            $allEntites = $this->entiteRepo->findAll();
            $userEntityIds = array_map('intval', array_column($allEntites, 'id_entite'));
            $entiteIds = $userEntityIds;
            $entiteNames = array_column($allEntites, 'nom_entite');
        } else {
            $userEntityIds = $pending['users-entite'];
            foreach ($entiteIds as $eid) {
                if (!in_array((int)$eid, $userEntityIds)) {
                    $this->jsonError('Entité non autorisée', 403);
                }
                $ent = $this->entiteRepo->findById((int)$eid);
                if ($ent) $entiteNames[] = $ent['nom_entite'];
            }
        }

        $user['region-sel'] = array_map('intval', $regionIds);
        $user['region-sel-names'] = $regionNames;
        $user['is-admin'] = $isAdmin;
        $user['is-superadmin'] = $isSuperadmin;
        $user['entite-sel'] = array_map('intval', $entiteIds);
        $user['entite-sel-names'] = $entiteNames;
        if ($isSuperadmin) {
            // Superadmin : TOUS les droits du catalogue (source unique de vérité).
            $catalog = RightsCatalog::rightsMap();
            $user['users-rights'] = [];
            foreach ($catalog as $objet => $valeur) {
                $user['users-rights'][] = ['users_rights_objet' => $objet, 'users_rights_valeur' => $valeur];
            }
            $this->userRepo->replaceUserRights((int)$user['id_user'], $catalog);
        } else {
            $user['users-rights'] = $this->userRepo->findRights((int)$user['id_user']);
        }
        $user['users-entite'] = array_map('intval', $userEntityIds);

        $_SESSION['usr-con'] = $user;
        $_SESSION['usr-con']['last_activity'] = time();
        unset($_SESSION['pending-login']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->json();
    }

    /** Handle context switch POST (JSON). Returns JSON. */
    public function switchContext(): never
    {
        // Reuse $jsonPost decoded in index.php — php://input is single-read.
        global $jsonPost;
        $regionIds = $jsonPost['regionIds'] ?? $_POST['regionIds'] ?? null;
        $entiteIds = $jsonPost['entiteIds'] ?? $_POST['entiteIds'] ?? null;

        if (!$regionIds || !$entiteIds) {
            $this->jsonError('Régions et entités requises');
        }

        $isSuperadmin = ($_SESSION['usr-con']['is-superadmin'] ?? false) === true;

        // Validate regions
        $userRegions = explode(',', $_SESSION['usr-con']['users_region']);
        $regionNames = [];
        if ($isSuperadmin) {
            foreach ($regionIds as $rid) {
                $reg = $this->regionRepo->findById((int)$rid);
                if ($reg) $regionNames[] = $reg['nom_region'];
            }
        } else {
            foreach ($regionIds as $rid) {
                if (!in_array((string)$rid, $userRegions)) {
                    $this->jsonError('Accès non autorisé à cette région', 403);
                }
                $reg = $this->regionRepo->findById((int)$rid);
                if ($reg) $regionNames[] = $reg['nom_region'];
            }
        }

        // Validate entities
        $userEntityIds = $_SESSION['usr-con']['users-entite'] ?? [];
        $entiteNames = [];
        if ($isSuperadmin) {
            foreach ($entiteIds as $eid) {
                $ent = $this->entiteRepo->findById((int)$eid);
                if ($ent) $entiteNames[] = $ent['nom_entite'];
            }
        } else {
            foreach ($entiteIds as $eid) {
                if (!in_array((int)$eid, $userEntityIds)) {
                    $this->jsonError('Accès non autorisé à cette entité', 403);
                }
                $ent = $this->entiteRepo->findById((int)$eid);
                if ($ent) $entiteNames[] = $ent['nom_entite'];
            }
        }

        $_SESSION['usr-con']['region-sel'] = array_map('intval', $regionIds);
        $_SESSION['usr-con']['region-sel-names'] = $regionNames;
        $_SESSION['usr-con']['entite-sel'] = array_map('intval', $entiteIds);
        $_SESSION['usr-con']['entite-sel-names'] = $entiteNames;

        $this->json();
    }
}
