<?php function getTableauTrajets()
{
    global $con;
    $q = db_select($con, "select * from destination_voyage where 1", []);
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Libellé</th><th>Distance</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['lib_destination']}</td><td>{$r['distance_destination']}</td><td><div class='btn-group'><button class='btn btn-secondary' title='Modifier le trajet' onclick='showModalUpdateTrajet(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-pencil-alt'></i></button><button class='btn btn-danger' title='Supprimer le trajet' onclick='delTrajet(\"".(sha1($r[0].$r[1]))."\")'><i class='fa fa-times'></i></button></div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewTrajet.php'); ?>
<?php if (isset($_POST['id-destination-forModal'])):
    $q = db_select($con, "select * from destination_voyage where sha1(concat(id_destination,lib_destination))=?", [$_POST['id-destination-forModal']]);
    while ($r = mysqli_fetch_array($q)):
        $destination = $r;
    endwhile;
    die("UpdTrajet%%%%%%" . json_encode($destination));
endif;
if (isset($_POST['id-destination'])):
    $_POST['nom-upd-destination'] = trim(strtoupper($_POST['nom-upd-destination']));
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
      $q = db_exec($con, "update destination_voyage set lib_destination=?, distance_destination=? where sha1(concat(id_destination,lib_destination))=?", [$_POST['nom-upd-destination'], $_POST['distance-destination-upd'], $_POST['id-destination']]);
        mysqli_commit($con);
        die("UpdTrajet%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdTrajet%%%%%%0");
    }
endif;
if (isset($_POST['id-destination-forDel'])):
    $q = db_exec($con, "delete from destination_voyage where sha1(concat(id_destination,lib_destination))=?", [$_POST['id-destination-forDel']]);
    if ($q) die("UpdTrajet%%%%%%1");
    die("UpdTrajet%%%%%%0");
endif;
?>
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
            data: 'id-destination-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdTrajet%%%%%%')[1]
            v = JSON.parse(v);
            $('#nom-destination-display').html(v.lib_destination);
            $('#nom-upd-destination').val(v.lib_destination);
  $('#distance-destination-upd').val(v.distance_destination);
        })
    }

    function updateTrajet($id) {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-destination').serialize()
            }).done((e) => {
                let v = e.split('UpdTrajet%%%%%%')[1]
                if (v == '1') {
                    $.notify('Modification effectuée!!', {
                        className: 'success'
                    })
                    location = "?page=voyages&subpage=listeTrajets"
                } else {
                    $.notify("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteTrajet(id) {
        if (confirm("Etes-vous sûr de vouloir supprimer?")) {
            $.ajax({
                type: 'post',
                data: 'id-destination-forDel=' + id
            }).done((e) => {
                let v = e.split('UpdTrajet%%%%%%')[1]
                if (v == '1') {
                    $.notify('Trajet supprimée!!', {
                        className: 'success'
                    })
                    location.reload()
                } else {
                    $.notify("Echec de l'opération")
                }
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
                        <input type="number" id="distance-destination-upd" name="distance-destination-upd" min=0 required class="form-control">
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