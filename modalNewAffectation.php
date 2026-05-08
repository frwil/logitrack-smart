<?php
/* POST handled by AffectationController — see controllers/router.php */
if (isset($_POST['refresh-vehicule'])):
    $vhRepo = new VehiculeRepository($con);
    $liste = "";
    foreach ($vhRepo->findAllWithDetails() as $r):
        $liste .= "<option value='" . $r['id_vehicule'] . "'>" . h($r['immatriculation_vehicule']) . "</option>";
    endforeach;
    die(json_encode(['success' => true, 'html' => $liste]));
endif;
?>
<div class="modal fade" id="modal-new-affectation" tabindex="-1" aria-labelledby="modal-new-affectationLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-affectationLabel">Nouvelle affectation de véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-affectation">
                    <div class="form-floating mb-3">
                        <select id="id-vehicule-aff" name="id-vehicule-aff" required>
                            <?php $vehiculeRepo = new VehiculeRepository($con);
                            foreach ($vehiculeRepo->findAllWithDetails() as $r):
                                echo "<option value='" . $r['id_vehicule'] . "'>" . h($r['immatriculation_vehicule']) . " - " . h($r['nom_marque']) . "</option>";
                            endforeach;
                            ?>
                        </select>
                        <label for="id-vehicule-aff">Véhicule</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select id="id-chauffeur-aff" name="id-chauffeur-aff" required>
                            <?php $chauffeurRepo = new ChauffeurRepository($con);
                            foreach ($chauffeurRepo->findAll() as $r):
                                echo "<option value='" . $r['id_chauffeur'] . "'>" . h($r['nom_chauffeur']) . "</option>";
                            endforeach;
                            ?>
                        </select>
                        <label for="id-chauffeur-aff">Chauffeur</label>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select id="id-typeutilisation-aff" name="id-typeutilisation-aff" required>
                                    <?php $typeUtilRepo = new TypeUtilisationRepository($con);
                                    foreach ($typeUtilRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_type_utilisation'] . "'>" . h($r['lib_type_utilisation']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                                <label for="id-typeutilisation-aff">Type utilisation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select id="id-modeutilisation-aff" name="id-modeutilisation-aff" required>
                                    <?php $modeUtilRepo = new ModeUtilisationRepository($con);
                                    foreach ($modeUtilRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_mode_utilisation'] . "'>" . h($r['lib_mode_utilisation']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                                <label for="id-modeutilisation-aff">Mode utilisation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select id="id-entite-aff" name="id-entite-aff" required>
                                    <?php $entiteRepo = new EntiteRepository($con);
                                    foreach ($entiteRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_entite'] . "'>" . h($r['nom_entite']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                                <label for="id-entite-aff">Entité</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select id="id-region-aff" name="id-region-aff" required>
                                    <?php $regionRepo = new RegionRepository($con);
                                    foreach ($regionRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_region'] . "'>" . h($r['nom_region']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                                <label for="id-region-aff">Région</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="objet-aff" name="objet-aff">

                        </textarea>
                                <label for="objet-aff">Objet d'affectation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="date-debut-aff" name="date-debut-aff" required>
                                <label for="date-debut-aff">Date début affectation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="date-fin-aff" name="date-fin-aff">
                                <label for="date-fin-aff">Date fin affectation</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveAffectation()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalAffectation() {
        $('#modal-new-affectation').modal('show')
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

    function saveAffectation() {
        var valid = true
        $('#form-new-affectation *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
            }
        })
        if (!valid) {
            showError('Tous les champs en rouge sont obligatoires!!!')
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-affectation').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouvelle affectation créee!!")
                $('#modal-new-affectation').modal('hide')
                $('#form-new-affectation *').val('')
                location="?page=affectationVehicules"
            } else if (e.error == '1062') {
                $('#form-new-affectation').notify("Vous devez clôturer l'affectation actuelle de ce véhicule avant de le réaffecter",{
                    position:'top'
                })
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>