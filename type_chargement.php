<?php function getTableauTypesChargement()
{
    global $con;
    global $rights_voyage;
    $repo = new TypeChargementRepository($con);
    $rows = $repo->findAll();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Libellé</th><th>Unité</th><th>Val. min</th><th>Val. max</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['lib_type_chargement']) . "</td><td>" . h($r['unite_mesure']) . "</td><td>" . h($r['valeur_min']) . "</td><td>" . h($r['valeur_max']) . "</td><td><div class='btn-group'>";
        if (in_array('upd', $rights_voyage)):
            $tableau .= "<button class='btn btn-secondary' title='Modifier' onclick='showModalUpdateTypeChargement(\"".$r['id_type_chargement']."\")'><i class='fa fa-pencil-alt'></i></button>";
        endif;
        if (in_array('del', $rights_voyage)):
            $tableau .= "<button class='btn btn-danger' title='Supprimer' onclick='delTypeChargement(\"".$r['id_type_chargement']."\")'><i class='fa fa-times'></i></button>";
        endif;
        $tableau .= "</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php if (isset($_GET['action']) && $_GET['action'] == 'new'): ?>
<script>
    setTimeout(() => { openModalTypeChargement() }, 2000)
</script>
<?php endif; ?>
<script>
    function openModalTypeChargement() { $('#modal-new-typechargement').modal('show') }

    function saveTypeChargement() {
        $('#form-new-typechargement').find('button[type=submit]').prop('disabled', true)
        $.ajax({
            type: 'post',
            data: $('#form-new-typechargement').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Type enregistré!!')
                location = "?page=voyages&subpage=listeTypesChargement"
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        }).always(() => {
            $('#form-new-typechargement').find('button[type=submit]').prop('disabled', false)
        })
    }

    function showModalUpdateTypeChargement(id) {
        $('#modal-upd-typechargement').modal('show')
        $('#id-typechargement').val(id)
        $.ajax({
            type: 'post',
            data: 'id-typechargement-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                let v = e.data
                $('#nom-typechargement-display').html(v.lib_type_chargement)
                $('#lib-typechargement-upd').val(v.lib_type_chargement)
                $('#unite-typechargement-upd').val(v.unite_mesure)
                $('#valmin-typechargement-upd').val(v.valeur_min)
                $('#valmax-typechargement-upd').val(v.valeur_max)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function updateTypeChargement() {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-typechargement').serialize(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location = "?page=voyages&subpage=listeTypesChargement"
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function delTypeChargement(id) {
        if (confirm("Etes-vous sûr de vouloir supprimer?")) {
            $.ajax({
                type: 'post',
                data: 'id-typechargement-forDel=' + id,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Type supprimé!!')
                    location.reload()
                } else {
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }
</script>

<!-- Modal New Type Chargement -->
<div class="modal fade" id="modal-new-typechargement" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Nouveau type de chargement</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-typechargement">
                    <div class="form-floating mb-3">
                        <input type="text" id="lib-typechargement" name="lib-typechargement" required class="form-control">
                        <label for="lib-typechargement">Libellé</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" id="unite-typechargement" name="unite-typechargement" class="form-control" placeholder="ex: tonnes, caisses, m³">
                        <label for="unite-typechargement">Unité de mesure</label>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" id="valmin-typechargement" name="valmin-typechargement" value="0" min="0" step="0.01" class="form-control">
                                <label for="valmin-typechargement">Valeur min</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" id="valmax-typechargement" name="valmax-typechargement" value="0" min="0" step="0.01" class="form-control">
                                <label for="valmax-typechargement">Valeur max</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveTypeChargement()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Update Type Chargement -->
<div class="modal fade" id="modal-upd-typechargement" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">Type de chargement <span id='nom-typechargement-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-typechargement">
                    <input type="hidden" id="id-typechargement" name="id-typechargement">
                    <div class="form-floating mb-3">
                        <input type="text" id="lib-typechargement-upd" name="lib-typechargement-upd" required class="form-control">
                        <label for="lib-typechargement-upd">Libellé</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" id="unite-typechargement-upd" name="unite-typechargement-upd" class="form-control" placeholder="ex: tonnes, caisses, m³">
                        <label for="unite-typechargement-upd">Unité de mesure</label>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" id="valmin-typechargement-upd" name="valmin-typechargement-upd" value="0" min="0" step="0.01" class="form-control">
                                <label for="valmin-typechargement-upd">Valeur min</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="number" id="valmax-typechargement-upd" name="valmax-typechargement-upd" value="0" min="0" step="0.01" class="form-control">
                                <label for="valmax-typechargement-upd">Valeur max</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateTypeChargement()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
