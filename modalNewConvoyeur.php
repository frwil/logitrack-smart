<?php
if (isset($_POST['nom-convoyeur'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $_POST['nom-convoyeur'] = trim(strtoupper($_POST['nom-convoyeur']));
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
        $q = mysqli_query($con, "INSERT INTO `convoyeur` (`id_convoyeur`, `nom_convoyeur`) VALUES (NULL, '{$_POST['nom-convoyeur']}')");
        mysqli_commit($con);
        die("NewConvoyeur%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewConvoyeur%%%%%%1062');
        die("NewConvoyeur%%%%%%0");
    }
endif;
if (isset($_POST['refresh-marque'])):
    $q = mysqli_query($con, "select * from marque_vehicule");
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='{$r[0]}'>{$r[1]}</option>";
    endwhile;
    die("NewConvoyeur%%%%%%$liste");
endif;
?>
<div class="modal fade" id="modal-new-convoyeur" tabindex="-1" aria-labelledby="modal-new-convoyeurLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-convoyeurLabel">Nouveau convoyeur de véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-convoyeur">
                    <div class="form-floating mb-3">
                        <input type="text" id="nom-convoyeur" name="nom-convoyeur" required class="form-control">
                        <label for="nom-convoyeur">Nom convoyeur</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveConvoyeur()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalConvoyeur() {
        $('#modal-new-convoyeur').modal('show')
    }

    function refreshConvoyeurOptions() {
        $.ajax({
            type: 'post',
            data: 'refresh-convoyeur=1'
        }).done((e) => {
            let v = e.split('NewConvoyeur%%%%%%')[1]
            $('#marque-vh').html(v)
        })
    }

    function saveConvoyeur() {
        if ($('#nom-convoyeur').val() == '') {
            $.notify("Le champ est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-convoyeur').serialize()
        }).done((e) => {
            let v = e.split('NewConvoyeur%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouveau convoyeur créee!!", {
                    className: 'success'
                })
                $('#modal-new-convoyeur').modal('hide')
                $('#form-new-convoyeur *').val('')
                refreshConvoyeurOptions()
                location="?page=affectationVehicules&subpage=listeConvoyeurs"
            } else if (v == '1062') {
                $.notify("Ce convoyeur existe déjà")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>