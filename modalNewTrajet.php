<?php
if (isset($_POST['nom-destination'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $_POST['nom-destination'] = trim(strtoupper($_POST['nom-destination']));
        $q = db_exec($con, "INSERT INTO `destination_voyage` (`id_destination`, `lib_destination`,`distance_destination`) VALUES (NULL, ?,?)", [$_POST['nom-destination'], $_POST['distance-destination']]);
        mysqli_commit($con);
        die("NewTrajet%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewTrajet%%%%%%1062');
        die("NewTrajet%%%%%%0");
    }
endif;
if (isset($_POST['refresh-destination'])):
    $q = db_select($con, "select * from destination_voyage", []);
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='".sha1($r[0].$r[1])."'>{$r[1]}</option>";
    endwhile;
    die("NewTrajet%%%%%%$liste");
endif;
?>
<div class="modal fade" id="modal-new-destination" tabindex="-1" aria-labelledby="modal-new-marqeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-destinationLabel">Nouveau trajet de voyage</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-destination">
                    <div class="form-floating mb-3">
                        <input type="text" id="nom-destination" name="nom-destination" required class="form-control">
                        <label for="nom-destination">Libellé trajet</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" id="distance-destination" name="distance-destination" required class="form-control">
                        <label for="distance-destination">Distance trajet</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveTrajet()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalTrajet() {
        $('#modal-new-destination').modal('show')
        setTimeout(()=>{$('#nom-destination').focus()},1000)
    }

    function refreshTrajetOptions() {
        $.ajax({
            type: 'post',
            data: 'refresh-destination=1'
        }).done((e) => {
            let v = e.split('NewTrajet%%%%%%')[1]
            $('#destination-vh').html(v)
        })
    }

    function saveTrajet() {
        var valid=true
        $('#form-new-destination *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-destination').notify("Tous les champs en rouge sont obligatoires!!!",{
                position:'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-destination').serialize()
        }).done((e) => {
            let v = e.split('NewTrajet%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouveau trajet créé!!", {
                    className: 'success'
                })
                $('#modal-new-destination').modal('hide')
                $('#form-new-destination *').val('')
                refreshTrajetOptions()
                <?php if (isset($_GET['subpage'])) : ?>
                    location.reload()
                <?php endif; ?>
            } else if (v == '1062') {
                $.notify("Ce trajet de voyage existe déjà")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>