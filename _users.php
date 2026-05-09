<?php
function getTableauUsers()
{
    global $con;
    $repo = new UserRepository($con);
    $currentRole = $_SESSION['usr-con']['role'] ?? 'user';
    $isSuperadmin = $currentRole === 'superadmin';

    $roleFilter = $isSuperadmin ? null : 'user';
    $rows = $repo->findAll($roleFilter);

    $roleLabels = [
        'user' => 'Utilisateur',
        'admin' => 'Administrateur',
        'superadmin' => 'Super Admin',
    ];

    $tableau = "<table class='table table-striped responsive' id='table-users'><thead><tr>
        <th>#</th>
        <th>Nom d'utilisateur</th>
        <th>Nom complet</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Statut</th>
        <th></th>
    </tr></thead><tbody>";

    $i = 1;
    foreach ($rows as $r):
        $id = $r['id_user'];
        $role = $r['role'] ?? 'user';
        $roleLabel = $roleLabels[$role] ?? $role;
        $isActive = (int)($r['is_active'] ?? 1);
        $statusBadge = $isActive
            ? '<span class="badge bg-success">Actif</span>'
            : '<span class="badge bg-danger">Inactif</span>';

        $tableau .= "<tr>
            <td>$i</td>
            <td>" . h($r['name_user']) . "</td>
            <td>" . h($r['fullname_user'] ?? '') . "</td>
            <td>" . h($r['email_user']) . "</td>
            <td>$roleLabel</td>
            <td>$statusBadge</td>
            <td><div class='btn-group'>";

        // Edit button
        $tableau .= "<button class='btn btn-primary btn-sm' onclick='showModalUpdateUser($id)' title='Modifier'><i class='fa fa-pencil-alt'></i></button>";

        // Toggle active button (superadmins can never be deactivated)
        if ($role !== 'superadmin'):
        $toggleIcon = $isActive ? 'fa-toggle-off' : 'fa-toggle-on';
        $toggleTitle = $isActive ? 'Désactiver' : 'Activer';
        $tableau .= "<button class='btn btn-warning btn-sm' onclick='toggleUserActive($id, $isActive)' title='$toggleTitle'><i class='fa $toggleIcon'></i></button>";
        endif;

        // Delete button
        $tableau .= "<button class='btn btn-danger btn-sm' onclick='deleteUser($id)' title='Supprimer'><i class='fa fa-times'></i></button>";

        $tableau .= "</div></td></tr>";
        $i++;
    endforeach;

    $tableau .= "</tbody></table>";
    return $tableau;
}
?>

<div class="modal fade" id="modal-user" tabindex="-1" aria-labelledby="modal-user-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-user-label">Nouvel utilisateur</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-user" novalidate>
                    <input type="hidden" id="id-user" name="id-user">
                    <input type="hidden" id="is-active-user" name="is-active-user" value="1">

                    <div class="row">
                        <div class="form-floating mb-3 col-md-6">
                            <input type="text" class="form-control" required id="name-user" name="name-user">
                            <label for="name-user">Nom d'utilisateur</label>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                        <div class="form-floating mb-3 col-md-6">
                            <input type="email" class="form-control" required id="email-user" name="email-user">
                            <label for="email-user">E-mail</label>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-floating mb-3 col-md-6">
                            <input type="password" class="form-control" id="pass-user" name="pass-user" placeholder=" ">
                            <label for="pass-user">Mot de passe</label>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                        <div class="form-floating mb-3 col-md-6">
                            <input type="password" class="form-control" id="pass-user-confirm" name="pass-user-confirm" placeholder=" ">
                            <label for="pass-user-confirm">Confirmer le mot de passe</label>
                            <div class="invalid-feedback">Les mots de passe ne correspondent pas</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-floating mb-3 col-md-6">
                            <input type="text" class="form-control" id="fullname-user" name="fullname-user">
                            <label for="fullname-user">Nom complet</label>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="role-user">Rôle</label>
                            <select id="role-user" required name="role-user">
                                <option value="user">Utilisateur</option>
                                <?php if ($isSuperadmin): ?>
                                <option value="admin">Administrateur</option>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="mb-3 col-md-6">
                            <label for="region-user">Régions</label>
                            <select id="region-user" required name="region-user[]" multiple>
                                <?php
                                $regionRepo = new RegionRepository($con);
                                foreach ($regionRepo->findAll() as $r):
                                    echo "<option value='" . $r['id_region'] . "'>" . h($r['nom_region']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                        <div class="mb-3 col-md-6">
                            <label for="entite-user">Entités</label>
                            <select id="entite-user" required name="entite-user[]" multiple>
                                <?php
                                $entiteRepo = new EntiteRepository($con);
                                foreach ($entiteRepo->findAll() as $e):
                                    echo "<option value='" . $e['id_entite'] . "'>" . h($e['nom_entite']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                            <div class="invalid-feedback">Ce champ est obligatoire</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Droits d'accès</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered no-datatable" id="rights-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th class="text-center">Voir</th>
                                        <th class="text-center">Ajouter</th>
                                        <th class="text-center">Modifier</th>
                                        <th class="text-center">Supprimer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $knownObjects = [
                                        'vehicules' => 'Véhicules',
                                        'voyages' => 'Voyages',
                                        'affectationVehicules' => 'Affectations',
                                        'maintenances' => 'Maintenance',
                                        'users' => 'Utilisateurs',
                                        'config' => 'Configuration',
                                        'report' => 'Rapports',
                                    ];
                                    $permKeys = ['view', 'save', 'upd', 'del'];
                                    foreach ($knownObjects as $objKey => $objLabel): ?>
                                    <tr>
                                        <td><?php echo h($objLabel); ?></td>
                                        <?php foreach ($permKeys as $pk): ?>
                                        <td class="text-center">
                                            <input type="checkbox" class="right-cb form-check-input"
                                                   data-object="<?php echo $objKey; ?>"
                                                   data-perm="<?php echo $pk; ?>"
                                                   value="<?php echo $pk; ?>">
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
// TomSelect instances — lazy-initialized on first modal open
var regionSelect, entiteSelect;
var tomSelectsReady = false;

function bindSelectAll(ts) {
    ts.on('dropdown_open', function() {
        var $dropdown = $(ts.dropdown);
        if (!$dropdown.length || $dropdown.children('.ts-select-all').length) return;
        $dropdown.prepend('<div class="ts-select-all"><a href="#" class="select-all-link">Tout sélectionner</a> &middot; <a href="#" class="deselect-all-link">Tout désélectionner</a></div>');
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

function initTomSelects() {
    if (regionSelect) regionSelect.destroy();
    if (entiteSelect) entiteSelect.destroy();

    regionSelect = new TomSelect('#region-user', {
        plugins: ['remove_button'],
        maxItems: null,
        placeholder: 'Sélectionner les régions...'
    });
    bindSelectAll(regionSelect);

    entiteSelect = new TomSelect('#entite-user', {
        plugins: ['remove_button'],
        maxItems: null,
        placeholder: 'Sélectionner les entités...'
    });
    bindSelectAll(entiteSelect);
}

function ensureTomSelects() {
    if (!tomSelectsReady) {
        tomSelectsReady = true;
        initTomSelects();
    }
}

$('#modal-user').on('show.bs.modal', function() {
    ensureTomSelects();
});

$('#modal-user').on('shown.bs.modal', function() {
    if (regionSelect) regionSelect.sync();
    if (entiteSelect) entiteSelect.sync();
});

function openModalUser() {
    ensureTomSelects();
    $('#form-user')[0].reset();
    $('#id-user').val('');
    $('#is-active-user').val('1');
    $('#modal-user-label').text('Nouvel utilisateur');
    $('#pass-user, #pass-user-confirm').prop('required', true);
    $('#pass-user, #pass-user-confirm').attr('placeholder', ' ');
    <?php if (!$isSuperadmin): ?>
    $('#role-user').val('user');
    <?php endif; ?>
    clearRights();
    $('#form-user .is-invalid').removeClass('is-invalid');
    if (regionSelect) { regionSelect.clear(); regionSelect.sync(); }
    if (entiteSelect) { entiteSelect.clear(); entiteSelect.sync(); }
    $('#modal-user').modal('show');
}

function showModalUpdateUser(id) {
    $('#modal-user-label').text('Modifier l\'utilisateur');
    $('#pass-user, #pass-user-confirm').prop('required', false);
    $('#pass-user, #pass-user-confirm').attr('placeholder', 'Laisser vide pour ne pas changer');
    $('#id-user').val(id);
    $('#form-user .is-invalid').removeClass('is-invalid');

    $.ajax({
        type: 'post',
        data: 'id-user-forModal=' + id,
        dataType: 'json'
    }).done(function(e) {
        if (e.success) {
            var v = e.data;
            $('#name-user').val(v.name_user);
            $('#email-user').val(v.email_user || '');
            $('#fullname-user').val(v.fullname_user || '');
            $('#role-user').val(v.role || 'user');
            $('#is-active-user').val(v.is_active);
            $('#pass-user').val('');
            $('#pass-user-confirm').val('');

            ensureTomSelects();
            if (regionSelect) { regionSelect.clear(); regionSelect.sync(); }
            if (entiteSelect) { entiteSelect.clear(); entiteSelect.sync(); }

            if (v.regions && v.regions.length) {
                regionSelect.setValue(v.regions.map(String));
            }
            if (v.entities && v.entities.length) {
                entiteSelect.setValue(v.entities.map(String));
            }

            clearRights();
            if (v.rights) {
                Object.keys(v.rights).forEach(function(obj) {
                    var perms = v.rights[obj].split(',');
                    perms.forEach(function(p) {
                        $('.right-cb[data-object="' + obj + '"][data-perm="' + p.trim() + '"]').prop('checked', true);
                    });
                });
            }

            $('#modal-user').modal('show');
        } else {
            showError(e.error || 'Erreur lors du chargement');
        }
    }).fail(function(jqXHR) {
        showError(jqXHR.responseJSON?.error || 'Erreur lors du chargement');
    });
}

function clearRights() {
    $('.right-cb').prop('checked', false);
}

function buildRightsJson() {
    var rights = {};
    $('.right-cb:checked').each(function() {
        var obj = $(this).data('object');
        var perm = $(this).data('perm');
        if (!rights[obj]) rights[obj] = [];
        if (rights[obj].indexOf(perm) === -1) rights[obj].push(perm);
    });
    return JSON.stringify(rights);
}

function saveUser() {
    $('#form-user .is-invalid').removeClass('is-invalid');
    if (regionSelect) regionSelect.wrapper.classList.remove('is-invalid');
    if (entiteSelect) entiteSelect.wrapper.classList.remove('is-invalid');

    var valid = true;

    $('#form-user [required]').each(function() {
        if ($(this).is('#region-user') || $(this).is('#entite-user')) return;
        if ($(this).val() === '') {
            valid = false;
            $(this).addClass('is-invalid');
        }
    });

    if (regionSelect && regionSelect.getValue().length === 0) {
        valid = false;
        regionSelect.wrapper.classList.add('is-invalid');
    }
    if (entiteSelect && entiteSelect.getValue().length === 0) {
        valid = false;
        entiteSelect.wrapper.classList.add('is-invalid');
    }

    var pwd = $('#pass-user').val();
    var confirm = $('#pass-user-confirm').val();
    if (pwd !== confirm) {
        showError('Les mots de passe ne correspondent pas');
        $('#pass-user, #pass-user-confirm').addClass('is-invalid');
        return;
    }

    if (!valid) {
        showError('Tous les champs obligatoires en rouge doivent être remplis');
        return;
    }

    var isUpdate = $('#id-user').val() !== '';
    var data = $('#form-user').serialize() + '&rights-json=' + encodeURIComponent(buildRightsJson());
    data += isUpdate ? '&id-user-upd=1' : '&new-user=1';

    $.ajax({
        type: 'post',
        data: data,
        dataType: 'json'
    }).done(function(e) {
        if (e.success) {
            showSuccess(isUpdate ? 'Utilisateur modifié!' : 'Utilisateur créé!');
            $('#modal-user').modal('hide');
            location.reload();
        } else {
            showError(e.error || 'Erreur lors de l\'enregistrement');
        }
    }).fail(function(jqXHR) {
        showError(jqXHR.responseJSON?.error || 'Erreur lors de l\'enregistrement');
    });
}

function deleteUser(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        $.ajax({
            type: 'post',
            data: 'id-user-forDel=' + id,
            dataType: 'json'
        }).done(function(e) {
            if (e.success) {
                showSuccess('Utilisateur supprimé');
                location.reload();
            } else {
                showError(e.error || 'Échec de la suppression');
            }
        }).fail(function(jqXHR) {
            showError(jqXHR.responseJSON?.error || 'Échec de la suppression');
        });
    }
}

function toggleUserActive(id, current) {
    var newVal = current ? 0 : 1;
    var action = newVal ? 'activer' : 'désactiver';
    if (confirm('Êtes-vous sûr de vouloir ' + action + ' cet utilisateur ?')) {
        $.ajax({
            type: 'post',
            data: 'id-user-active=' + id + '&val-user-active=' + newVal,
            dataType: 'json'
        }).done(function(e) {
            if (e.success) {
                showSuccess('Statut modifié');
                location.reload();
            } else {
                showError(e.error || 'Erreur');
            }
        }).fail(function(jqXHR) {
            showError(jqXHR.responseJSON?.error || 'Erreur');
        });
    }
}
</script>
