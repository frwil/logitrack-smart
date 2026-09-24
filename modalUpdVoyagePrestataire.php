<?php /* POST handled by PrestataireTransportController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-upd-voyage-prestataire" tabindex="-1" aria-labelledby="modal-upd-voyage-prestataireLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-voyage-prestataireLabel">Voyage prestataire externe <span id="vp-upd-display"></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-voyage-prestataire" class="row">
                    <input type="hidden" id="id-voyage-prestataire-upd" name="id-voyage-prestataire-upd">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-upd-vge" name="date-upd-vge" required class="form-control">
                            <label for="date-upd-vge">Date du voyage</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="prestataire-upd-vge" class="form-control" readonly>
                            <label for="prestataire-upd-vge">Prestataire</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="id-entite-upd-vge">Entité</label>
                            <select id="id-entite-upd-vge" name="id-entite-upd-vge" required>
                                <?php $entiteRepo = new EntiteRepository($con);
                                $ctxEntites = array_map('intval', getContextEntities());
                                foreach ($entiteRepo->findAll() as $r):
                                    if (!in_array((int)$r['id_entite'], $ctxEntites)) continue;
                                    echo "<option value='" . $r['id_entite'] . "'>" . h($r['nom_entite']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="id-region-upd-vge">Région</label>
                            <select id="id-region-upd-vge" name="id-region-upd-vge" required>
                                <?php $regionRepo = new RegionRepository($con);
                                $ctxRegions = array_map('intval', getContextRegions());
                                foreach ($regionRepo->findAll() as $r):
                                    if (!in_array((int)$r['id_region'], $ctxRegions)) continue;
                                    echo "<option value='" . $r['id_region'] . "'>" . h($r['nom_region']) . "</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="convoyeur-upd-vge" name="convoyeur-upd-vge" class="form-control">
                            <label for="convoyeur-upd-vge">Convoyeur</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="numero-scelle-upd-vge" name="numero-scelle-upd-vge" class="form-control" placeholder="N° scellé">
                            <label for="numero-scelle-upd-vge">N° de scellé</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="typechargement-upd-vge">Type de chargement</label>
                            <select id="typechargement-upd-vge" name="typechargement-upd-vge" required>
                            <?php $voyageRepo = new VoyageRepository($con);
                                    foreach ($voyageRepo->findAllTypesChargement() as $r):
                                        echo "<option value='" . $r['id_type_chargement'] . "'>" . h($r['lib_type_chargement']) . "</option>";
                                    endforeach;
                                    ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="qtechargement-upd-vge" name="qtechargement-upd-vge" required min="0" class="form-control">
                            <label for="qtechargement-upd-vge">Qté chargement</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="trajet-list-upd-vge">Trajets</label>
                            <div class="input-group">
                                <select id="trajet-list-upd-vge">
                                    <?php $trajetRepo = new TrajetRepository($con);
                                    foreach ($trajetRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_destination'] . "' dest-km='" . h($r['distance_destination']) . "'>" . h($r['lib_destination']) . " (" . h($r['distance_destination']) . " km)</option>";
                                    endforeach;
                                    ?>
                                </select>
                                <button class="btn btn-primary" onclick="addTrajetUpd()" type="button">Ajouter le trajet</button>
                            </div>
                            <div class="row mt-2" id="trajet-container-upd-vge"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateVoyagePrestataire()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    var trajetsUpd = []
    var trajetsAllUpd = []
    $('#trajet-list-upd-vge option').each(function () {
        trajetsAllUpd.push({ id: $(this).val(), lib: $(this).text(), km: $(this).attr('dest-km') })
    })

    function renderTrajetsUpd() {
        var html = ''
        trajetsUpd.forEach((t) => {
            html += "<div class='col-6'><div class='input-group mb-3'><input class='form-control' disabled value='" + t.lib.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;') + "'><button class='btn btn-danger' type='button' title='retirer ce trajet' onclick='rmTrajetUpd(" + t.id + ")'><i class='fa fa-times'></i></button>&nbsp;<span class='badge text-bg-secondary' style='padding:10px'>&rarr;</span></div></div>"
        })
        $('#trajet-container-upd-vge').html(html)
        var used = trajetsUpd.map(t => t.id)
        var opts = ''
        trajetsAllUpd.forEach((t) => {
            if (!used.includes(t.id)) opts += "<option value='" + t.id + "' dest-km='" + t.km + "'>" + t.lib + "</option>"
        })
        $('#trajet-list-upd-vge').html(opts)
    }

    function addTrajetUpd() {
        var sel = $('#trajet-list-upd-vge').val()
        if (!sel || trajetsUpd.some(t => t.id === sel)) return
        var t = trajetsAllUpd.find(t => t.id === sel)
        trajetsUpd.push(t)
        renderTrajetsUpd()
    }

    function rmTrajetUpd(id) {
        trajetsUpd = trajetsUpd.filter(t => t.id !== String(id))
        renderTrajetsUpd()
    }

    function updVoyagePresta(id) {
        $('#modal-upd-voyage-prestataire').modal('show')
        $('#id-voyage-prestataire-upd').val(id)
        $.ajax({
            type: 'post',
            data: 'id-voyage-prestataire-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#date-upd-vge').val(e.date_voyage)
                $('#prestataire-upd-vge').val((e.nom_societe || '') + ' — ' + e.immatriculation + ' (' + e.nom_chauffeur + ')')
                $('#id-entite-upd-vge').val(e.id_entite)
                $('#id-region-upd-vge').val(e.id_region)
                $('#convoyeur-upd-vge').val(e.convoyeur || '')
                $('#numero-scelle-upd-vge').val(e.numero_scelle || '')
                $('#typechargement-upd-vge').val(e.id_type_chargement)
                $('#qtechargement-upd-vge').val(e.qte_chargement)
                trajetsUpd = (e.trajets || []).map((t) => ({ id: String(t.id_destination), lib: t.lib_destination, km: t.distance_destination }))
                renderTrajetsUpd()
            } else {
                showError(e.error || "Echec du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Echec du chargement")
        })
    }

    function updateVoyagePrestataire() {
        var valid = true
        $('#form-upd-voyage-prestataire *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-upd-voyage-prestataire').notify('Tous les champs en rouge sont obligatoires!', { position: 'top' })
            return false
        }
        if (trajetsUpd.length == 0) {
            $('#form-upd-voyage-prestataire').notify("Ajoutez au moins un trajet à ce voyage", { position: 'top' })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-upd-voyage-prestataire').serialize() + '&trajets-voyage=' + JSON.stringify(trajetsUpd.map(t => parseInt(t.id))),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Voyage mis à jour")
                $('#modal-upd-voyage-prestataire').modal('hide')
                location.reload()
            } else {
                showError(e.error || "Echec de l'opération")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Echec de l'opération")
        })
    }

    function delVoyagePresta(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer ce voyage prestataire ?")) {
            $.ajax({
                type: 'post',
                data: 'id-voyage-prestataire-del=' + id,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess("Voyage supprimé")
                    location.reload()
                } else {
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }
</script>
