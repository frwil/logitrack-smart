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
        <form action="index.php" method="post" id="login-form">
            <div class="login-error" id="login-error"></div>
            <div class="login-field">
                <label for="name-user">Identifiant</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-user"></i>
                    <input type="text" class="login-input" placeholder="Nom d'utilisateur ou email" id="name-user" name="name-user" autocomplete="username">
                </div>
            </div>
            <div class="login-field">
                <label for="pass-user">Mot de passe</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-lock"></i>
                    <input type="password" class="login-input" placeholder="••••••••" id="pass-user" name="pass-user" autocomplete="current-password">
                </div>
            </div>
            <div class="login-field">
                <label for="region-user">Région</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-map-marker-alt"></i>
                    <select class="login-select" id="region-user" name="region-user">
                        <?php $regionRepo = new RegionRepository($con);
                        foreach ($regionRepo->findAll() as $r):
                            echo "<option value='" . $r['id_region'] . "'>{$r['nom_region']}</option>";
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>
            <button class="login-submit" type="submit" id="btn-connect">
                <span id="btn-connect-text">Se connecter</span>
                <i class="icon fas fa-arrow-right"></i>
            </button>
        </form>
        <div class="login-footer">Groupe NJS</div>
    </div>
</div>
<script>
    function showLoginError(msg) {
        var $el = $('#login-error');
        $el.text(msg).addClass('show');
        if (typeof showError === 'function') showError(msg);
    }

    function clearLoginError() {
        $('#login-error').removeClass('show').text('');
    }

    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        clearLoginError();

        var $inputs = $('.login-input');
        var valid = true;
        $inputs.each(function() {
            $(this).removeClass('is-invalid').css({borderColor: '', boxShadow: ''});
            if ($(this).val() === '') {
                valid = false;
                $(this).addClass('is-invalid').css({borderColor: '#ef4444', boxShadow: '0 0 0 3px rgba(239,68,68,.15)'});
            }
        });

        if (!valid) {
            showLoginError("Tous les champs sont obligatoires");
            return;
        }

        var $btn = $('#btn-connect');
        $btn.prop('disabled', true);
        $('#btn-connect-text').text('Connexion...');

        $.ajax({
            type: 'post',
            url: 'index.php',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(resp) {
            if (resp.success) {
                window.location = 'index.php';
            } else {
                showLoginError(resp.error || "Erreur d'authentification");
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            var msg;
            try {
                var r = JSON.parse(jqXHR.responseText);
                msg = r.error || r.message || JSON.stringify(r);
            } catch (_) {
                var preview = (jqXHR.responseText || '').substring(0, 300);
                msg = 'HTTP ' + jqXHR.status + ' ' + errorThrown + (preview ? ' — ' + preview : '');
            }
            showLoginError(msg);
        }).always(function() {
            $btn.prop('disabled', false);
            $('#btn-connect-text').text('Se connecter');
        });
    });
</script>