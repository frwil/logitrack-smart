<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
// Ensure session save path is valid and writable
$localSessions = __DIR__ . '/tmp/sessions';
if (!is_dir($localSessions)) {
    @mkdir($localSessions, 0700, true);
}
$currentPath = ini_get('session.save_path');
if ((!$currentPath || !is_writable($currentPath)) && is_dir($localSessions)) {
    ini_set('session.save_path', $localSessions);
}
// Only set Secure cookie on actual HTTPS connections
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (!$isHttps && isset($_SERVER['REQUEST_SCHEME'])) {
    $isHttps = $_SERVER['REQUEST_SCHEME'] === 'https';
}
if ($isHttps) {
    ini_set('session.cookie_secure', 1);
}
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
if (isset($_GET['logout'])) unset($_SESSION['usr-con']);

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sanitize.php';
require_once __DIR__ . '/models/autoload.php';
require_once __DIR__ . '/controllers/autoload.php';

set_time_limit(60);

if ($_SERVER['REQUEST_METHOD'] === 'POST'):

    // CSRF check — also decode JSON body for reuse by the router.
    // php://input can only be read once, so we save the decoded result.
    $csrf_raw = $_POST['csrf_token'] ?? null;
    $csrf_from = 'post';
    if (is_array($csrf_raw)) {
        $csrf_token = end($csrf_raw);
        $csrf_note = 'array(' . count($csrf_raw) . ')';
    } else {
        $csrf_token = $csrf_raw;
        $csrf_note = 'string';
    }
    $jsonPost = [];
    if ($csrf_token === null):
        $jsonPost = json_decode(file_get_contents('php://input'), true) ?? [];
        $csrf_token = $jsonPost['csrf_token'] ?? null;
        $csrf_from = 'json';
        $csrf_note = 'json';
    endif;
    $session_token = $_SESSION['csrf_token'] ?? '(none)';
    $csrf_match = hash_equals($session_token, (string)$csrf_token);
    // Log every CSRF check for diagnostics
    $log_line = date('Y-m-d H:i:s')
        . ' ' . ($csrf_match ? 'OK' : 'FAIL')
        . ' sid=' . substr(session_id(), 0, 12)
        . ' src=' . $csrf_from
        . ' type=' . $csrf_note
        . ' rcv=' . substr((string)$csrf_token, 0, 16)
        . ' exp=' . substr($session_token, 0, 16)
        . ' post_keys=' . implode(',', array_slice(array_keys($_POST), 0, 10))
        . "\n";
    $log_dir = __DIR__ . '/tmp';
    $log_file = $log_dir . '/csrf_errors.log';
    if (is_dir($log_dir) || @mkdir($log_dir, 0700, true)) {
        @file_put_contents($log_file, $log_line, FILE_APPEND);
    }
    // Also write to PHP error log as fallback
    if (!$csrf_match) {
        @error_log('CSRF FAIL: ' . $log_line);
    }
    if (!$csrf_match):
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'error' => 'CSRF validation failed — reload the page', 'reload' => true]));
    endif;

    // Merge JSON body into $_POST so controllers can read JSON-sent data via post()
    if ($jsonPost) {
        $_POST = array_merge($_POST, $jsonPost);
    }

    // DB connection + route dispatch
    try {
        $con = mysqli_init();
        if (!$con) {
            throw new \RuntimeException('Database connection failed');
        }
        mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        mysqli_options($con, MYSQLI_OPT_READ_TIMEOUT, 30);
        mysqli_real_connect($con, getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
        if (mysqli_connect_errno()) {
            throw new \RuntimeException('Database connection failed');
        }
        require_once __DIR__ . '/controllers/router.php';
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'error' => $e->getMessage()]));
    }
endif;

$con = mysqli_init();
if ($con) {
    mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    mysqli_options($con, MYSQLI_OPT_READ_TIMEOUT, 30);
    mysqli_real_connect($con, getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
}
$partial = isset($_GET['_partial']) && isset($_SESSION['usr-con']);
?>
<?php if (!$partial): ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" />
    <title>LogiTrack</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%235D54A4'/><text x='16' y='23' text-anchor='middle' font-family='Arial,sans-serif' font-weight='bold' font-size='18' fill='white'>LT</text></svg>">
    <link rel="stylesheet" href="public/build/css/main.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>window.CSRF_TOKEN = <?= j($_SESSION['csrf_token']) ?>;</script>

</head>

<body>
    <script src="public/build/js/main.js"></script>
    <script>
        if (!window.CSRF_TOKEN) {
            if (!sessionStorage.getItem('csrf-reloaded')) {
                sessionStorage.setItem('csrf-reloaded', '1');
                location.reload();
            }
        } else {
            sessionStorage.removeItem('csrf-reloaded');
        }
        $(document).ajaxError(function(event, jqXHR) {
            if (jqXHR.status === 403 && !sessionStorage.getItem('csrf-reloaded')) {
                try {
                    var resp = JSON.parse(jqXHR.responseText);
                    if (resp.reload) {
                        sessionStorage.setItem('csrf-reloaded', '1');
                        location.reload();
                    }
                } catch(e) {}
            }
        });
        $.ajaxPrefilter(function(options, originalOptions) {
            if (options.type && options.type.toLowerCase() === 'post') {
                if (options.contentType && options.contentType.indexOf('application/json') !== -1) return;
                if (options.data instanceof FormData) {
                    if (!options.data.has('csrf_token')) options.data.append('csrf_token', window.CSRF_TOKEN);
                } else if (typeof options.data === 'object' && options.data !== null) {
                    options.data.csrf_token = window.CSRF_TOKEN;
                } else {
                    options.data = (options.data || '');
                    if (options.data.indexOf('csrf_token=') === -1) {
                        options.data += (options.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
                    }
                }
            }
        });
        $(document).on('submit', 'form[method="post"]', function() {
            var $form = $(this);
            if (!$form.find('input[name="csrf_token"]').length) {
                $('<input type="hidden" name="csrf_token">').val(window.CSRF_TOKEN).appendTo($form);
            }
        });
    </script>
<?php endif; ?>
    <?php function isRightObjectAllowed($r_objet, $rights)
    {
        for ($i = 0; $i < count($rights); $i++) {
            if ($rights[$i]['users_rights_objet'] == $r_objet)
                return $rights[$i]['users_rights_valeur'];
        }
        return false;
    }
    ?>

    <?php if (isset($dbError)): ?>
        <div class="alert alert-danger m-3">Erreur base de données : <?= h($dbError) ?></div>
    <?php elseif (!isset($_SESSION['usr-con'])): include('login.php');
    else : ?>
        <?php
        $userRepo = new UserRepository($con);
        $entiteRepo = new EntiteRepository($con);
        $user = $userRepo->findById((int)$_SESSION['usr-con']['id_user']);
        unset($user['pass_user']);

        // Migrate old scalar region-sel to array format
        $oldRegion = $_SESSION['usr-con']['region-sel'] ?? '';
        if ($oldRegion !== '' && !is_array($oldRegion)) {
            $user['region-sel'] = $oldRegion ? [(int)$oldRegion] : [];
            $user['region-sel-names'] = [$_SESSION['usr-con']['region-sel-name'] ?? ''];
            $user['region-sel-admin'] = $_SESSION['usr-con']['region-sel-admin'] ?? '';
        } else {
            $user['region-sel'] = $_SESSION['usr-con']['region-sel'] ?? [];
            $user['region-sel-names'] = $_SESSION['usr-con']['region-sel-names'] ?? [];
        }

        $isAdmin = $_SESSION['usr-con']['is-admin'] ?? false;
        $isSuperadmin = $_SESSION['usr-con']['is-superadmin'] ?? false;
        $user['is-admin'] = $isAdmin;
        $user['is-superadmin'] = $isSuperadmin;

        $user['entite-sel'] = $_SESSION['usr-con']['entite-sel'] ?? [];
        $user['entite-sel-names'] = $_SESSION['usr-con']['entite-sel-names'] ?? [];

        if ($isAdmin) {
            $allEntites = $entiteRepo->findAll();
            $user['users-entite'] = array_map('intval', array_column($allEntites, 'id_entite'));
            if (empty($user['entite-sel'])) {
                $user['entite-sel'] = $user['users-entite'];
                $user['entite-sel-names'] = array_column($allEntites, 'nom_entite');
            }
        } else {
            $userEntites = $entiteRepo->findByUser((int)$user['id_user']);
            $user['users-entite'] = array_map('intval', array_column($userEntites, 'id_entite'));
        }

        $_SESSION['usr-con'] = $user;
        $_SESSION['usr-con']['users-rights'] = $userRepo->findRights((int)$_SESSION['usr-con']['id_user']);
        ?>
        <?php $user_rights = $_SESSION['usr-con']['users-rights']; ?>

        <?php
        $page = '';
        if (isset($_GET['page']) && is_string($_GET['page']) && $_GET['page'] !== '') {
            $page = basename($_GET['page']);
        }
        if ($page !== '' && is_valid_page($page)) :
            if (file_exists($page . ".php")) :
                include($page . ".php");
            else :
                include("home.php");
            endif;
        else :
            include("home.php");
        endif; ?>
    <?php endif; ?>
<?php if (!$partial): ?>
</body>

</html>
<?php endif; ?>
