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

    /** Handle login POST. Accepts multi-select regions and entities. Returns JSON. */
    public function login(): never
    {
        $nameUser = $this->post('name-user');
        $passUser = $this->post('pass-user');
        $regionIds = $this->post('region-user');
        $entiteIds = $this->post('entite-user');
        $role = $this->post('role-user');

        if (!$nameUser || !$entiteIds || !$regionIds || !$role) {
            $this->jsonError('Tous les champs sont obligatoires');
        }

        if (!is_array($regionIds)) $regionIds = [$regionIds];
        if (!is_array($entiteIds)) $entiteIds = [$entiteIds];

        $user = $this->userRepo->findByName($nameUser);
        if (!$user || !password_verify($passUser, $user['pass_user'])) {
            $this->jsonError('Nom d\'utilisateur ou mot de passe incorrect', 401);
        }

        $role = $user['role'] ?? 'user';
        $isAdmin = $role === 'admin' || $role === 'superadmin';
        $isSuperadmin = $role === 'superadmin';

        $userRegions = explode(',', $user['users_region']);
        $regionNames = [];

        if ($isSuperadmin) {
            // Superadmin gets all regions automatically
            $allRegions = $this->regionRepo->findAll();
            $regionIds = array_map('intval', array_column($allRegions, 'id_region'));
            $regionNames = array_column($allRegions, 'nom_region');
        } else {
            foreach ($regionIds as $rid) {
                if (!in_array((string)$rid, $userRegions)) {
                    $this->jsonError('Région non autorisée', 403);
                }
                $reg = $this->regionRepo->findById((int)$rid);
                if ($reg) {
                    $regionNames[] = $reg['nom_region'];
                }
            }
        }

        $entiteNames = [];
        if ($isAdmin) {
            $allEntites = $this->entiteRepo->findAll();
            $userEntityIds = array_map('intval', array_column($allEntites, 'id_entite'));
            $entiteIds = $userEntityIds;
            $entiteNames = array_column($allEntites, 'nom_entite');
        } else {
            $userEntities = $this->entiteRepo->findByUser((int)$user['id_user']);
            $userEntityIds = array_map('intval', array_column($userEntities, 'id_entite'));
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
            // Superadmin gets all module rights automatically
            $user['users-rights'] = [
                ['users_rights_objet' => 'vehicules', 'users_rights_valeur' => 'view,save,upd,del,print'],
                ['users_rights_objet' => 'voyages', 'users_rights_valeur' => 'view,save,upd,del,report,savetrajet'],
                ['users_rights_objet' => 'affectationVehicules', 'users_rights_valeur' => 'view,save,upd,del,print'],
                ['users_rights_objet' => 'maintenances', 'users_rights_valeur' => 'view,save,upd,del,print,updPrestataire,viewCentreCout,viewPrestataire,viewVidange,delPrestataire,updCentreCout,delCentreCout,viewBonsReparation,savePrestataire,saveBonsReparation,updBonsReparation,viewReleveKms,saveReleveKms,saveCentreCout,delVidange'],
                ['users_rights_objet' => 'users', 'users_rights_valeur' => 'view,save,upd,del'],
                ['users_rights_objet' => 'config', 'users_rights_valeur' => 'view,save,upd,del'],
                ['users_rights_objet' => 'report', 'users_rights_valeur' => 'view'],
            ];
        } else {
            $user['users-rights'] = $this->userRepo->findRights((int)$user['id_user']);
        }
        $user['users-entite'] = array_map('intval', $userEntityIds);
        unset($user['pass_user']);

        $_SESSION['usr-con'] = $user;
        session_regenerate_id(true);
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
