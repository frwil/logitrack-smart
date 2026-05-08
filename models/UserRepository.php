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

    public function insertUser(string $name, string $passHash, ?string $fullname, ?string $email): int|string
    {
        return $this->insert(
            "INSERT INTO users (name_user, pass_user, fullname_user, email_user) VALUES (?, ?, ?, ?)",
            [$name, $passHash, $fullname, $email]
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
}
