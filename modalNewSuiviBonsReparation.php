<?php /* POST handled by MaintenanceController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-suiviBonsReparation" tabindex="-1" aria-labelledby="modal-new-suiviBonsReparationLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-suiviBonsReparationLabel">Nouveau suivi des réparations</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-br" class="row">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="num-br" name="num-br" readonly required class="form-control" value="BR-<?php echo date('YmdHi-s'); ?>">
                            <label for="num-br">N° Bon de réparation</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">

                            <label for="vh-br">Véhicule</label>

                            <select id="vh-br" name="vh-br" required>
                                <?php $affectationRepo = new AffectationRepository($con);
                                foreach ($affectationRepo->findActiveByContext(getContextRegions(), getContextEntities()) as $r):
                                    echo "<option value='" . $r['id_affectation'] . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == $r['id_affectation'] ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>

                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="date-entree-br" name="date-entree-br" required value="<?php echo date('Y-m-d'); ?>">
                            <label for="date-entree-br">Date d'entrée</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="diagnostic-br" name="diagnostic-br" required></textarea>
                            <label for="diagnostic-br">Diagnostic</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">

                            <label for="type-execution-br">Type d'exécution</label>

                            <select id="type-execution-br" name="type-execution-br" required>
                                <option value="0">Interne</option>
                                <option value="1">Externe</option>
                            </select>

                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="prestataire-br">Prestataire</label>
                            <div class="input-group">
                                <select id="prestataire-br" name="prestataire-br">
                                    <?php $maintenanceRepo = new MaintenanceRepository($con);
                                    $prestataires = $maintenanceRepo->findAllPrestataires();
                                    foreach ($prestataires as $r):
                                        echo "<option value='" . $r['id_prestataire'] . "'>" . h($r['nom_prestataire']) . "</option>";
                                    endforeach;
                                    if (count($prestataires) == 0) echo "<option value=''></option>";
                                    ?>
                                </select>
                                <a class="btn btn-primary" style="padding:15px" href="?page=maintenances&subpage=prestataire&action=new&extpage" target="blank" title="Nouveau prestataire"><i class="fa fa-plus"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="montant-br" name="montant-br" required min="0" value="0" class="form-control">
                            <label for="montant-br">Montant réparation</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="destination-br" name="destination-br" required class="form-control">
                            <label for="destination-br">Destination</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-justif-br" name="date-justif-br" required class="form-control">
                            <label for="date-justif-br">Date Justification</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" name="date-prevue-br" id="date-prevue-br" class="form-control">
                            <label for="date-prevue-br">Date prévue sortie</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="observation-br" name="observation-br"></textarea>
                            <label for="observation-br">Observations</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveBR()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveBR() {
        var valid = true
        $('#form-new-br *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-br').notify("Tous les champs en rouge sont obligatoires!", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-br').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Enregistrement effectué!')
                location.reload()
            } else if (e.error == '1062') {
                $('#form-new-br').notify("Ce bon de réparation existe déjà!", {
                    position: 'top'
                })
            } else {
                $('#form-new-br').notify(e.error || "Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            }
        }).fail((jqXHR) => {
            $('#form-new-br').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!", {
                position: 'top'
            })
        })
    }

    function openModalSuiviBonsReparation() {
        $('#modal-new-suiviBonsReparation').modal('show')
    }

    function togglePrestataireBR() {
        const externe = $('#type-execution-br').val() === '1'
        $('#prestataire-br').closest('.col-6').toggle(externe)
        $('#prestataire-br').prop('required', externe)
        if (!externe) {
            const ts = $('#prestataire-br')[0] && $('#prestataire-br')[0].tomselect
            ts ? ts.clear() : $('#prestataire-br').val('')
        }
    }
    $('#type-execution-br').on('change', togglePrestataireBR)
    togglePrestataireBR()   // masqué au chargement (Interne par défaut)
</script>