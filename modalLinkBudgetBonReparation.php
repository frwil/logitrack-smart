<?php /* POST handled by MaintenanceController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-link-budget-bonReparation" tabindex="-1" aria-labelledby="modal-link-budget-bonReparationLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-link-budget-bonReparationLabel">Ligne budgétaire — <span id='link-br-num'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Ligne actuelle : <span id='link-br-current'>—</span></p>
                <form id="form-link-budget-br">
                    <input type="hidden" id="link-br-id" name="link-br-id">
                    <div class="mb-3">
                        <label for="link-br-lb">Ligne budgétaire</label>
                        <select id="link-br-lb" name="link-br-lb" required>
                        </select>
                    </div>
                    <div id="link-br-info" class="alert alert-light mb-0"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="linkBudgetBR()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function linkBudgetBR() {
        var valid = true
        $('#form-link-budget-br *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-link-budget-br').notify("Tous les champs en rouge sont obligatoires!", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: 'link-budget-br=' + $('#link-br-id').val() + '&id-lb-link=' + $('#link-br-lb').val(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Liaison effectuée!')
                location.reload()
            } else {
                $('#modal-link-budget-bonReparation .modal-body').notify(e.error || "Erreur lors de la liaison!", {
                    position: 'top'
                })
            }
        }).fail((jqXHR) => {
            $('#modal-link-budget-bonReparation .modal-body').notify(jqXHR.responseJSON?.error || "Erreur lors de la liaison!", {
                position: 'top'
            })
        })
    }

    function refreshLinkBudgetInfo() {
        const opt = $('#link-br-lb option:selected')
        const budget = parseInt(opt.data('budget') || 0)
        const utilise = parseInt(opt.data('utilise') || 0)
        const restant = budget - utilise
        const fmt = n => n.toLocaleString('fr-FR')
        $('#link-br-info').html('Budget centre : <strong>' + fmt(budget) + '</strong> — Utilisé : <strong>' + fmt(utilise) + '</strong> — Restant : <strong>' + fmt(restant) + '</strong>')
    }

    const modalLinkBudgetBR = document.getElementById('modal-link-budget-bonReparation')
    if (modalLinkBudgetBR) {
        modalLinkBudgetBR.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const id = event.relatedTarget.getAttribute('data-bs-id-br')
            $('#link-br-id').val(id)
            $('#link-br-num').html('')
            $('#link-br-current').html('—')
            $('#link-br-info').html('')
            $.ajax({
                type: 'post',
                data: 'c-br-s=' + id,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    $('#link-br-num').html(e.data.num_bon_reparation)
                }
            })
            $.ajax({
                type: 'post',
                data: 'load-lb=1&bon-lb=' + id,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    $('#link-br-lb').html(e.html)
                    if (e.linked) {
                        $('#link-br-current').html(e.linked)
                    }
                    refreshLinkBudgetInfo()
                } else {
                    showError(e.error || "Erreur lors du chargement")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
            })
        })
    }
</script>
