<?php
if (isset($_POST['nom-modele'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $_POST['nom-modele'] = trim(strtoupper($_POST['nom-modele']));
        $q = db_exec($con, "INSERT INTO `modele_vehicule` (`id_modele_vehicule`, `nom_modele_vehicule`) VALUES (NULL, ?)", [$_POST['nom-modele']]);
        mysqli_commit($con);
        die("NewModele%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewModele%%%%%%1062');
        die("NewModele%%%%%%0");
    }
endif;

if (isset($_POST['refresh-modele'])):
    $q = db_select($con, "select * from modele_vehicule", []);
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='{$r[0]}'>{$r[1]}</option>";
    endwhile;
    die("NewModele%%%%%%$liste");
endif;
?>
<div class="modal fade" id="modal-new-modele" tabindex="-1" aria-labelledby="modal-new-modeleLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-modeleLabel">Nouveau modèle de véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-modele">
                    <div class="form-floating mb-3">
                        <input type="text" id="nom-modele" name="nom-modele" required class="form-control">
                        <label for="nom-marque">Libellé modèle</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveModele()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModalModele() {
        $('#modal-new-modele').modal('show')
    }

    function refreshModeleOptions() {
        $.ajax({
            type: 'post',
            data: 'refresh-modele=1'
        }).done((e) => {
            let v = e.split('NewModele%%%%%%')[1]
            $('#modele-vh').html(v)
        })
    }

    function saveModele() {
        if ($('#nom-modele').val() == '') {
            $.notify("Le champs est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-modele').serialize()
        }).done((e) => {
            let v = e.split('NewModele%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouveau modèle de véhicule créé!!", {
                    className: 'success'
                })
                $('#modal-new-modele').modal('hide')
                $('#form-new-modele *').val('')
                refreshModeleOptions()
                <?php if (isset($_GET['subpage'])) : ?>
                    location.reload()
                <?php endif; ?>
            } else if (v == '1062') {
                $.notify("Ce modèle de véhicule existe déjà")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>