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
                            <select id="prestataire-br-upd" name="prestataire-br-upd">
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
                        <div class="form-floating mb-3">
                            <input type="number" id="montant-paye-br-upd" name="montant-paye-br-upd" min="0" class="form-control">
                            <label for="montant-paye-br-upd">Montant payé</label>
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
                            <input type="date" id="date-justif-br-upd" name="date-justif-br-upd" required class="form-control">
                            <label for="date-justif-br-upd">Date Justification</label>
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
                            <label for="date-fin-br-upd">Date effective de sortie</label>
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
                if (v.id_prestataire) $('#prestataire-br-upd').val(v.id_prestataire)
                $('#montant-br-upd').val(v.montant_reparation)
                $('#montant-paye-br-upd').val(v.montant_paye ?? '')
                $('#destination-br-upd').val(v.destination_bon)
                $('#date-justif-br-upd').val(v.date_justification)
                $('#date-prevue-br-upd').val(v.date_prevue_sortie)
                $('#date-fin-br-upd').val(v.date_fin_reparation === '0000-00-00' ? '' : v.date_fin_reparation)
                $('#observation-br-upd').val(v.observations)
                togglePrestataireBRUpd()
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
            })
        })
    }

    function togglePrestataireBRUpd() {
        const externe = $('#type-execution-br-upd').val() === '1'
        $('#prestataire-br-upd').closest('.col-6').toggle(externe)
        $('#prestataire-br-upd').prop('required', externe)
        if (!externe) {
            const ts = $('#prestataire-br-upd')[0] && $('#prestataire-br-upd')[0].tomselect
            ts ? ts.clear() : $('#prestataire-br-upd').val('')
        }
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
