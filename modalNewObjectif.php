<?php
if (isset($_POST['date-objectif'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
        $q = mysqli_query($con, "INSERT INTO `objectif_periode_region` (`id_objectif_periode`, `date_objectif_periode`, `id_region`, `objectif`) VALUES (NULL, '{$_POST['date-objectif']}', {$_SESSION['usr-con']['region-sel']}, {$_POST['objectif']})");
        mysqli_commit($con);
        die("NewObjectif%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewObjectif%%%%%%1062');
        die("NewObjectif%%%%%%0");
    }
endif;

?>
<div class="modal fade" id="modal-new-objectif" tabindex="-1" aria-labelledby="modal-new-objectifLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-objectifLabel">Nouvel objectif</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-objectif">
                <div class="form-floating mb-3">
                        <input type="date" id="date-objectif" name="date-objectif" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        <label for="date-objectif">Date</label>
                    </div>    
                <div class="form-floating mb-3">
                        <input type="number" id="objectif" name="objectif" required class="form-control">
                        <label for="objectif">Objectif de voyages</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveObjectif()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalObjectif() {
        $('#modal-new-objectif').modal('show')
    }
    function saveObjectif() {
        if ($('#date-objectif').val() == '' || $('#objectif').val()=='') {
            $.notify("Les champs sont obligatoires!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-objectif').serialize()
        }).done((e) => {
            let v = e.split('NewObjectif%%%%%%')[1]
            if (v == '1') {
                $.notify("Objectif enregisté pour la date du "+$('#date-objectif').val()+" !!", {
                    className: 'success'
                })
                $('#modal-new-objectif').modal('hide')
                $('#form-new-objectif *').val('')
                location="?page=voyages&subpage=listeObjectifsVoyages&action=new"
            } else if (v == '1062') {
                $.notify("Un objectif a déjà été défini pour cette journée.")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>