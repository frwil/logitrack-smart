<?php
/**
 * Auth controller — login/logout/session management.
 */
class AuthController extends BaseController
{
    private UserRepository $userRepo;
    private RegionRepository $regionRepo;

    public function __construct(UserRepository $userRepo, RegionRepository $regionRepo)
    {
        $this->userRepo = $userRepo;
        $this->regionRepo = $regionRepo;
    }

    /** Handle login POST. Returns JSON: {success:true} or {success:false, error:"..."}. */
    public function login(): never
    {
        $nameUser = $this->post('name-user');
        $passUser = $this->post('pass-user');
        $regionId = $this->post('region-user');

        if (!$nameUser || !$passUser || !$regionId) {
            $this->jsonError('Tous les champs sont obligatoires');
        }

        $user = $this->userRepo->findByName($nameUser);
        if (!$user || !password_verify($passUser, $user['pass_user'])) {
            $this->jsonError('Nom d\'utilisateur ou mot de passe incorrect', 401);
        }

        $region = $this->regionRepo->findById((int)$regionId);
        if (!$region) {
            $this->jsonError('Région invalide', 400);
        }

        $userRegions = explode(',', $user['users_region']);
        $found = false;
        for ($i = 0; $i < count($userRegions); $i++) {
            if ((int)$userRegions[$i] === (int)$region['id_region']) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $this->jsonError('Vous n\'avez pas le droit de vous connecter à cette région', 403);
        }

        $user['region-sel'] = $region['id_region'];
        $user['region-sel-name'] = $region['nom_region'];
        $user['region-sel-admin'] = $region['is_admin'];
        $user['users-rights'] = $this->userRepo->findRights((int)$user['id_user']);
        unset($user['pass_user']);

        $_SESSION['usr-con'] = $user;
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->json();
    }

    /** Handle region switch POST. Returns JSON. */
    public function switchRegion(): never
    {
        $regionId = $this->post('nSess');
        if (!$regionId) {
            $this->jsonError('Région non spécifiée');
        }

        $reg = $this->regionRepo->findById((int)$regionId);
        if (!$reg) {
            $this->jsonError('Région introuvable');
        }

        $userRegions = explode(',', $_SESSION['usr-con']['users_region']);
        if (!in_array($reg['id_region'], $userRegions)) {
            $this->jsonError('Accès non autorisé à cette région', 403);
        }

        $_SESSION['usr-con']['region-sel'] = $reg['id_region'];
        $_SESSION['usr-con']['region-sel-name'] = $reg['nom_region'];

        $this->json();
    }
}
