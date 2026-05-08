<?php /* POST handled by ObjectifController — see controllers/router.php */ ?>
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
            showError("Les champs sont obligatoires!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-objectif').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Objectif enregisté pour la date du "+$('#date-objectif').val()+" !!")
                $('#modal-new-objectif').modal('hide')
                $('#form-new-objectif *').val('')
                location="?page=voyages&subpage=listeObjectifsVoyages&action=new"
            } else if (e.error == '1062') {
                showError("Un objectif a déjà été défini pour cette journée.")
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>