<?php /* POST handled by MaintenanceController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-exercice" tabindex="-1" aria-labelledby="modal-new-exerciceLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-exerciceLabel">Nouvel exercice budgétaire</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-ex">
                    <div class="form-floating mb-3">
                        <input type="text" id="lib-ex" name="lib-ex" required class="form-control">
                        <label for="lib-ex">Libellé de l'exercice</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="date" id="date-debut-ex" name="date-debut-ex" required class="form-control" value="<?php echo date('Y-01-01'); ?>">
                        <label for="date-debut-ex">Date début</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="date" id="date-fin-ex" name="date-fin-ex" required class="form-control" value="<?php echo date('Y-12-31'); ?>">
                        <label for="date-fin-ex">Date fin</label>
                    </div>
                    <div class="mb-3">
                        <label for="statut-ex">Statut</label>
                        <select id="statut-ex" name="statut-ex" required>
                            <option value="Ouvert" selected>Ouvert</option>
                            <option value="Clôturé">Clôturé</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveEX()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveEX() {
        var valid = true
        $('#form-new-ex *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-ex').notify("Tous les champs en rouge sont obligatoires!", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-ex').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Enregistrement effectué!')
                location.reload()
            } else {
                $('#modal-new-exercice .modal-body').notify(e.error || "Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            }
        }).fail((jqXHR) => {
            $('#modal-new-exercice .modal-body').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!", {
                position: 'top'
            })
        })
    }

    function openModalExercice() {
        $('#modal-new-exercice').modal('show')
    }
</script>
