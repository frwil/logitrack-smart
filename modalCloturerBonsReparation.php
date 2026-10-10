<?php /* POST handled by MaintenanceController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-cloturer-bonsReparation" tabindex="-1" aria-labelledby="modal-cloturer-bonsReparationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-cloturer-bonsReparationLabel">Clôturer le bon de réparation <span id="num-br-close-display"></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-close-br" class="row">
                    <input type="hidden" id="id-br-close" name="id-br-close">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="montant-paye-br-close" name="montant-paye-br-close" required min="0" step="0.01" class="form-control">
                            <label for="montant-paye-br-close">Montant payé</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-fin-br-close" name="date-fin-br-close" required class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            <label for="date-fin-br-close">Date effective de sortie</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="observation-br-close" name="observation-br-close" style="height: 100px;"></textarea>
                            <label for="observation-br-close">Observations</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="closeBR()">Clôturer</button>
            </div>
        </div>
    </div>
</div>
<script>
    const modalCloseBR = document.getElementById('modal-cloturer-bonsReparation')
    if (modalCloseBR) {
        modalCloseBR.addEventListener('show.bs.modal', event => {
            const id = event.relatedTarget.getAttribute('data-bs-id-br')
            $.ajax({
                type: 'post',
                data: 'c-br-s=' + id,
                dataType: 'json'
            }).done((e) => {
                if (!e.success) { showError(e.error); return }
                let v = e.data
                $('#id-br-close').val(v.id_bon_reparation)
                $('#num-br-close-display').html(v.num_bon_reparation)
                $('#montant-paye-br-close').val(v.montant_paye ?? '')
                // Date de sortie : conservée si déjà clôturé, sinon la valeur par défaut (aujourd'hui).
                if (v.date_fin_reparation && v.date_fin_reparation !== '0000-00-00') {
                    $('#date-fin-br-close').val(v.date_fin_reparation)
                }
                $('#observation-br-close').val(v.observations || '')
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
            })
        })
    }

    function closeBR() {
        var valid = true
        $('#form-close-br *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-close-br').notify("Tous les champs en rouge sont obligatoires!", { position: 'top' })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-close-br').serialize() + '&close-br=1',
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Bon clôturé!')
                location.reload()
            } else {
                $('#form-close-br').notify(e.error || "Erreur lors de la clôture!", { position: 'top' })
            }
        }).fail((jqXHR) => {
            $('#form-close-br').notify(jqXHR.responseJSON?.error || "Erreur lors de la clôture!", { position: 'top' })
        })
    }
</script>
