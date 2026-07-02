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
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

function db_exec($con, string $sql, array $params = []): bool
{
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) return false;
    if ($params) {
        $types = _db_types($params);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
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
    $id = mysqli_insert_id($con);
    mysqli_stmt_close($stmt);
    return $id;
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
        $selInt = array_map('intval', $sel);
        // If an admin region (e.g. CAMEROUN) is selected, expand to include
        // all non-admin operational regions so that data is visible.
        $con = $GLOBALS['con'] ?? null;
        if ($con) {
            $repo = new RegionRepository($con);
            $nonAdminIds = array_map('intval', array_column($repo->findAllNonAdmin(), 'id_region'));
            if (array_diff($selInt, $nonAdminIds)) {
                return array_unique(array_merge($selInt, $nonAdminIds));
            }
        }
        return $selInt;
    }
    // Fallback: all allowed regions (superadmin gets all, others get assigned)
    if ($_SESSION['usr-con']['is-superadmin'] ?? false) {
        $con = $GLOBALS['con'] ?? null;
        if ($con) {
            $repo = new RegionRepository($con);
            return array_map('intval', array_column($repo->findAll(), 'id_region'));
        }
    }
    $userRegions = array_map('intval', explode(',', $_SESSION['usr-con']['users_region'] ?? ''));
    // If an admin region (e.g. CAMEROUN) is in the user's assigned regions,
    // expand to include all non-admin operational regions.
    $con = $GLOBALS['con'] ?? null;
    if ($con && !empty($userRegions)) {
        $repo = new RegionRepository($con);
        $nonAdminIds = array_map('intval', array_column($repo->findAllNonAdmin(), 'id_region'));
        if (array_diff($userRegions, $nonAdminIds)) {
            return array_unique(array_merge($userRegions, $nonAdminIds));
        }
    }
    return $userRegions;
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

function devise(): string
{
    $con = $GLOBALS['con'] ?? null;
    if (!$con) return 'F';
    static $devise = null;
    if ($devise === null) {
        $repo = new ConfigRepository($con);
        $devise = $repo->getParametre('devise', 'F');
    }
    return $devise;
}

/**
 * Get the exploded rights array for a given module object from session.
 * Returns empty array if no rights found for that object.
 */
function getUserRightsFor(string $object): array
{
    foreach ($_SESSION['usr-con']['users-rights'] ?? [] as $r) {
        if (($r['users_rights_objet'] ?? '') === $object) {
            return explode(',', $r['users_rights_valeur'] ?? '');
        }
    }
    return [];
}

/**
 * Backward-compatible sub-right check.
 *
 * If the user has the $specific right in their $rights array → true.
 * If the user has ANY of the $knownSpecifics but NOT $specific → false (explicit deny).
 * If the user has NONE of the $knownSpecifics → fall back to $fallback (old user compat).
 */
function hasSubRight(string $specific, string $fallback, array $rights, array $knownSpecifics): bool
{
    if (in_array($specific, $rights, true)) {
        return true;
    }
    $hasAnySpecific = !empty(array_intersect($knownSpecifics, $rights));
    if (!$hasAnySpecific) {
        return in_array($fallback, $rights, true);
    }
    return false;
}
