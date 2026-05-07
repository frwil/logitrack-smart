<?php
if (isset($_POST['name-user'])):
    $q = mysqli_query($con, "select * from users where name_user='{$_POST['name-user']}'");
    while ($r = mysqli_fetch_array($q)):
        $user = $r;
    endwhile;
    if (mysqli_num_rows($q) > 0):
        $regions = explode(',', $user['users_region']);
        $q=mysqli_query($con,"select * from region where sha1(concat(id_region,nom_region))='{$_POST['region-user']}'");
        while($r=mysqli_fetch_array($q)) $region=$r;
        $trouve=false;
        for($i=0;$i<count($regions);$i++){
            if(sha1($regions[$i].$region[1])==$_POST['region-user']) $trouve=true;
        }
        if ($trouve):
            $user['region-sel']=$region[0];
            $user['region-sel-name']=$region[1];
            $user['region-sel-admin']=$region['is_admin'];
            if (password_verify($_POST['pass-user'], $user['pass_user'])) {
                $q = mysqli_query($con, "select * from users_rights where id_user={$user['id_user']}");
                $rights = array();
                while ($r = mysqli_fetch_array($q)) :
                    array_push($rights, $r);
                endwhile;
                $user['users-rights'] = $rights;
                $_SESSION['usr-con'] = $user;
                die("%%%%%%1");
            } else {
                die("%%%%%%2");
            }
        else :
            die("%%%%%%3");
        endif;
    else:
        die("%%%%%%0");
    endif;
endif;
?>
<link rel="stylesheet" href="css/style.css">
<div class="container">
    <div class="screen">
        <div class="screen__content">
            <form class="login" action="#" method="post">
                <div class="login__field">
                    <i class="login__icon fas fa-user"></i>
                    <input type="text" class="login__input" placeholder="User name / Email" id="name-user" name="name-user">
                </div>
                <div class="login__field">
                    <i class="login__icon fas fa-lock"></i>
                    <input type="password" class="login__input" placeholder="Password" id="pass-user" name="pass-user">
                </div>
                <div class="login__field">
                    <label for="region-user">Région</label>
                    <select class="login__select" placeholder="Region" id="region-user" name="region-user">
                        <?php $q = mysqli_query($con, "select * from region where 1");
                        while ($r = mysqli_fetch_array($q)):
                            echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                        endwhile;
                        ?>
                    </select>
                </div>
                <button class="button login__submit" type="button" id="btn-connect">
                    <span class="button__text">Se Connecter</span>
                    <i class="button__icon fas fa-chevron-right"></i>
                </button>
            </form>
            <div class="social-login" style="top:90%">
                <h3 class="h6">Groupe NJS</h3>
                <div class="social-icons">

                </div>
            </div>
        </div>
        <div class="screen__background">
            <span class="screen__background__shape screen__background__shape4"></span>
            <span class="screen__background__shape screen__background__shape3"></span>
            <span class="screen__background__shape screen__background__shape2"></span>
            <span class="screen__background__shape screen__background__shape1"></span>
        </div>
    </div>
</div>
<script>
    $('#btn-connect').click((e) => {
        var valid = true
        $('form.login input:not(.button__text)').each((e, el) => {
            $(el).removeClass('is-invalid text-danger')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid text-danger')
            }
        })
        if (!valid) {
            $.notify("Tous les champs sont obligatoires!!")
            return false
        }
        $.ajax({
            type: 'post',
            data: $('form.login').serialize()
        }).done((e) => {
            let v = e.split("%%%%%%")[1]
            if (v == '1') {
                location = 'index.php'
            } else if (v == '0') {
                $.notify("Cet utilisateur n'existe pas")
            } else if (v == '3') {
                $.notify("Vous n'avez pas le droit de vous connecter à cette région")
            } else {
                $.notify("Le mot de passe est incorrect!!")
            }
        })
    })
</script>