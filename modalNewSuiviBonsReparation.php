<?php /* POST handled by BonReparationController — see controllers/router.php */
if (isset($_POST['load-cc-br'])):
    $maintenanceRepo = new MaintenanceRepository($con);
    $centresCouts = $maintenanceRepo->findAllCentresCouts();
    $options = "";
    foreach ($centresCouts as $r):
        $options .= "<option value='" . $r['id_centre_cout'] . "'>" . h($r['lib_centre_cout']) . "</option>";
    endforeach;
    if (count($centresCouts) == 0) $options = "<option value=''></option>";
    die(json_encode(['success' => true, 'html' => $options]));
endif;
?>
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
                        <div class="form-floating mb-3">
                            <select id="vh-br" name="vh-br" class="form-select">
                                <?php $affectationRepo = new AffectationRepository($con);
                                foreach ($affectationRepo->findActiveByRegion((int)$_SESSION['usr-con']['region-sel']) as $r):
                                    echo "<option value='" . $r['id_affectation'] . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == $r['id_affectation'] ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>
                            <label for="vh-br">Véhicule</label>
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
                        <div class="form-floating mb-3">
                            <select class="form-select" id="type-execution-br" name="type-execution-br">
                                <option value="0">Interne</option>
                                <option value="1">Externe</option>
                            </select>
                            <label for="type-execution-br">Type d'exécution</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group mb-3">
                            <div class="form-floating">
                                <select class="form-select" id="prestataire-br" name="prestataire-br">
                                    <?php $maintenanceRepo = new MaintenanceRepository($con);
                                    $prestataires = $maintenanceRepo->findAllPrestataires();
                                    foreach ($prestataires as $r):
                                        echo "<option value='" . $r['id_prestataire'] . "'>" . h($r['nom_prestataire']) . "</option>";
                                    endforeach;
                                    if (count($prestataires) == 0) echo "<option value=''></option>";
                                    ?>
                                </select>
                                <label for="prestataire-br">Prestataire</label>
                            </div>
                            <a class="btn btn-primary" style="padding:15px" href="?page=maintenances&subpage=prestataire&action=new&extpage" target="blank" title="Nouveau prestataire"><i class="fa fa-plus"></i></a>
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
                            <select class="form-select" id="plus-moins-br" name="plus-moins-br">
                                <?php $plusOuMoins = $maintenanceRepo->findAllPlusOuMoinsValue();
                                foreach ($plusOuMoins as $r):
                                    echo "<option value='" . $r['id_plus_ou_moins_value'] . "'>" . h($r['lib_plus_ou_moins_value']) . "</option>";
                                endforeach;
                                if (count($plusOuMoins) == 0) echo "<option value=''></option>";
                                ?>
                            </select>
                            <label for="plus-moins-br">Type Valeur additionnelle</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="plus-moins-val-br" name="plus-moins-val-br" required value="0" min="0" class="form-control">
                            <label for="plus-moins-val-br">Valeur additionnelle</label>
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
                            <input type="number" id="duree-br" name="duree-br" required value="0" min="0" class="form-control">
                            <label for="duree-br">Durée (en jours)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-justif-br" name="date-justif-br" required class="form-control">
                            <label for="date-justif-br">Date Justification</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group mb-3">
                            <div class="form-floating">
                                <select class="form-select" id="centrecout-br" name="centrecout-br" required>
                                    <?php $maintenanceRepo = new MaintenanceRepository($con);
                                    $centresCouts = $maintenanceRepo->findAllCentresCouts();
                                    foreach ($centresCouts as $r):
                                        echo "<option value='" . $r['id_centre_cout'] . "'>" . h($r['lib_centre_cout']) . "</option>";
                                    endforeach;
                                    if (count($centresCouts) == 0) echo "<option value=''></option>";
                                    ?>
                                </select>
                                <label for="centrecout-br">Centre de coûts</label>
                            </div>
                            <button class="btn btn-primary" style="padding:15px" onclick="var win = window.open('?page=maintenances&subpage=centreCouts&action=new&extpage', '_blank'); setTimeout(()=>{win.addEventListener('beforeunload', function(event) {loadCentreCouts()})},5000);" type="button" title="Nouveau centre de coûts"><i class="fa fa-plus"></i></button>
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
                            <input type="date" name="date-fin-br" id="date-fin-br" class="form-control">
                            <label for="date-fin-br">Date effective de fin</label>
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
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
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

    function loadCentreCouts() {
        $.ajax({
            type: 'post',
            data: 'load-cc-br=1',
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#centrecout-br').html(e.html)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }
</script>