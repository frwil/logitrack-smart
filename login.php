<?php
if (isset($_POST['name-user'])):
    $q = db_select($con, "select * from users where name_user=?", [$_POST['name-user']]);
    while ($r = mysqli_fetch_array($q)):
        $user = $r;
    endwhile;
    if (mysqli_num_rows($q) > 0 && password_verify($_POST['pass-user'], $user['pass_user'])):
        $regions = explode(',', $user['users_region']);
        $q = db_select($con, "select * from region where sha1(concat(id_region,nom_region))=?", [$_POST['region-user']]);
        while($r=mysqli_fetch_array($q)) $region=$r;
        $trouve=false;
        for($i=0;$i<count($regions);$i++){
            if(sha1($regions[$i].$region[1])==$_POST['region-user']) $trouve=true;
        }
        if ($trouve):
            $user['region-sel']=$region[0];
            $user['region-sel-name']=$region[1];
            $user['region-sel-admin']=$region['is_admin'];
            $q = db_select($con, "select * from users_rights where id_user=?", [(int)$user['id_user']]);
            $rights = array();
            while ($r = mysqli_fetch_array($q)) :
                array_push($rights, $r);
            endwhile;
            $user['users-rights'] = $rights;
            unset($user['pass_user']);
            $_SESSION['usr-con'] = $user;
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            die("%%%%%%1");
        else :
            die("%%%%%%3");
        endif;
    else:
        die("%%%%%%0");
    endif;
endif;
?>
<link rel="stylesheet" href="css/style.css">
<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">
                <i class="fa fa-truck"></i>
            </div>
            <h2>LogiTrack</h2>
            <p>Gestion de flotte — Groupe NJS</p>
        </div>
        <form action="#" method="post">
            <div class="login-field">
                <label for="name-user">Identifiant</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-user"></i>
                    <input type="text" class="login-input" placeholder="Nom d'utilisateur ou email" id="name-user" name="name-user">
                </div>
            </div>
            <div class="login-field">
                <label for="pass-user">Mot de passe</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-lock"></i>
                    <input type="password" class="login-input" placeholder="••••••••" id="pass-user" name="pass-user">
                </div>
            </div>
            <div class="login-field">
                <label for="region-user">Région</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-map-marker-alt"></i>
                    <select class="login-select" id="region-user" name="region-user">
                        <?php $q = db_select($con, "select * from region where 1", []);
                        while ($r = mysqli_fetch_array($q)):
                            echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                        endwhile;
                        ?>
                    </select>
                </div>
            </div>
            <button class="login-submit" type="button" id="btn-connect">
                <span>Se connecter</span>
                <i class="icon fas fa-arrow-right"></i>
            </button>
        </form>
        <div class="login-footer">Groupe NJS</div>
    </div>
</div>
<script>
    $('#btn-connect').click((e) => {
        var valid = true
        $('.login-input').each((e, el) => {
            $(el).removeClass('is-invalid').css({borderColor: '', boxShadow: ''})
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid').css({borderColor: '#ef4444', boxShadow: '0 0 0 3px rgba(239,68,68,.15)'})
            }
        })
        if (!valid) {
            showError("Tous les champs sont obligatoires!!")
            return false
        }
        $.ajax({
            type: 'post',
            data: $('form').serialize()
        }).done((e) => {
            let v = e.split("%%%%%%")[1]
            if (v == '1') {
                location = 'index.php'
            } else if (v == '0') {
                showError("Nom d'utilisateur ou mot de passe incorrect")
            } else if (v == '3') {
                showError("Vous n'avez pas le droit de vous connecter à cette région")
            } else {
                showError("Le mot de passe est incorrect!!")
            }
        })
    })
</script>