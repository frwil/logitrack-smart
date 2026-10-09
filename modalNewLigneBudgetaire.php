<?php /* POST handled by MaintenanceController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-ligne-budgetaire" tabindex="-1" aria-labelledby="modal-new-ligne-budgetaireLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-ligne-budgetaireLabel">Nouvelle ligne budgétaire</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-lb">
                    <div class="form-floating mb-3">
                        <input type="text" id="lib-lb" name="lib-lb" required class="form-control">
                        <label for="lib-lb">Libellé de la ligne budgétaire</label>
                    </div>
                    <div class="mb-3">
                        <label for="cc-lb">Centre de coûts</label>
                        <select id="cc-lb" name="cc-lb" required>
                            <?php $maintenanceRepo = new MaintenanceRepository($con);
                            foreach ($maintenanceRepo->findAllCentresCouts() as $r):
                                echo "<option value='" . $r['id_centre_cout'] . "'>" . h($r['lib_centre_cout']) . "</option>";
                            endforeach;
                            if (count($maintenanceRepo->findAllCentresCouts()) == 0) echo "<option value=''></option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exercice-lb">Exercice</label>
                        <select id="exercice-lb" name="exercice-lb" required>
                            <?php $exercices = $maintenanceRepo->findAllExercicesBudgetaires();
                            $openExercice = $maintenanceRepo->findOpenExerciceBudgetaire();
                            foreach ($exercices as $r):
                                $sel = ($openExercice && $r['id_exercice_budgetaire'] == $openExercice['id_exercice_budgetaire']) ? "selected" : "";
                                echo "<option value='" . $r['id_exercice_budgetaire'] . "' $sel>" . h($r['lib_exercice_budgetaire']) . " (" . h($r['statut_exercice']) . ")</option>";
                            endforeach;
                            if (count($exercices) == 0) echo "<option value=''></option>";
                            ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveLB()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveLB() {
        var valid = true
        $('#form-new-lb *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-lb').notify("Tous les champs en rouge sont obligatoires!", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-lb').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Enregistrement effectué!')
                location.reload()
            } else {
                $('#modal-new-ligne-budgetaire .modal-body').notify(e.error || "Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            }
        }).fail((jqXHR) => {
            $('#modal-new-ligne-budgetaire .modal-body').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!", {
                position: 'top'
            })
        })
    }

    function openModalLigneBudgetaire() {
        $('#modal-new-ligne-budgetaire').modal('show')
    }
</script>
