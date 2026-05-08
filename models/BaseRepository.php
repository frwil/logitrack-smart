<?php
/**
 * Base repository — all repositories extend this.
 * Wraps db_select/db_exec/db_insert_id so callers never touch mysqli directly.
 */
class BaseRepository
{
    protected mysqli $con;

    public function __construct(mysqli $con)
    {
        $this->con = $con;
    }

    /** Run SELECT and return all rows as associative array. */
    protected function select(string $sql, array $params = []): array
    {
        $result = db_select($this->con, $sql, $params);
        if ($result === false) {
            return [];
        }
        return mysqli_fetch_all($result, MYSQLI_ASSOC);
    }

    /** Run SELECT and return first row, or null. */
    protected function selectOne(string $sql, array $params = []): ?array
    {
        $rows = $this->select($sql, $params);
        return $rows ? $rows[0] : null;
    }

    /** Run INSERT/UPDATE/DELETE. Returns affected rows. */
    protected function exec(string $sql, array $params = []): bool
    {
        return db_exec($this->con, $sql, $params);
    }

    /** Run INSERT IGNORE — returns true regardless of whether row was inserted or already existed. */
    protected function insertIgnore(string $sql, array $params = []): bool
    {
        return db_exec($this->con, $sql, $params);
    }

    /** Run INSERT and return the new auto-increment ID. */
    protected function insertGetId(string $sql, array $params = []): int|string
    {
        return db_insert_id($this->con, $sql, $params);
    }

    /** Wrap a callable in a transaction. */
    protected function transactional(callable $fn): mixed
    {
        mysqli_begin_transaction($this->con);
        try {
            $result = $fn();
            mysqli_commit($this->con);
            return $result;
        } catch (\Throwable $e) {
            mysqli_rollback($this->con);
            throw $e;
        }
    }
}
