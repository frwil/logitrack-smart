<?php /* POST /vehicule/create handled by VehiculeController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-vehicule" tabindex="-1" aria-labelledby="modal-new-vehiculeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-vehiculeLabel">Nouveau Véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-vehicule">
                    <div class="row">
                        <div class="form-floating mb-3 col-6">
                            <input type="text" id="immat-vh" name="immat-vh" required class="form-control">
                            <label for="immat-vh">Immatriculation</label>
                        </div>
                        <div class="col-6">
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select type="text" id="marque-vh" name="marque-vh" required>
                                        <?php $marqueRepo = new MarqueRepository($con);
                                        foreach ($marqueRepo->findAll() as $r):
                                            echo "<option value='" . h($r['id_marque']) . "'>" . h($r['nom_marque']) . "</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <label for="marque-vh">Marque</label>
                                </div>
                                <button class="btn btn-primary" type="button" title="Ajouter une marque" onclick="openModalMarque()"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select type="text" id="modele-vh" name="modele-vh" required>
                                        <?php $modeleRepo = new ModeleRepository($con);
                                        foreach ($modeleRepo->findAll() as $r):
                                            echo "<option value='" . h($r['id_modele_vehicule']) . "'>" . h($r['nom_modele_vehicule']) . "</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <label for="modele-vh">Modele</label>
                                </div>
                                <button class="btn btn-primary" type="button" title="Ajouter un modèle de véhicule" onclick="openModalModele()"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="text" id="chassis-vh" name="chassis-vh" required class="form-control">
                            <label for="chassis-vh">N° de chassis</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="date" id="dutil-vh" name="dutil-vh" required class="form-control">
                            <label for="dutil-vh">Date de 1ère utilisation</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="date" id="dexpir-vh" name="dexpir-vh" required class="form-control">
                            <label for="dexpir-vh">Date expir. carte grise</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="number" id="puissance-vh" name="puissance-vh" required class="form-control">
                            <label for="puissance-vh">Puissance</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="number" id="capacite-vh" name="capacite-vh" required class="form-control">
                            <label for="capacite-vh">Capacité</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="nbplace-vh" name="nbplace-vh" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="7">7</option>
                            </select>
                            <label for="nbplace-vh">Nb. places</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="tcarb-vh" name="tcarb-vh" required>
                                <option value="GASOIL">Gasoil</option>
                                <option value="SUPER">Super</option>
                            </select>
                            <label for="tcarb-vh">Type de carburant</label>
                        </div>
                        <hr>
                        <div class="form-floating mb-3 col-6">
                            <select id="qualif-permis" name="qualif-permis" required>
                                    <?php $configRepo = new ConfigRepository($con);
                                    foreach ($configRepo->findAllTypePermis() as $r):
                                        echo "<option value='" . $r['id_type_permis'] . "'>" . h($r['lib_type_permis']) . "</option>";
                                    endforeach;
                                    ?>
                            </select>
                            <label for="qualif-permis">Qualification de permis</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveVehicule()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveVehicule() {
        var valid = true
        $('#form-new-vehicule *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
            }
        })
        if (!valid) {
            showError('Les champs en rouge sont obligatoires')
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-vehicule').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Enregistrement effectué!!!")
                location.reload()
            } else {
                showError(e.error||"Erreur lors de l'enregistrement")
            }
        })
    }
</script>