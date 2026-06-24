<?php /* POST handled by MarqueController — see controllers/router.php */ ?>
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
            data: 'refresh-marque=1',
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

    function saveMarque() {
        if ($('#nom-marque').val() == '') {
            showError("Le champs est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-marque').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouvelle marque créee!!")
                $('#modal-new-marque').modal('hide')
                $('#form-new-marque *').val('')
                refreshMarqueOptions()
                <?php if (isset($_GET['subpage'])) : ?>
                    location.reload()
                <?php endif; ?>
            } else if (e.error == '1062') {
                showError("Cette marque de véhicule existe déjà")
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>