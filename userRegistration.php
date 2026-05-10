<?php
if (isset($_POST['name-user'])) {
    try {
        $userRepo = new UserRepository($con);
        $userRepo->transactional(function () use ($userRepo) {
            $passHashed = password_hash($_POST['pass-user'], PASSWORD_DEFAULT);
            $userRepo->insertUser(
                $_POST['name-user'],
                $passHashed,
                $_POST['fullname-user'] === '' ? null : $_POST['fullname-user'],
                $_POST['email-user'] === '' ? null : $_POST['email-user']
            );
            $userRepo->insertUserRegion($_POST['name-user'], (int)$_POST['region-user']);
        });
        die(json_encode(['success' => true]));
    } catch (\mysqli_sql_exception $e) {
        error_log("User registration failed: " . $e->getMessage());
        die(json_encode(['success' => false, 'error' => "Erreur lors de l'enregistrement"]));
    } catch (\Throwable $e) {
        error_log("User registration failed: " . $e->getMessage());
        die(json_encode(['success' => false, 'error' => "Erreur lors de l'enregistrement"]));
    }
}
?>
<div class="d-flex justify-content-center">
    <div class="lt-card" style="max-width:500px;width:100%">
        <div class="lt-card-title mb-3">Nouvel utilisateur</div>
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
                <div class="mb-3">

                    <label for="region-user">Région</label>

                    <select id="region-user" name="region-user" required>
                        <?php $regionRepo = new RegionRepository($con);
                        foreach ($regionRepo->findActive() as $r):
                            echo "<option value='" . h($r['id_region']) . "'>" . h($r['nom_region']) . "</option>";
                        endforeach;
                        ?>
                    </select>

                </div>
                <div class="text-end">
                    <button class="btn btn-primary" id="btn-save-user" type="button">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
<script>
    $(document).ready(function() {
        initTomSelect('#region-user');
    });

    $('#btn-save-user').click((e)=>{
        var valid=true
        $('form *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        $('#pass-user-confirm').removeClass('is-invalid')
        if($('#pass-user').val()!=$('#pass-user-confirm').val()){
            valid=false
            showError("Le mot de passe doit être identique à la confirmation!")
            $('#pass-user-confirm').addClass('is-invalid')
            return false
        }
        $('#name-user').removeClass('is-invalid')
        if($('#name-user').val().trim().split(" ").length>1){
            showError("Le nom d'utilisateur doit être en un seul mot")
            valid=false
            $('#name-user').addClass('is-invalid')
            return false
        }
        if(!valid){
            showError("Tous les champs en rouge sont obligatoires!")
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-user-reg').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess("Enregistrement effectué!")
                location.reload()
            }else{
                showError(e.error || "Erreur lors de l'enregistrement !!!")
            }
        }).fail((jqXHR)=>{
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    })
</script>