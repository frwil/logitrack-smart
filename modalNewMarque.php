<?php
if (isset($_POST['nom-marque'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $_POST['nom-marque'] = trim(strtoupper($_POST['nom-marque']));
        $q = db_exec($con, "INSERT INTO `marque_vehicule` (`id_marque`, `nom_marque`) VALUES (NULL, ?)", [$_POST['nom-marque']]);
        mysqli_commit($con);
        die("NewMarque%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewMarque%%%%%%1062');
        die("NewMarque%%%%%%0");
    }
endif;
if (isset($_POST['refresh-marque'])):
    $q = db_select($con, "select * from marque_vehicule", []);
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='{$r[0]}'>{$r[1]}</option>";
    endwhile;
    die("NewMarque%%%%%%$liste");
endif;
?>
<div class="modal fade" id="modal-new-marque" tabindex="-1" aria-labelledby="modal-new-marqeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-marqueLabel">Nouvelle marque de véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-marque">
                    <div class="form-floating mb-3">
                        <input type="text" id="nom-marque" name="nom-marque" required class="form-control">
                        <label for="nom-marque">Libellé marque</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveMarque()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalMarque() {
        $('#modal-new-marque').modal('show')
    }

    function refreshMarqueOptions() {
        $.ajax({
            type: 'post',
            data: 'refresh-marque=1'
        }).done((e) => {
            let v = e.split('NewMarque%%%%%%')[1]
            $('#marque-vh').html(v)
        })
    }

    function saveMarque() {
        if ($('#nom-marque').val() == '') {
            $.notify("Le champs est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-marque').serialize()
        }).done((e) => {
            let v = e.split('NewMarque%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouvelle marque créee!!", {
                    className: 'success'
                })
                $('#modal-new-marque').modal('hide')
                $('#form-new-marque *').val('')
                refreshMarqueOptions()
                <?php if (isset($_GET['subpage'])) : ?>
                    location.reload()
                <?php endif; ?>
            } else if (v == '1062') {
                $.notify("Cette marque de véhicule existe déjà")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>