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

        if (!$nameUser || !$passUser || !$regionIds) {
            $this->jsonError('Tous les champs sont obligatoires');
        }

        if (!is_array($regionIds)) $regionIds = [$regionIds];
        if (!is_array($entiteIds)) $entiteIds = [$entiteIds];

        $user = $this->userRepo->findByName($nameUser);
        if (!$user || !password_verify($passUser, $user['pass_user'])) {
            $this->jsonError('Nom d\'utilisateur ou mot de passe incorrect', 401);
        }

        $userRegions = explode(',', $user['users_region']);
        $regionNames = [];
        $isAdmin = false;
        foreach ($regionIds as $rid) {
            if (!in_array((string)$rid, $userRegions)) {
                $this->jsonError('Région non autorisée', 403);
            }
            $reg = $this->regionRepo->findById((int)$rid);
            if ($reg) {
                $regionNames[] = $reg['nom_region'];
                if ($reg['is_admin']) $isAdmin = true;
            }
        }

        // Validate and collect entity names
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
        $user['entite-sel'] = array_map('intval', $entiteIds);
        $user['entite-sel-names'] = $entiteNames;
        $user['users-rights'] = $this->userRepo->findRights((int)$user['id_user']);
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
        $input = json_decode(file_get_contents('php://input'), true);
        $regionIds = $input['regionIds'] ?? null;
        $entiteIds = $input['entiteIds'] ?? null;

        if (!$regionIds || !$entiteIds) {
            $this->jsonError('Régions et entités requises');
        }

        // Validate regions
        $userRegions = explode(',', $_SESSION['usr-con']['users_region']);
        $regionNames = [];
        foreach ($regionIds as $rid) {
            if (!in_array((string)$rid, $userRegions)) {
                $this->jsonError('Accès non autorisé à cette région', 403);
            }
            $reg = $this->regionRepo->findById((int)$rid);
            if ($reg) $regionNames[] = $reg['nom_region'];
        }

        // Validate entities
        $userEntityIds = $_SESSION['usr-con']['users-entite'] ?? [];
        $entiteNames = [];
        foreach ($entiteIds as $eid) {
            if (!in_array((int)$eid, $userEntityIds)) {
                $this->jsonError('Accès non autorisé à cette entité', 403);
            }
            $ent = $this->entiteRepo->findById((int)$eid);
            if ($ent) $entiteNames[] = $ent['nom_entite'];
        }

        $_SESSION['usr-con']['region-sel'] = array_map('intval', $regionIds);
        $_SESSION['usr-con']['region-sel-names'] = $regionNames;
        $_SESSION['usr-con']['entite-sel'] = array_map('intval', $entiteIds);
        $_SESSION['usr-con']['entite-sel-names'] = $entiteNames;

        $this->json();
    }
}
