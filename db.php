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

function db_context_filter(array $regionIds, array $entiteIds): array
{
    $parts = [];
    $params = [];
    if (!empty($regionIds)) {
        [$ph, $p] = db_in($regionIds);
        $parts[] = "affectation_vehicule.id_region IN ($ph)";
        $params = array_merge($params, $p);
    }
    if (!empty($entiteIds)) {
        [$ph, $p] = db_in($entiteIds);
        $parts[] = "affectation_vehicule.id_entite IN ($ph)";
        $params = array_merge($params, $p);
    }
    $where = empty($parts) ? '1' : implode(' AND ', $parts);
    return [$where, $params];
}

function getContextRegions(): array
{
    $sel = $_SESSION['usr-con']['region-sel'] ?? [];
    if (!empty($sel)) {
        return $sel;
    }
    // Fallback: all allowed regions (superadmin gets all, others get assigned)
    if ($_SESSION['usr-con']['is-superadmin'] ?? false) {
        $con = $GLOBALS['con'] ?? null;
        if ($con) {
            $repo = new RegionRepository($con);
            return array_map('intval', array_column($repo->findAll(), 'id_region'));
        }
    }
    return array_map('intval', explode(',', $_SESSION['usr-con']['users_region'] ?? ''));
}

function getContextEntities(): array
{
    $sel = $_SESSION['usr-con']['entite-sel'] ?? [];
    if (!empty($sel)) {
        return $sel;
    }
    // Fallback: all allowed entities (superadmin gets all, others get assigned)
    if ($_SESSION['usr-con']['is-superadmin'] ?? false) {
        $con = $GLOBALS['con'] ?? null;
        if ($con) {
            $repo = new EntiteRepository($con);
            return array_map('intval', array_column($repo->findAll(), 'id_entite'));
        }
    }
    return $_SESSION['usr-con']['users-entite'] ?? [];
}
