<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}
@session_start();
?>
<?php if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); ?>
<?php if (isset($_GET['logout'])): unset($_SESSION['usr-con']);
endif; ?>
<?php //print_r($_SESSION);
?>
<?php require_once __DIR__ . '/env_loader.php'; ?>
<?php require_once __DIR__ . '/db.php'; ?>
<?php require_once __DIR__ . '/sanitize.php'; ?>
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
    <?php function isRightObjectAllowed($r_objet,$rights)
    {   
        //print_r($rights);    
        for ($i = 0; $i < count($rights); $i++) { 
                if($rights[$i]['users_rights_objet']==$r_objet)
                return $rights[$i]['users_rights_valeur'];
            }
            return false;
    }
    ?>
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    $csrf_token = $_POST['csrf_token'] ?? null;
    if ($csrf_token === null):
        $json = json_decode(file_get_contents('php://input'), true);
        $csrf_token = $json['csrf_token'] ?? null;
    endif;
    if (!hash_equals($_SESSION['csrf_token'], (string)$csrf_token)):
        http_response_code(403);
        die('CSRF validation failed');
    endif;
endif; ?>
    <?php $con = mysqli_connect(getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), getenv('DB_NAME')); ?>
    <?php if (!isset($_SESSION['usr-con'])): include('login.php');
    else : ?>
        <?php
        $q = db_select($con, "select * from users where id_user=?", [(int)$_SESSION['usr-con']['id_user']]);
        while ($r = mysqli_fetch_array($q)):
            $user = $r;
        endwhile;
        unset($user['pass_user']);
        $user['region-sel']=(isset($_SESSION['usr-con']['region-sel']) ? $_SESSION['usr-con']['region-sel'] : '');
        $user['region-sel-name']=(isset($_SESSION['usr-con']['region-sel-name']) ? $_SESSION['usr-con']['region-sel-name'] : '');
        $user['region-sel-admin']=(isset($_SESSION['usr-con']['region-sel-admin']) ? $_SESSION['usr-con']['region-sel-admin'] : '');
        $_SESSION['usr-con']=$user;
        $q = db_select($con, "select * from users_rights where id_user=?", [(int)$_SESSION['usr-con']['id_user']]);
        $rights = array();
        while ($r = mysqli_fetch_array($q)):
            array_push($rights, $r);
        endwhile;
        $_SESSION['usr-con']['users-rights'] = $rights;
        ?>
        <?php $user_rights = $_SESSION['usr-con']['users-rights'];?>
        <?php  ?>
        <?php //print_r($user_rights); 
        ?>
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
else:
    include("home.php");
endif; ?>
    <?php endif; ?>
</body>

</html>