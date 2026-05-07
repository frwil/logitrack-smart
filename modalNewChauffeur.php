<?php
if (isset($_POST['nom-chauffeur'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $_POST['nom-chauffeur'] = trim(strtoupper($_POST['nom-chauffeur']));
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
        $q = mysqli_query($con, "INSERT INTO `chauffeur` (`id_chauffeur`, `nom_chauffeur`,id_type_permis) VALUES (NULL, '{$_POST['nom-chauffeur']}',(select id_type_permis from type_permis_vehicule where sha1(concat(id_type_permis,lib_type_permis))='{$_POST['type-permis']}'))");
        mysqli_commit($con);
        die("NewChauffeur%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewChauffeur%%%%%%1062');
        die("NewChauffeur%%%%%%0".$e->getCode());
    }
endif;
if (isset($_POST['refresh-marque'])):
    $q = mysqli_query($con, "select * from marque_vehicule");
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='{$r[0]}'>{$r[1]}</option>";
    endwhile;
    die("NewChauffeur%%%%%%$liste");
endif;
?>
<div class="modal fade" id="modal-new-chauffeur" tabindex="-1" aria-labelledby="modal-new-chauffeurLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-chauffeurLabel">Nouveau chauffeur de véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-chauffeur">
                    <div class="form-floating mb-3">
                        <input type="text" id="nom-chauffeur" name="nom-chauffeur" required class="form-control">
                        <label for="nom-chauffeur">Nom chauffeur</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select id="type-permis" name="type-permis" required class="form-select">
                            <?php $q = mysqli_query($con, "select * from type_permis_vehicule");
                            while ($r = mysqli_fetch_array($q)):
                                echo "<option value='".sha1($r[0].$r[1])."'>{$r[1]}</option>";
                            endwhile;
                            ?>
                        </select>
                        <label for="type-permis">Cat. de permis (la plus élevée)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveChauffeur()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalChauffeur() {
        $('#modal-new-chauffeur').modal('show')
    }

    function refreshChauffeurOptions() {
        $.ajax({
            type: 'post',
            data: 'refresh-chauffeur=1'
        }).done((e) => {
            let v = e.split('NewChauffeur%%%%%%')[1]
            $('#marque-vh').html(v)
        })
    }

    function saveChauffeur() {
        if ($('#nom-chauffeur').val() == '') {
            $.notify("Le champ est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-chauffeur').serialize()
        }).done((e) => {
            let v = e.split('NewChauffeur%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouveau chauffeur créee!!", {
                    className: 'success'
                })
                $('#modal-new-chauffeur').modal('hide')
                $('#form-new-chauffeur *').val('')
                refreshChauffeurOptions()
                location = "?page=affectationVehicules&subpage=listeChauffeurs"
            } else if (v == '1062') {
                $.notify("Ce chauffeur existe déjà")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>