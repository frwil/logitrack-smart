<?php /* POST /login handled by AuthController — see controllers/router.php */ ?>
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
                        <?php $regionRepo = new RegionRepository($con);
                        foreach ($regionRepo->findAll() as $r):
                            echo "<option value='" . sha1($r['id_region'] . $r['nom_region']) . "'>{$r['nom_region']}</option>";
                        endforeach;
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
            data: $('form').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                location = 'index.php'
            } else {
                showError(e.error||"Erreur d'authentification")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error||"Erreur de connexion")
        })
    })
</script>