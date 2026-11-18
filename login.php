<?php /* POST /login et /login-context gérés par AuthController — voir controllers/router.php */ ?>
<?php
// Connexion en 2 étapes :
//  - écran 1 : identifiant + mot de passe ;
//  - écran 2 (après authentification) : choix des régions/entités autorisées.
// Si une étape 1 vient de réussir (pending-login), l'écran 2 est pré-rendu
// avec les régions/entités de l'utilisateur (résiste à un F5).
$pending = $_SESSION['pending-login'] ?? null;
$pendingOk = $pending && (time() - (int)$pending['ts'] <= 900);
$pendingRegions = $pendingOk ? $pending['regions'] : [];
$pendingEntites = $pendingOk ? $pending['entites'] : [];
$pendingName = '';
if ($pendingOk) {
    $pendingName = trim($pending['user']['fullname_user'] ?? '') !== ''
        ? $pending['user']['fullname_user']
        : ($pending['user']['name_user'] ?? '');
}
?>
<link rel="stylesheet" href="css/style.css">
<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">
                <i class="fa fa-truck"></i>
            </div>
            <h2>LogiTrack</h2>
            <p>Gestion de flotte</p>
        </div>

        <!-- Écran 1 : identifiant + mot de passe -->
        <div id="login-screen-1" style="<?= $pendingOk ? 'display:none' : '' ?>">
            <form action="index.php" method="post" id="login-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
                <button class="login-submit" type="submit" id="btn-connect">
                    <span id="btn-connect-text">Continuer</span>
                    <i class="icon fas fa-arrow-right"></i>
                </button>
            </form>
        </div>

        <!-- Écran 2 : choix des régions et entités (après authentification) -->
        <div id="login-screen-2" style="<?= $pendingOk ? '' : 'display:none' ?>">
            <form action="index.php" method="post" id="login-context-form">
                <input type="hidden" name="csrf_token" id="context-csrf" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="login-context" value="1">
                <div class="login-error" id="login-context-error"></div>
                <div class="login-context-user" id="login-context-user" style="text-align:center;margin-bottom:14px;font-weight:600"><?= h($pendingName) ?></div>
                <p class="login-context-hint" style="text-align:center;color:#6b7280;font-size:.85rem;margin-bottom:14px">Sélectionnez vos régions et entités</p>
                <div class="login-field">
                    <label for="region-user">Région(s)</label>
                    <div class="login-input-wrapper">
                        <i class="icon fas fa-map-marker-alt"></i>
                        <select class="login-select login-multi" id="region-user" name="region-user[]" multiple>
                            <?php foreach ($pendingRegions as $r): ?>
                                <option value="<?= (int)$r['id_region'] ?>"><?= h($r['nom_region']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="login-field">
                    <label for="entite-user">Entité(s)</label>
                    <div class="login-input-wrapper">
                        <i class="icon fas fa-building"></i>
                        <select class="login-select login-multi" id="entite-user" name="entite-user[]" multiple>
                            <?php foreach ($pendingEntites as $e): ?>
                                <option value="<?= (int)$e['id_entite'] ?>"><?= h($e['nom_entite']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:6px">
                    <button class="login-submit" type="button" id="btn-back-login" style="flex:0 0 auto;background:#e5e7eb;color:#374151">
                        <i class="icon fas fa-arrow-left"></i>
                    </button>
                    <button class="login-submit" type="submit" id="btn-context-submit" style="flex:1">
                        <span id="btn-context-submit-text">Se connecter</span>
                        <i class="icon fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="login-footer">LogiTrack</div>
    </div>
</div>
<script>
    function showLoginError(msg, target) {
        var $el = $(target || '#login-error');
        $el.text(msg).addClass('show');
        if (typeof showError === 'function') showError(msg);
    }

    function clearLoginError(target) {
        $(target || '#login-error').removeClass('show').text('');
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

    function bindSelectAll(ts) {
        ts.on('dropdown_open', function() {
            var $dropdown = $(ts.dropdown);
            if (!$dropdown.length || $dropdown.children('.ts-select-all').length) return;
            $dropdown.prepend('<div class="ts-select-all"><a href="#" class="select-all-link">Tout selectionner</a> &middot; <a href="#" class="deselect-all-link">Tout desélectionner</a></div>');
            $dropdown.find('.select-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                ts.setValue(Object.keys(ts.options).map(function(k) { return ts.options[k].value; }));
            });
            $dropdown.find('.deselect-all-link').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                ts.clear();
            });
        });
    }

    // Initialise les TomSelect de l'écran 2 avec les options fournies (serveur ou réponse AJAX).
    function initContextSelects(regions, entites) {
        if (regions && regions.length) {
            var $r = $('#region-user');
            if (!$r.find('option').length) {
                regions.forEach(function(o) { $r.append('<option value="' + o.id + '">' + $('<div>').text(o.nom).html() + '</option>'); });
            }
        }
        if (entites && entites.length) {
            var $e = $('#entite-user');
            if (!$e.find('option').length) {
                entites.forEach(function(o) { $e.append('<option value="' + o.id + '">' + $('<div>').text(o.nom).html() + '</option>'); });
            }
        }
        if (window._tsRegion) { try { window._tsRegion.destroy(); } catch(e) {} window._tsRegion = null; }
        if (window._tsEntite) { try { window._tsEntite.destroy(); } catch(e) {} window._tsEntite = null; }
        window._tsRegion = new TomSelect('#region-user', {
            plugins: ['remove_button'],
            maxItems: null,
            placeholder: 'Selectionner une ou plusieurs regions...'
        });
        bindSelectAll(window._tsRegion);
        window._tsEntite = new TomSelect('#entite-user', {
            plugins: ['remove_button'],
            maxItems: null,
            placeholder: 'Selectionner une ou plusieurs entites...'
        });
        bindSelectAll(window._tsEntite);
    }

    function showScreen2() {
        $('#login-screen-1').hide();
        $('#login-screen-2').show();
    }

    function showScreen1() {
        $('#login-screen-1').show();
        $('#login-screen-2').hide();
    }

    // Écran 1 : authentification (identifiant + mot de passe uniquement).
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        clearLoginError('#login-error');

        var valid = true;
        $('.login-input', this).each(function() {
            var v = $(this).val();
            clearFieldError($(this));
            if (v === '' || v === null) {
                valid = false;
                invalidateField($(this));
            }
        });

        if (!valid) {
            showLoginError("Identifiant et mot de passe sont obligatoires", '#login-error');
            return;
        }

        var $btn = $('#btn-connect');
        $btn.prop('disabled', true);
        $('#btn-connect-text').text('Vérification...');

        $.ajax({
            type: 'post',
            url: 'index.php',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(resp) {
            if (resp.success) {
                // Authentifié : passage à l'écran 2 avec les régions/entités autorisées.
                // Le token CSRF de session a été régénéré : resynchroniser les deux formulaires.
                $('#context-csrf').val(resp.csrf_token);
                $('#login-form input[name="csrf_token"]').val(resp.csrf_token);
                $('#login-context-user').text(resp.display_name || '');
                initContextSelects(resp.regions || [], resp.entites || []);
                clearLoginError('#login-context-error');
                showScreen2();
            } else {
                showLoginError(resp.error || "Erreur d'authentification", '#login-error');
            }
        }).fail(function(jqXHR) {
            var msg;
            try {
                var r = JSON.parse(jqXHR.responseText);
                msg = r.error || r.message || JSON.stringify(r);
            } catch (_) {
                msg = 'HTTP ' + jqXHR.status;
            }
            showLoginError(msg, '#login-error');
        }).always(function() {
            $btn.prop('disabled', false);
            $('#btn-connect-text').text('Continuer');
        });
    });

    // Écran 2 : choix des régions/entités puis finalisation de la connexion.
    $('#login-context-form').on('submit', function(e) {
        e.preventDefault();
        clearLoginError('#login-context-error');

        if (!window._tsRegion || window._tsRegion.getValue().length === 0) {
            invalidateField($('#region-user'));
            showLoginError("Sélectionnez au moins une région", '#login-context-error');
            return;
        }
        clearFieldError($('#region-user'));

        var $btn = $('#btn-context-submit');
        $btn.prop('disabled', true);
        $('#btn-context-submit-text').text('Connexion...');

        $.ajax({
            type: 'post',
            url: 'index.php',
            data: $(this).serialize(),
            dataType: 'json'
        }).done(function(resp) {
            if (resp.success) {
                window.location = 'index.php';
            } else {
                showLoginError(resp.error || "Erreur lors de la connexion", '#login-context-error');
            }
        }).fail(function(jqXHR) {
            var msg;
            try {
                var r = JSON.parse(jqXHR.responseText);
                msg = r.error || r.message || JSON.stringify(r);
            } catch (_) {
                msg = 'HTTP ' + jqXHR.status;
            }
            if (jqXHR.status === 401) {
                // Session de connexion expirée : retour à l'écran 1.
                showScreen1();
            }
            showLoginError(msg, '#login-error');
        }).always(function() {
            $btn.prop('disabled', false);
            $('#btn-context-submit-text').text('Se connecter');
        });
    });

    $('#btn-back-login').on('click', function() {
        clearLoginError('#login-error');
        showScreen1();
    });

    // Page rechargée après l'étape 1 (pending-login encore valide) : afficher l'écran 2.
    <?php if ($pendingOk): ?>
    $(function() {
        initContextSelects([], []);
        $('#login-context-user').text(<?= j($pendingName) ?>);
    });
    <?php endif; ?>
</script>
