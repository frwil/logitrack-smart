<?php
/* POST handled by TrajetController — see controllers/router.php */
if (isset($_POST['refresh-destination'])):
    $trajetRepo = new TrajetRepository($con);
    $liste = "";
    foreach ($trajetRepo->findAll() as $r):
        $liste .= "<option value='" . $r['id_destination'] . "'>" . h($r['lib_destination']) . "</option>";
    endforeach;
    die(json_encode(['success' => true, 'html' => $liste]));
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
            data: 'refresh-destination=1',
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#destination-vh').html(e.html)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function saveTrajet() {
        var valid=true
        $('#form-new-destination *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
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
            data: $('#form-new-destination').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau trajet créé!!")
                $('#modal-new-destination').modal('hide')
                $('#form-new-destination *').val('')
                refreshTrajetOptions()
                <?php if (isset($_GET['subpage'])) : ?>
                    location.reload()
                <?php endif; ?>
            } else if (e.error == '1062') {
                showError("Ce trajet de voyage existe déjà")
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>