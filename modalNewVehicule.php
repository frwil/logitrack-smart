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
                            <div class="mb-3">
                                <label for="marque-vh">Marque</label>
                                <div class="input-group">
                                    <select type="text" id="marque-vh" name="marque-vh" required>
                                        <?php $marqueRepo = new MarqueRepository($con);
                                        foreach ($marqueRepo->findAll() as $r):
                                            echo "<option value='" . h($r['id_marque']) . "'>" . h($r['nom_marque']) . "</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <button class="btn btn-primary" type="button" title="Ajouter une marque" onclick="openModalMarque()"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="modele-vh">Modele</label>
                                <div class="input-group">
                                    <select type="text" id="modele-vh" name="modele-vh" required>
                                        <?php $modeleRepo = new ModeleRepository($con);
                                        foreach ($modeleRepo->findAll() as $r):
                                            echo "<option value='" . h($r['id_modele_vehicule']) . "'>" . h($r['nom_modele_vehicule']) . "</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <button class="btn btn-primary" type="button" title="Ajouter un modèle de véhicule" onclick="openModalModele()"><i class="fa fa-plus"></i></button>
                                </div>
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
                        <div class="mb-3 col-6">
                            <label for="nbplace-vh">Nb. places</label>
                            <select id="nbplace-vh" name="nbplace-vh" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="7">7</option>
                            </select>
                        </div>
                        <div class="mb-3 col-6">
                            <label for="tcarb-vh">Type de carburant</label>
                            <select id="tcarb-vh" name="tcarb-vh" required>
                                <option value="GASOIL">Gasoil</option>
                                <option value="SUPER">Super</option>
                            </select>
                        </div>
                        <hr>
                        <div class="mb-3 col-6">
                            <label for="qualif-permis">Qualification de permis</label>
                            <select id="qualif-permis" name="qualif-permis" required>
                                    <?php $configRepo = new ConfigRepository($con);
                                    foreach ($configRepo->findAllTypePermis() as $r):
                                        echo "<option value='" . $r['id_type_permis'] . "'>" . h($r['lib_type_permis']) . "</option>";
                                    endforeach;
                                    ?>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Capacités de chargement</label>
                        <div id="capacites-container" class="mb-2"></div>
                        <div class="input-group input-group-sm">
                            <select id="capacite-unite-select" class="form-select" style="max-width:40%">
                                <option value="">-- Unité --</option>
                                <?php $tcRepo2 = new TypeChargementRepository($con);
                                $seen = [];
                                foreach ($tcRepo2->findAll() as $tc):
                                    if ($tc['unite_mesure'] === '' || isset($seen[$tc['unite_mesure']])) continue;
                                    $seen[$tc['unite_mesure']] = true;
                                    echo "<option value='" . h($tc['unite_mesure']) . "'>" . h($tc['unite_mesure']) . "</option>";
                                endforeach; ?>
                                <option value="__other__">Autre...</option>
                            </select>
                            <input type="text" id="capacite-unite-custom" class="form-control" placeholder="Unité personnalisée" style="display:none;max-width:40%">
                            <input type="number" id="capacite-max-input" class="form-control" placeholder="Capacité max" min="0" step="0.01" style="max-width:40%">
                            <button type="button" class="btn btn-outline-primary" onclick="addCapaciteRow()"><i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <input type="hidden" name="capacites-vh" id="capacites-vh" value="[]">
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
    function openModalVehicule() {
        $('#modal-new-vehicule').modal('show')
    }

    function addCapaciteRow(unite, max) {
        if (!unite) {
            unite = $('#capacite-unite-select').val()
            if (unite === '__other__') unite = $('#capacite-unite-custom').val().trim()
            max = $('#capacite-max-input').val()
        }
        if (!unite || !max || parseFloat(max) <= 0) return
        var key = unite.replace(/"/g, '')
        if ($('#cap-row-' + CSS.escape(key)).length) return
        var html = '<div class="badge bg-light text-dark me-1 mb-1 p-2" id="cap-row-' + key + '">' +
            unite + ' : <strong>' + max + '</strong> ' +
            '<button type="button" class="btn-close btn-close-sm ms-1" onclick="$(\'#cap-row-' + key + '\').remove();syncCapacitesJson()"></button>' +
            '</div>'
        $('#capacites-container').append(html)
        $('#capacite-max-input').val('')
        syncCapacitesJson()
    }

    function syncCapacitesJson() {
        var caps = []
        $('#capacites-container .badge').each(function() {
            var t = $(this).text().trim()
            var m = t.match(/^(.+?) : (.+?) /)
            if (m) caps.push({unite: m[1].trim(), max: parseFloat(m[2])})
        })
        $('#capacites-vh').val(JSON.stringify(caps))
    }

    $('#capacite-unite-select').change(function() {
        var v = $(this).val()
        if (v === '__other__') {
            $('#capacite-unite-custom').show().val('')
            $('#capacite-unite-select').hide()
        }
    })

    function saveVehicule() {
        syncCapacitesJson()
        var valid = true
        $('#form-new-vehicule *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
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