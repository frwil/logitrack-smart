<?php
/* POST handled by ConvoyeurController — see controllers/router.php */
if (isset($_POST['refresh-marque'])):
    $marqueRepo = new MarqueRepository($con);
    $liste = "";
    foreach ($marqueRepo->findAll() as $r):
        $liste .= "<option value='" . h($r['id_marque']) . "'>" . h($r['nom_marque']) . "</option>";
    endforeach;
    die(json_encode(['success' => true, 'html' => $liste]));
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
            data: 'refresh-convoyeur=1',
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#marque-vh').html(e.html)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function saveConvoyeur() {
        if ($('#nom-convoyeur').val() == '') {
            showError("Le champ est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-convoyeur').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau convoyeur créee!!")
                $('#modal-new-convoyeur').modal('hide')
                $('#form-new-convoyeur *').val('')
                refreshConvoyeurOptions()
                location="?page=affectationVehicules&subpage=listeConvoyeurs"
            } else if (e.error == '1062') {
                showError("Ce convoyeur existe déjà")
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>