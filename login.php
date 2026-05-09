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
            <input type="hidden" name="login" value="1">
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
                <label for="region-user">Région(s)</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-map-marker-alt"></i>
                    <select class="login-select login-multi" id="region-user" name="region-user[]" multiple>
                        <?php $regionRepo = new RegionRepository($con);
                        foreach ($regionRepo->findAll() as $r):
                            echo "<option value='" . $r['id_region'] . "'>{$r['nom_region']}</option>";
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>
            <div class="login-field">
                <label for="entite-user">Entité(s)</label>
                <div class="login-input-wrapper">
                    <i class="icon fas fa-building"></i>
                    <select class="login-select login-multi" id="entite-user" name="entite-user[]" multiple>
                        <?php $entiteRepo = new EntiteRepository($con);
                        foreach ($entiteRepo->findAll() as $e):
                            echo "<option value='" . $e['id_entite'] . "'>" . h($e['nom_entite']) . "</option>";
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

    function invalidateField($el) {
        $el.addClass('is-invalid').css({borderColor: '#ef4444', boxShadow: '0 0 0 3px rgba(239,68,68,.15)'});
        var $ts = $el.next('.ts-wrapper');
        if ($ts.length) $ts.find('.ts-control').css({borderColor: '#ef4444', boxShadow: '0 0 0 3px rgba(239,68,68,.15)'});
    }

    function clearFieldError($el) {
        $el.removeClass('is-invalid').css({borderColor: '', boxShadow: ''});
        var $ts = $el.next('.ts-wrapper');
        if ($ts.length) $ts.find('.ts-control').css({borderColor: '', boxShadow: ''});
    }

    // Shared dropdown template with select-all / deselect-all links
    function makeSelectDropdown() {
        return '<div class="ts-dropdown-content"><div class="ts-select-all"><a href="#" class="select-all-link">Tout selectionner</a> &middot; <a href="#" class="deselect-all-link">Tout desélectionner</a></div></div>';
    }

    function bindSelectAll(ts) {
        ts.on('dropdown_open', function() {
            var $dd = $(ts.dropdown_content);
            $dd.find('.select-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                ts.setValue(Object.keys(ts.options).map(function(k) { return ts.options[k].value; }));
            });
            $dd.find('.deselect-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                ts.clear();
            });
        });
    }

    // Initialize Tom Select on multi-select fields
    var regionSelect = new TomSelect('#region-user', {
        plugins: ['remove_button'],
        maxItems: null,
        placeholder: 'Selectionner une ou plusieurs regions...',
        render: { dropdown: makeSelectDropdown }
    });
    bindSelectAll(regionSelect);

    var entiteSelect = new TomSelect('#entite-user', {
        plugins: ['remove_button'],
        maxItems: null,
        placeholder: 'Selectionner une ou plusieurs entites...',
        render: { dropdown: makeSelectDropdown }
    });
    bindSelectAll(entiteSelect);

    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        clearLoginError();

        var valid = true;

        // Validate text inputs
        $('.login-input').each(function() {
            var v = $(this).val();
            clearFieldError($(this));
            if (v === '' || v === null) {
                valid = false;
                invalidateField($(this));
            }
        });

        // Validate Tom Select fields
        if (regionSelect.getValue().length === 0) {
            valid = false;
            invalidateField($('#region-user'));
        } else {
            clearFieldError($('#region-user'));
        }
        if (entiteSelect.getValue().length === 0) {
            valid = false;
            invalidateField($('#entite-user'));
        } else {
            clearFieldError($('#entite-user'));
        }

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
