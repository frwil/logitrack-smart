<?php @session_start(); ?>
<?php if (isset($_GET['logout'])): unset($_SESSION['usr-con']);
endif; ?>
<?php //print_r($_SESSION); 
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width" />
    <title>Gestion Logistique</title>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/all.css'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css'>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.0/css/buttons.dataTables.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/5.0.4/css/fixedColumns.dataTables.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.css" integrity="sha512-BDStKWno6Ga+5cOFT9BUnl9erQFzfj+Qmr5MDnuGqTQ/QYDO1LPdonnF6V6lBO6JI13wg29/XmPsufxmCJ8TvQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css">
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>

</head>

<body style="background:url('img/36d7f5_6fba6fa5d3ce4f74a889a303addc7274~mv2.png') no-repeat fixed <?php if(isset($_SESSION['usr-con'])) : ?>center<?php endif; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="js/notify.min.js"></script>
    <script src="js/html2canvas.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.dataTables.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.5.1/js/dataTables.rowGroup.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.5.1/js/rowGroup.dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.dataTables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js">
  </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
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
    <?php /* ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); */ ?>
    <?php $con = mysqli_connect("mysql-responsablelogistiquenjs.alwaysdata.net","390253","211Willy85@2101","responsablelogistiquenjs_logistiquenjs"); ?>
    <?php if (!isset($_SESSION['usr-con'])): include('login.php');
    else : ?>
        <?php
        $q = mysqli_query($con, "select * from users where id_user='{$_SESSION['usr-con']['id_user']}'");
        while ($r = mysqli_fetch_array($q)):
            $user = $r;
        endwhile;
        $user['region-sel']=(isset($_SESSION['usr-con']['region-sel']) ? $_SESSION['usr-con']['region-sel'] : '');
        $user['region-sel-name']=(isset($_SESSION['usr-con']['region-sel-name']) ? $_SESSION['usr-con']['region-sel-name'] : '');
        $user['region-sel-admin']=(isset($_SESSION['usr-con']['region-sel-admin']) ? $_SESSION['usr-con']['region-sel-admin'] : '');
        $_SESSION['usr-con']=$user;
        $q = mysqli_query($con, "select * from users_rights where id_user={$_SESSION['usr-con']['id_user']}");
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
        <?php if (isset($_GET['page']) && $_GET['page'] != '') :
            if (file_exists($_GET['page'] . ".php")) :
                include($_GET['page'] . ".php");
            else :
                include("home.php");
            endif;
        else:
            include("home.php");
        endif; ?>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pivottable/2.23.0/pivot.min.js" integrity="sha512-XgJh9jgd6gAHu9PcRBBAp0Hda8Tg87zi09Q2639t0tQpFFQhGpeCgaiEFji36Ozijjx9agZxB0w53edOFGCQ0g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
</body>

</html>