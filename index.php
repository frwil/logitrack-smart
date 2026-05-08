<?php
// === Bootstrap — runs for every request ===
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
// PHP built-in server has no session save path — use local tmp/ if it exists
$localSessions = __DIR__ . '/tmp/sessions';
if (!ini_get('session.save_path') && is_dir($localSessions)) {
    ini_set('session.save_path', $localSessions);
}
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
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

// === POST handling — must run BEFORE any HTML output ===
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    // CSRF check
    $csrf_token = $_POST['csrf_token'] ?? null;
    if ($csrf_token === null):
        $json = json_decode(file_get_contents('php://input'), true);
        $csrf_token = $json['csrf_token'] ?? null;
    endif;
    if (!hash_equals($_SESSION['csrf_token'], (string)$csrf_token)):
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'error' => 'CSRF validation failed — reload the page']));
    endif;

    // DB connection + route dispatch
    try {
        $con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
        if (!$con) {
            throw new \RuntimeException('Database connection failed');
        }
        require_once __DIR__ . '/controllers/router.php';
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['success' => false, 'error' => $e->getMessage()]));
    }
endif;

// === GET request: render the page ===
$con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" />
    <title>LogiTrack — Groupe NJS</title>
    <link rel="stylesheet" href="public/build/css/main.css">
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script>window.CSRF_TOKEN = <?= j($_SESSION['csrf_token']) ?>;</script>

</head>

<body>
    <script src="public/build/js/main.js"></script>
    <script>
        $.ajaxPrefilter(function(options, originalOptions) {
            if (options.type && options.type.toLowerCase() === 'post') {
                options.data = options.data || '';
                if (typeof options.data === 'string') {
                    options.data += (options.data ? '&' : '') + 'csrf_token=' + encodeURIComponent(window.CSRF_TOKEN);
                }
            }
        });
        $(document).on('submit', 'form[method="post"]', function() {
            var $form = $(this);
            if (!$form.find('input[name="csrf_token"]').length) {
                $form.append('<input type="hidden" name="csrf_token" value="' + window.CSRF_TOKEN + '">');
            }
        });
    </script>
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
        $user = $userRepo->findById((int)$_SESSION['usr-con']['id_user']);
        unset($user['pass_user']);
        $user['region-sel'] = (isset($_SESSION['usr-con']['region-sel']) ? $_SESSION['usr-con']['region-sel'] : '');
        $user['region-sel-name'] = (isset($_SESSION['usr-con']['region-sel-name']) ? $_SESSION['usr-con']['region-sel-name'] : '');
        $user['region-sel-admin'] = (isset($_SESSION['usr-con']['region-sel-admin']) ? $_SESSION['usr-con']['region-sel-admin'] : '');
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
</body>

</html>
