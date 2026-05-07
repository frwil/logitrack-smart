<?php if(isset($_POST['name-user'])) : 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_begin_transaction($con);
try{
    $q=mysqli_query($con,"INSERT INTO `users` (`id_user`, `name_user`, `pass_user`, `fullname_user`, `email_user`) VALUES (NULL, '{$_POST['name-user']}', '".password_hash($_POST['pass-user'],PASSWORD_DEFAULT)."', ".($_POST['fullname-user']=='' ? 'NULL' : "'{$_POST['fullname-user']}'").", '{$_POST['email-user']}')");
    $q=mysqli_query($con,"INSERT INTO `users_region` (`id_user_region`, `id_user`, `id_region`, `is_active`) VALUES (NULL, (select id_user from users where name_user='{$_POST['name-user']}'), {$_POST['region-user']}, '1')");
    mysqli_commit($con);
    die("%%%%%%1");
}catch(mysqli_sql_exception $e){
    mysqli_rollback($con);
    die("%%%%%%0".$e->getMessage());
}
endif;
?>
<div class="container container-fluid" style="display: flex;justify-content: center;padding:80px">
    <div class="main border row" style="width:50%;padding:80px"> 
        <form method="post" action="#" id="form-user-reg" class="col">
            <div class="form-floating mb-3">
                <input type="text" id="name-user" name="name-user" class="form-control" required>
                <label for="name-user">Nom Utilisateur</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" id="pass-user" name="pass-user" class="form-control" required>
                <label for="pass-user">Mot de passe</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" id="pass-user-confirm" name="pass-user-confirm" class="form-control" required>
                <label for="pass-user-confirm">Confirmation</label>
            </div>
            <div class="form-floating mb-3">
                <input type="email" id="email-user" name="email-user" class="form-control" required>
                <label for="email-user">E-mail</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="fullname-user" name="fullname-user" class="form-control">
                <label for="fullname-user">Nom Complet</label>
            </div>
            <div class="form-floating mb-3">
                <select id="region-user" name="region-user" class="form-select" required>
                    <?php $q=mysqli_query($con,"select * from region where is_active=1"); 
                    while($r=mysqli_fetch_array($q)):
                        echo "<option value='{$r[0]}'>{$r[1]}</option>";
                    endwhile;
                    ?>
                </select>
                <label for="region-user">Région</label>
            </div>
            <div class="text-end" style="width:100%">
                <button class="btn btn-primary" id="btn-save-user" type="button">Enregistrer</button>
            </div>
        </form>
    </div>
</div>
<script>
    $('#btn-save-user').click((e)=>{
        var valid=true
        $('form *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        $('#pass-user-confirm').removeClass('is-invalid')
        if($('#pass-user').val()!=$('#pass-user-confirm').val()){
            valid=false
            $.notify("Le mot de passe doit être identique à la confirmation!")
            $('#pass-user-confirm').addClass('is-invalid')
            return false
        }
        $('#name-user').removeClass('is-invalid')
        if($('#name-user').val().trim().split(" ").length>1){
            $.notify("Le nom d'utilisateur doit être en un seul mot")
            valid=false
            $('#name-user').addClass('is-invalid')
            return false
        }
        if(!valid){
            $.notify("Tous les champs en rouge sont obligatoires!")
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-user-reg').serialize()
        }).done((e)=>{
            let v=e.split('%%%%%%')[1]
            if(v=='1'){
                $.notify("Enregistrement effectué!",{
                    className:'success'
                })
                location.reload()
            }else{
                $.notify("Erreur lors de l'enregistrement !!!")
            }
        })
    })
</script>