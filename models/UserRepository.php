<?php
/**
 * User repository — users + users_rights table queries.
 */
class UserRepository extends BaseRepository
{
    public function findByName(string $nameUser): ?array
    {
        return $this->selectOne(
            "SELECT * FROM users WHERE name_user = ?",
            [$nameUser]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->selectOne(
            "SELECT * FROM users WHERE id_user = ?",
            [$id]
        );
    }

    /** Rights for the currently logged-in user. */
    public function findRights(int $userId): array
    {
        return $this->select(
            "SELECT * FROM users_rights WHERE id_user = ?",
            [$userId]
        );
    }

    public function insertUser(string $name, string $passHash, ?string $fullname, ?string $email, string $role = 'user'): int|string
    {
        return $this->insertGetId(
            "INSERT INTO users (name_user, pass_user, fullname_user, email_user, role) VALUES (?, ?, ?, ?, ?)",
            [$name, $passHash, $fullname, $email, $role]
        );
    }

    public function insertUserRegion(string $nameUser, int $regionId): bool
    {
        return $this->exec(
            "INSERT INTO users_region (id_user, id_region, is_active)
             VALUES ((SELECT id_user FROM users WHERE name_user = ?), ?, '1')",
            [$nameUser, $regionId]
        );
    }

    public function findAll(?string $roleFilter = null): array
    {
        $sql = "SELECT id_user, name_user, fullname_user, email_user, is_active, role
                FROM users WHERE 1";
        $params = [];
        if ($roleFilter !== null) {
            $sql .= " AND role = ?";
            $params[] = $roleFilter;
        }
        $sql .= " ORDER BY name_user ASC";
        return $this->select($sql, $params);
    }

    public function findUserRegions(int $userId): array
    {
        return $this->select(
            "SELECT id_region FROM users_region WHERE id_user = ? AND is_active = 1",
            [$userId]
        );
    }

    public function findUserEntities(int $userId): array
    {
        return $this->select(
            "SELECT id_entite FROM users_entite WHERE id_user = ? AND is_active = 1",
            [$userId]
        );
    }

    public function findUserRightsFlat(int $userId): array
    {
        return $this->select(
            "SELECT users_rights_objet, users_rights_valeur FROM users_rights WHERE id_user = ?",
            [$userId]
        );
    }

    public function updateUser(int $id, string $name, ?string $fullname, ?string $email, string $role, bool $isActive): bool
    {
        return $this->exec(
            "UPDATE users SET name_user = ?, fullname_user = ?, email_user = ?, role = ?, is_active = ? WHERE id_user = ?",
            [$name, $fullname, $email, $role, $isActive ? 1 : 0, $id]
        );
    }

    public function updatePassword(int $id, string $passHash): bool
    {
        return $this->exec(
            "UPDATE users SET pass_user = ? WHERE id_user = ?",
            [$passHash, $id]
        );
    }

    public function deleteUser(int $id): bool
    {
        return $this->exec("UPDATE users SET is_active = 0 WHERE id_user = ?", [$id]);
    }

    public function replaceUserRegions(int $userId, array $regionIds): void
    {
        $this->exec("DELETE FROM users_region WHERE id_user = ?", [$userId]);
        foreach ($regionIds as $rid) {
            $this->exec(
                "INSERT INTO users_region (id_user, id_region, is_active) VALUES (?, ?, 1)",
                [$userId, (int)$rid]
            );
        }
    }

    public function replaceUserEntities(int $userId, array $entiteIds): void
    {
        $this->exec("DELETE FROM users_entite WHERE id_user = ?", [$userId]);
        foreach ($entiteIds as $eid) {
            $this->exec(
                "INSERT INTO users_entite (id_user, id_entite, is_active) VALUES (?, ?, 1)",
                [$userId, (int)$eid]
            );
        }
    }

    public function replaceUserRights(int $userId, array $rights): void
    {
        $this->exec("DELETE FROM users_rights WHERE id_user = ?", [$userId]);
        foreach ($rights as $objet => $valeur) {
            if ($valeur === '' || $valeur === []) continue;
            $this->exec(
                "INSERT INTO users_rights (id_user, users_rights_objet, users_rights_valeur) VALUES (?, ?, ?)",
                [$userId, $objet, $valeur]
            );
        }
    }
}
