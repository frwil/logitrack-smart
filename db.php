<?php
/**
 * Safe database helper functions using prepared statements.
 * Requires mysqlnd for mysqli_stmt_get_result().
 *
 * Type notes:
 * - $_POST values are always strings; the helper defaults to 's' type.
 * - For integer columns, cast: [(int)$_POST['id']]
 * - For nullable columns, pass PHP null (becomes SQL NULL).
 */

function db_select($con, string $sql, array $params = []): mysqli_result|false
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) return false;
    if ($params) {
        $types = _db_types($params);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

function db_exec($con, string $sql, array $params = []): bool
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) return false;
    if ($params) {
        $types = _db_types($params);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    return mysqli_stmt_execute($stmt);
}

function db_insert_id($con, string $sql, array $params = []): int|string|false
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) return false;
    if ($params) {
        $types = _db_types($params);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_insert_id($con);
}

function _db_types(array $params): string
{
    $types = '';
    foreach ($params as $p) {
        if (is_int($p))       $types .= 'i';
        elseif (is_float($p)) $types .= 'd';
        else                  $types .= 's';
    }
    return $types;
}

function db_in(array $values): array
{
    return [
        implode(',', array_fill(0, count($values), '?')),
        array_values($values),
    ];
}
