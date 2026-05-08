<?php
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function j($value): string
{
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
}

function is_valid_page(string $page): bool
{
    static $allowed = [
        'vehicules', 'voyages', 'affectationVehicules', 'maintenances',
        'configuration', 'reports', 'users', 'config',
        'userRegistration', 'import', 'reporting',
    ];
    return in_array($page, $allowed, true);
}
