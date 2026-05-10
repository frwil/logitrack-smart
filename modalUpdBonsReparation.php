<?php /* POST handled by MaintenanceController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-upd-bonsReparation" tabindex="-1" aria-labelledby="modal-upd-bonsReparationLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-bonsReparationLabel">Modifier le bon de réparation <span id="num-br-display"></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-upd-br" class="row">
                    <input type="hidden" id="id-upd-br" name="id-upd-br">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="num-br-upd" name="num-br-upd" readonly required class="form-control">
                            <label for="num-br-upd">N° Bon de réparation</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="vh-br-upd">Véhicule</label>
                            <select id="vh-br-upd" name="vh-br-upd" required>
                                <?php $affectationRepo = new AffectationRepository($con);
                                foreach ($affectationRepo->findActiveByContext(getContextRegions(), getContextEntities()) as $r):
                                    echo "<option value='" . $r['id_affectation'] . "'>" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="date-entree-br-upd" name="date-entree-br-upd" required>
                            <label for="date-entree-br-upd">Date d'entrée</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="diagnostic-br-upd" name="diagnostic-br-upd" required></textarea>
                            <label for="diagnostic-br-upd">Diagnostic</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="type-execution-br-upd">Type d'exécution</label>
                            <select id="type-execution-br-upd" name="type-execution-br-upd" required>
                                <option value="0">Interne</option>
                                <option value="1">Externe</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="prestataire-br-upd">Prestataire</label>
                            <select id="prestataire-br-upd" name="prestataire-br-upd" required>
                                <?php $maintenanceRepo = new MaintenanceRepository($con);
                                foreach ($maintenanceRepo->findAllPrestataires() as $r):
                                    echo "<option value='" . $r['id_prestataire'] . "'>" . h($r['nom_prestataire']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="montant-br-upd" name="montant-br-upd" required min="0" value="0" class="form-control">
                            <label for="montant-br-upd">Montant réparation</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="plus-moins-br-upd">Type Valeur additionnelle</label>
                            <select id="plus-moins-br-upd" name="plus-moins-br-upd" required>
                                <?php foreach ($maintenanceRepo->findAllPlusOuMoinsValue() as $r):
                                    echo "<option value='" . $r['id_plus_ou_moins_value'] . "'>" . h($r['lib_plus_ou_moins_value']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="plus-moins-val-br-upd" name="plus-moins-val-br-upd" required value="0" min="0" class="form-control">
                            <label for="plus-moins-val-br-upd">Valeur additionnelle</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="destination-br-upd" name="destination-br-upd" required class="form-control">
                            <label for="destination-br-upd">Destination</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="duree-br-upd" name="duree-br-upd" required value="0" min="0" class="form-control">
                            <label for="duree-br-upd">Durée (en jours)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-justif-br-upd" name="date-justif-br-upd" required class="form-control">
                            <label for="date-justif-br-upd">Date Justification</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="centrecout-br-upd">Centre de coûts</label>
                            <select id="centrecout-br-upd" name="centrecout-br-upd" required>
                                <?php foreach ($maintenanceRepo->findAllCentresCouts() as $r):
                                    echo "<option value='" . $r['id_centre_cout'] . "'>" . h($r['nom_centre_cout']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" name="date-prevue-br-upd" id="date-prevue-br-upd" class="form-control">
                            <label for="date-prevue-br-upd">Date prévue sortie</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" name="date-fin-br-upd" id="date-fin-br-upd" class="form-control">
                            <label for="date-fin-br-upd">Date effective de fin</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="observation-br-upd" name="observation-br-upd"></textarea>
                            <label for="observation-br-upd">Observations</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateBR()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    const modalUpdBR = document.getElementById('modal-upd-bonsReparation')
    if (modalUpdBR) {
        modalUpdBR.addEventListener('show.bs.modal', event => {
            const id = event.relatedTarget.getAttribute('data-bs-id-br')
            $.ajax({
                type: 'post',
                data: 'c-br-s=' + id,
                dataType: 'json'
            }).done((e) => {
                if (!e.success) { showError(e.error); return }
                let v = e.data
                $('#id-upd-br').val(v.id_bon_reparation)
                $('#num-br-display').html(v.num_bon_reparation)
                $('#num-br-upd').val(v.num_bon_reparation)
                $('#vh-br-upd').val(v.id_affectation_vehicule)
                $('#date-entree-br-upd').val(v.date_entree)
                $('#diagnostic-br-upd').val(v.diagnostic)
                $('#type-execution-br-upd').val(v.type_execution)
                $('#prestataire-br-upd').val(v.id_prestataire)
                $('#montant-br-upd').val(v.montant_reparation)
                $('#plus-moins-br-upd').val(v.id_plus_ou_moins_value)
                $('#plus-moins-val-br-upd').val(v.plus_ou_moins_value_valeur)
                $('#destination-br-upd').val(v.destination_bon)
                $('#duree-br-upd').val(v.duree_reparation)
                $('#date-justif-br-upd').val(v.date_justification)
                $('#centrecout-br-upd').val(v.id_centre_cout)
                $('#date-prevue-br-upd').val(v.date_prevue_sortie)
                $('#date-fin-br-upd').val(v.date_fin_reparation)
                $('#observation-br-upd').val(v.observations)
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
            })
        })
    }

    function updateBR() {
        var valid = true
        $('#form-upd-br *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-upd-br').notify("Tous les champs en rouge sont obligatoires!", { position: 'top' })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-upd-br').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Modification effectuée!')
                location.reload()
            } else {
                $('#form-upd-br').notify(e.error || "Erreur lors de la modification!", { position: 'top' })
            }
        }).fail((jqXHR) => {
            $('#form-upd-br').notify(jqXHR.responseJSON?.error || "Erreur lors de la modification!", { position: 'top' })
        })
    }
</script>
