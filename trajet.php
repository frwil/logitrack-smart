<?php function getTableauTrajets()
{
    global $con;
    global $rights_voyage;
    $repo = new TrajetRepository($con);
    $rows = $repo->findAll();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Libellé</th><th>Distance</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['lib_destination']) . "</td><td>" . h($r['distance_destination']) . "</td><td><div class='btn-group'>";
        if (hasSubRight('updtrajet', 'upd', $rights_voyage, ['viewtrajet','savetrajet','updtrajet','deltrajet'])):
            $tableau .= "<button class='btn btn-secondary' title='Modifier le trajet' onclick='showModalUpdateTrajet(\"".$r['id_destination']."\")'><i class='fa fa-pencil-alt'></i></button>";
        endif;
        $isSuper = $_SESSION['usr-con']['is-superadmin'] ?? false;
        if ($isSuper && hasSubRight('deltrajet', 'del', $rights_voyage, ['viewtrajet','savetrajet','updtrajet','deltrajet'])):
            $tableau .= "<button class='btn btn-danger' title='Supprimer le trajet' onclick='deleteTrajet(\"".($r['id_destination'])."\")'><i class='fa fa-times'></i></button>";
        endif;
        $tableau .= "</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewTrajet.php'); ?>
<?php /* POST handled by TrajetController — see controllers/router.php */ ?>
<?php if (isset($_GET['action']) && $_GET['action'] == 'new'): ?>
    <script>
        setTimeout(() => {
            openModalTrajet()
        }, 2000)
    </script>
<?php endif; ?>
<script>
    function showModalUpdateTrajet(id) {
        $('#modal-upd-destination').modal('show')
        $('#id-destination').val(id)
        $.ajax({
            type: 'post',
            data: 'id-destination-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#nom-destination-display').html(e.data.lib_destination);
                $('#nom-upd-destination').val(e.data.lib_destination);
                $('#distance-destination-upd').val(e.data.distance_destination);
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement");
        })
    }

    function updateTrajet($id) {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-destination').serialize(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location = "?page=voyages&subpage=listeTrajets"
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function deleteTrajet(id) {
        if (confirm("Etes-vous sûr de vouloir supprimer?")) {
            $.ajax({
                type: 'post',
                data: 'id-destination-forDel=' + id,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Trajet supprimé!!')
                    location.reload()
                } else if (e.canForceDelete) {
                    if (confirm("Ce trajet est utilisé dans " + e.usageCount + " voyage(s). Voulez-vous forcer la suppression ? Les voyages liés seront également supprimés.")) {
                        $.ajax({
                            type: 'post',
                            data: 'id-destination-forDel=' + id + '&force-destination-del=1',
                            dataType: 'json'
                        }).done((f) => {
                            if (f.success) {
                                showSuccess('Trajet et voyages liés supprimés!')
                                location.reload()
                            } else {
                                showError(f.error || "Echec de l'opération")
                            }
                        }).fail((jqXHR) => {
                            showError(jqXHR.responseJSON?.error || "Echec de l'opération")
                        })
                    }
                } else {
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }
</script>
<div class="modal fade" id="modal-upd-destination" tabindex="-1" aria-labelledby="modal-upd-destinationLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-destinationLabel">Trajet de voyage <span id='nom-destination-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-destination">
                    <div class="form-floating mb-3">
                        <input type="hidden" id="id-destination" name="id-destination">
                        <input type="text" id="nom-upd-destination" name="nom-upd-destination" required class="form-control">
                        <label for="nom-upd-destination">Destination</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" id="distance-destination-upd" name="distance-destination-upd" min="1" required class="form-control">
                        <label for="distance-destination-upd">Distance (en km)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateTrajet()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
