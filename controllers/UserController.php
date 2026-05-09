<?php
class UserController extends BaseController
{
    private UserRepository $repo;
    private RegionRepository $regionRepo;
    private EntiteRepository $entiteRepo;

    public function __construct(UserRepository $repo, RegionRepository $regionRepo, EntiteRepository $entiteRepo)
    {
        $this->repo = $repo;
        $this->regionRepo = $regionRepo;
        $this->entiteRepo = $entiteRepo;
    }

    private function sessionRole(): string
    {
        return $_SESSION['usr-con']['role'] ?? 'user';
    }

    private function isSuperadmin(): bool
    {
        return $this->sessionRole() === 'superadmin';
    }

    private function isAdmin(): bool
    {
        $r = $this->sessionRole();
        return $r === 'admin' || $r === 'superadmin';
    }

    private function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            $this->jsonError('Accès non autorisé', 403);
        }
    }

    /** Create a new user. Admin can only create users with role='user'. */
    public function create(): never
    {
        $this->requireAdmin();

        $name = $this->post('name-user');
        $pass = $this->post('pass-user');
        $fullname = $this->post('fullname-user') ?: null;
        $email = $this->post('email-user');
        $role = $this->post('role-user') ?: 'user';
        $regionIds = $this->post('region-user');
        $entiteIds = $this->post('entite-user');
        $rightsJson = $this->post('rights-json');

        if (!$name || !$pass || !$email) {
            $this->jsonError('Nom d\'utilisateur, mot de passe et email sont obligatoires');
        }

        if (!$this->isSuperadmin()) {
            $role = 'user';
        }
        if (!in_array($role, ['user', 'admin'], true)) {
            $role = 'user';
        }

        if (!is_array($regionIds)) $regionIds = $regionIds ? [$regionIds] : [];
        if (!is_array($entiteIds)) $entiteIds = $entiteIds ? [$entiteIds] : [];
        $regionIds = array_map('intval', $regionIds);
        $entiteIds = array_map('intval', $entiteIds);

        $rights = [];
        if ($rightsJson) {
            $decoded = json_decode($rightsJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $obj => $val) {
                    if (is_array($val)) {
                        $rights[$obj] = implode(',', $val);
                    } else {
                        $rights[$obj] = $val;
                    }
                }
            }
        }

        // Validate uniqueness
        if ($this->repo->findByName($name)) {
            $this->jsonError('Ce nom d\'utilisateur existe déjà');
        }

        try {
            $this->repo->transactional(function () use ($name, $pass, $fullname, $email, $role, $regionIds, $entiteIds, $rights) {
                $passHash = password_hash($pass, PASSWORD_DEFAULT);
                $userId = $this->repo->insertUser($name, $passHash, $fullname, $email, $role);
                $userId = (int)$userId;
                if ($regionIds) {
                    $this->repo->replaceUserRegions($userId, $regionIds);
                }
                if ($entiteIds) {
                    $this->repo->replaceUserEntities($userId, $entiteIds);
                }
                if ($rights) {
                    $this->repo->replaceUserRights($userId, $rights);
                }
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la création de l\'utilisateur');
        }
    }

    /** Fetch a single user for the edit modal. */
    public function fetchByHash(): never
    {
        $this->requireAdmin();

        $id = (int)$this->post('id-user-forModal');
        $user = $this->repo->findById($id);
        if (!$user) {
            $this->jsonError('Utilisateur introuvable', 404);
        }

        // Admin cannot fetch other admins or superadmins
        if (!$this->isSuperadmin() && ($user['role'] === 'admin' || $user['role'] === 'superadmin')) {
            $this->jsonError('Accès non autorisé', 403);
        }

        $regions = $this->repo->findUserRegions($id);
        $entities = $this->repo->findUserEntities($id);
        $rightsRows = $this->repo->findUserRightsFlat($id);

        $rights = [];
        foreach ($rightsRows as $r) {
            $rights[$r['users_rights_objet']] = $r['users_rights_valeur'];
        }

        $this->json([
            'data' => [
                'id_user'      => $user['id_user'],
                'name_user'    => $user['name_user'],
                'fullname_user' => $user['fullname_user'],
                'email_user'   => $user['email_user'],
                'role'         => $user['role'] ?? 'user',
                'is_active'    => (int)$user['is_active'],
                'regions'      => array_map('intval', array_column($regions, 'id_region')),
                'entities'     => array_map('intval', array_column($entities, 'id_entite')),
                'rights'       => $rights,
            ]
        ]);
    }

    /** Update an existing user. */
    public function update(): never
    {
        $this->requireAdmin();

        $id = (int)$this->post('id-user');
        $pass = $this->post('pass-user');
        $fullname = $this->post('fullname-user') ?: null;
        $email = $this->post('email-user');
        $role = $this->post('role-user') ?: 'user';
        $isActive = (int)$this->post('is-active-user') ?: 0;
        $regionIds = $this->post('region-user');
        $entiteIds = $this->post('entite-user');
        $rightsJson = $this->post('rights-json');

        $target = $this->repo->findById($id);
        if (!$target) {
            $this->jsonError('Utilisateur introuvable', 404);
        }

        // Admin can only edit users, not admins/superadmins
        if (!$this->isSuperadmin() && ($target['role'] === 'admin' || $target['role'] === 'superadmin')) {
            $this->jsonError('Accès non autorisé', 403);
        }

        // Admin cannot change role
        if (!$this->isSuperadmin()) {
            $role = $target['role'];
        }
        if (!in_array($role, ['user', 'admin'], true)) {
            $role = $target['role'];
        }

        // Prevent self-demotion
        if ($id === (int)($_SESSION['usr-con']['id_user'] ?? 0) && $role !== $target['role']) {
            $this->jsonError('Vous ne pouvez pas modifier votre propre rôle');
        }

        // Superadmin can never be deactivated
        if ($target['role'] === 'superadmin' && !$isActive) {
            $this->jsonError('Un superadmin ne peut pas être désactivé');
        }

        if (!is_array($regionIds)) $regionIds = $regionIds ? [$regionIds] : [];
        if (!is_array($entiteIds)) $entiteIds = $entiteIds ? [$entiteIds] : [];
        $regionIds = array_map('intval', $regionIds);
        $entiteIds = array_map('intval', $entiteIds);

        $rights = [];
        if ($rightsJson) {
            $decoded = json_decode($rightsJson, true);
            if (is_array($decoded)) {
                foreach ($decoded as $obj => $val) {
                    if (is_array($val)) {
                        $rights[$obj] = implode(',', $val);
                    } else {
                        $rights[$obj] = $val;
                    }
                }
            }
        }

        // Name is immutable — keep the existing one
        $name = $target['name_user'];

        try {
            $this->repo->transactional(function () use ($id, $name, $pass, $fullname, $email, $role, $isActive, $regionIds, $entiteIds, $rights) {
                $this->repo->updateUser($id, $name, $fullname, $email, $role, (bool)$isActive);
                if ($pass) {
                    $this->repo->updatePassword($id, password_hash($pass, PASSWORD_DEFAULT));
                }
                $this->repo->replaceUserRegions($id, $regionIds);
                $this->repo->replaceUserEntities($id, $entiteIds);
                if ($rights) {
                    $this->repo->replaceUserRights($id, $rights);
                }
            });
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la modification');
        }
    }

    /** Soft-delete a user. */
    public function delete(): never
    {
        $this->requireAdmin();

        $id = (int)$this->post('id-user-forDel');
        $target = $this->repo->findById($id);
        if (!$target) {
            $this->jsonError('Utilisateur introuvable', 404);
        }

        if (!$this->isSuperadmin() && ($target['role'] === 'admin' || $target['role'] === 'superadmin')) {
            $this->jsonError('Accès non autorisé', 403);
        }

        // Prevent self-deletion
        if ($id === (int)($_SESSION['usr-con']['id_user'] ?? 0)) {
            $this->jsonError('Vous ne pouvez pas supprimer votre propre compte');
        }

        try {
            $this->repo->transactional(fn() => $this->repo->deleteUser($id));
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la suppression');
        }
    }

    /** Toggle is_active on a user. */
    public function toggleActive(): never
    {
        $this->requireAdmin();

        $id = (int)$this->post('id-user-active');
        $val = (int)$this->post('val-user-active');
        $val = $val ? 1 : 0;

        $target = $this->repo->findById($id);
        if (!$target) {
            $this->jsonError('Utilisateur introuvable', 404);
        }

        if (!$this->isSuperadmin() && ($target['role'] === 'admin' || $target['role'] === 'superadmin')) {
            $this->jsonError('Accès non autorisé', 403);
        }

        // Superadmin can never be deactivated
        if ($target['role'] === 'superadmin' && !$val) {
            $this->jsonError('Un superadmin ne peut pas être désactivé');
        }

        // Prevent self-deactivation
        if ($id === (int)($_SESSION['usr-con']['id_user'] ?? 0)) {
            $this->jsonError('Vous ne pouvez pas modifier votre propre statut');
        }

        try {
            $this->repo->transactional(fn() =>
                $this->repo->updateUser($id, $target['name_user'], $target['fullname_user'], $target['email_user'], $target['role'] ?? 'user', (bool)$val)
            );
            $this->json();
        } catch (\mysqli_sql_exception $e) {
            $this->jsonError('Erreur lors de la modification');
        }
    }
}
