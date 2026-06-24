<?php /* POST handled by ModeleController — see controllers/router.php */ ?>
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
            data: 'refresh-modele=1',
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#modele-vh').html(e.html)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function saveModele() {
        if ($('#nom-modele').val() == '') {
            showError("Le champs est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-modele').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau modèle de véhicule créé!!")
                $('#modal-new-modele').modal('hide')
                $('#form-new-modele *').val('')
                refreshModeleOptions()
                <?php if (isset($_GET['subpage'])) : ?>
                    location.reload()
                <?php endif; ?>
            } else if (e.error == '1062') {
                showError("Ce modèle de véhicule existe déjà")
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>