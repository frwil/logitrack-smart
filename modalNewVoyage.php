<?php /* All POST handlers migrated to controllers/router.php — dateV, trajets, chrelevekms */ ?>
<div class="modal fade" id="modal-new-voyage" tabindex="-1" aria-labelledby="modal-new-voyageLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-voyageLabel">Nouveau voyage</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-voyage">
                    <div class="col-12 mb-3">
                        <label>Mode de transport</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode-vg" id="mode-vg-flotte" value="flotte" checked>
                            <label class="form-check-label" for="mode-vg-flotte">Véhicule flotte</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode-vg" id="mode-vg-externe" value="externe">
                            <label class="form-check-label" for="mode-vg-externe">Prestataire externe</label>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" id="titre-vg" name="titre-vg" required class="form-control" readonly>
                        <label for="titre-vg">Titre du voyage</label>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="date" id="date-vg" name="date-vg" required class="form-control">
                                <input type="hidden" id="date-check" name="date-check" value="0">
                                <label for="date-vg">Date du voyage</label>
                            </div>
                        </div>
                        <div class="col-6 vg-flotte-only">
                            <div class="mb-3">

                                <label for="id-vehicule-vg">Véhicule</label>

                                <select id="id-vehicule-vg" name="id-vehicule-vg" required>
                                    <?php $affectationRepo = new AffectationRepository($con);
                                    foreach ($affectationRepo->findActiveByContext(getContextRegions(), getContextEntities()) as $r):
                                        $regionName = $r['nom_region'] ?? '';
                                        $entiteName = $r['nom_entite'] ?? '';
                                        $extra = $regionName ? " — $regionName" : '';
                                        $extra .= $entiteName ? " / $entiteName" : '';
                                        echo "<option value='" . $r['id_affectation'] . "' data-id-region='" . (int)$r['id_region'] . "' data-id-entite='" . (int)$r['id_entite'] . "'>" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")" . h($extra) . "</option>";
                                    endforeach;
                                    ?>
                                </select>

                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="id-convoyeur-vg" name="id-convoyeur-vg">
                                <label for="id-convoyeur-vg">Convoyeur</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="numero-scelle-vg" name="numero-scelle-vg" placeholder="N° scellé">
                                <label for="numero-scelle-vg">N° de scellé</label>
                            </div>
                        </div>
                        <div class="col-6 vg-ext-only" style="display:none">
                            <div class="mb-3">
                                <label for="id-prestataire-vg">Prestataire externe</label>
                                <div class="input-group">
                                    <select id="id-prestataire-vg" name="id-prestataire-vg">
                                        <?php $ptRepo = new PrestataireTransportRepository($con);
                                        foreach ($ptRepo->findAll() as $r):
                                            echo "<option value='" . $r['id_prestataire_transport'] . "' data-chauffeur='" . h($r['nom_chauffeur']) . "'>" . h($r['nom_societe']) . " — " . h($r['immatriculation']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <button class="btn btn-primary" onclick="openModalPrestataireTransport()" type="button" title="Ajouter un prestataire"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 vg-ext-only" style="display:none">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="chauffeur-vg" name="chauffeur-vg">
                                <label for="chauffeur-vg">Chauffeur</label>
                            </div>
                        </div>
                        <div class="col-6 vg-ext-only" style="display:none">
                            <div class="mb-3">
                                <label for="id-entite-vg">Entité</label>
                                <select id="id-entite-vg" name="id-entite-vg">
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
                        <div class="col-6 vg-ext-only" style="display:none">
                            <div class="mb-3">
                                <label for="id-region-vg">Région</label>
                                <select id="id-region-vg" name="id-region-vg">
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
                        <div class="col-6 vg-flotte-only">
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" id="qtecarburant-vg" value="0" name="qtecarburant-vg" required min="0">
                                <label for="qtecarburant-vg">Carburant consommé (en Litres)</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">

                                <label for="typechargement-vg">Type de chargement</label>

                                <select id="typechargement-vg" name="typechargement-vg" required>
                                    <?php $voyageRepo = new VoyageRepository($con);
                                    foreach ($voyageRepo->findAllTypesChargement() as $r):
                                        echo "<option value='" . $r['id_type_chargement'] . "' val-min='" . h($r['valeur_min']) . "' val-max='" . h($r['valeur_max']) . "'>" . h($r['lib_type_chargement']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>

                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" class="form-control" id="qtechargement-vg" value="0" min="0" name="qtechargement-vg" required>
                                <label for="qtechargement-vg">Qté chargement</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <h3 class="h5">Trajets <span id="total-km-trajet"></span></h3>
                            <hr>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="trajet-list-vg">Trajet</label>
                                <div class="input-group">
                                    <select id="trajet-list-vg" role="trajet" required>
                                        <?php $trajetRepo = new TrajetRepository($con);
                                        foreach ($trajetRepo->findAll() as $r):
                                            echo "<option value='" . $r['id_destination'] . "' dest-km='" . h($r['distance_destination']) . "'>" . h($r['lib_destination']) . " (" . h($r['distance_destination']) . " km)</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <button class="btn btn-primary" onclick="addTrajet()" type="button">Ajouter le trajet</button>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="col-12">
                            <div class="row" id="trajet-container">

                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveVoyage()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    var trajets = []
    var typeChargements = []
    var qteChargements = []
    var totalkm = 0;

    function addTrajet() {
        trajets.push($('#trajet-list-vg').val())
        totalkm += parseInt($('#trajet-list-vg option[value="' + $('#trajet-list-vg').val() + '"]').attr('dest-km'))
        $('#total-km-trajet').html(totalkm + ' km')
        addTrajetFormField($('#trajet-list-vg').val(), $('#trajet-list-vg option[value="' + $('#trajet-list-vg').val() + '"]').html(), $('#trajet-list-vg option[value="' + $('#trajet-list-vg').val() + '"]').attr('dest-km'))
        refreshTrajetsOptions()
    }

    function addTrajetFormField(id, displayValue, km) {
        $('#trajet-container').append("<div class='col-6'><div class='input-group mb-3'><input type='hidden' name='listeTrajets[]' value='" + id + "'><input class='form-control' id='" + id + "' disabled value='" + displayValue + "' dest-km='" + km + "'><button class='btn btn-danger' type='button' title='retirer ce trajet' onclick='rmTrajet(\"" + id + "\")'><i class='fa fa-times'></i></button>&nbsp;<span class='badge text-bg-secondary' style='padding:10px'>&rarr;</span></div>")
    }

    function refreshTrajetsOptions() {
        $.ajax({
            type: 'post',
            data: 'trajets=' + JSON.stringify(trajets),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#trajet-list-vg').html(e.html)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function rmTrajet(id) {
        for (i = 0; i < trajets.length; i++) {
            if (trajets[i] == id) {
                trajets.splice(i, 1)
                totalkm -= parseInt($('input#' + id).attr('dest-km'))
                $('#total-km-trajet').html(totalkm == 0 ? '' : totalkm + ' km')
                $('input#' + id).parent().parent().remove()
            }
        }
        refreshTrajetsOptions()
    }

    function Str_Random(length) {
        let result = '';
        const characters = 'abcdefghijklmnopqrstuvwxyz0123456789';

        for (let i = 0; i < length; i++) {
            const randomInd = Math.floor(Math.random() * characters.length);
            result += characters.charAt(randomInd);
        }
        return result;
    }

    function openModalVoyage() {
        $('#modal-new-voyage').modal('show')
        $('#titre-vg').val('Voyage-<?php echo date('ym'); ?>' + Str_Random(5).toUpperCase())
        $('#date-check').val(0)
        $('input[name="mode-vg"][value="flotte"]').prop('checked', true)
        toggleModeVg()
        $('#id-prestataire-vg').trigger('change')
    }

    // Bascule flotte / prestataire externe
    $('input[name="mode-vg"]').change(toggleModeVg)

    // Pré-remplit le chauffeur avec celui du prestataire choisi
    $('#id-prestataire-vg').change(function () {
        $('#chauffeur-vg').val($(this).find('option:selected').attr('data-chauffeur') || '')
    })

    function toggleModeVg() {
        const externe = $('input[name="mode-vg"]:checked').val() === 'externe'
        $('.vg-flotte-only').toggle(!externe)
        $('.vg-ext-only').toggle(externe)
        $('#id-vehicule-vg').prop('disabled', externe)
        $('#qtecarburant-vg').prop('disabled', externe)
        $('#id-prestataire-vg, #id-entite-vg, #id-region-vg, #chauffeur-vg').prop('disabled', !externe)
    }

    async function checkReleveKms(id,dvg,fvg){
        let check=await fetch('',{method:'post',body: JSON.stringify({ chrelevekms: id,datevg:dvg,finvg:fvg,csrf_token:window.CSRF_TOKEN }),headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    }})
        check=await check.json()
        return check.success && check.count==1;
    }

   async function saveVoyage() {
        if ($('input[name="mode-vg"]:checked').val() === 'externe') {
            saveVoyagePrestataire()
            return
        }
        var valid = true
        $('#form-new-voyage *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-voyage').notify("Tous les champs en rouge sont obligatoire!!!", {
                position: 'top'
            })
            return false
        }
        if (trajets.length == 0) {
            $('#trajet-list-vg').parent().parent().parent().notify("Vous n'avez ajouté aucun trajet à ce voyage", {
                position: 'top'
            })
            return false
        }
        //alert($('#date-vg').val());
        //return false;
        if($('#date-check').val()=='1'){
            if(confirm("Cette journée n'a pas d'objectif défini.\n Bien vouloir définir l'objectif de la journée avant d'enregistrer des voyages.\n Voulez-vous définir un objectif pour cette journée ?")){
                var selected = $('#id-vehicule-vg option:selected');
                var region = selected.attr('data-id-region');
                var entite = selected.attr('data-id-entite');
                var url = '?page=voyages&subpage=listeObjectifsVoyages&action=new';
                if (region) url += '&prefill_region=' + region;
                if (entite) url += '&prefill_entite=' + entite;
                location = url;
            }
            return false
        }
        valid=await checkReleveKms($('#id-vehicule-vg').val(),moment($('#date-vg').val()).startOf('week').format('YYYY-MM-DD'),moment($('#date-vg').val()).endOf('week').format('YYYY-MM-DD'))
        if(!valid){
            /*if(confirm("Aucun relevé de kilométrage n'a été fait pour ce véhicule cette semaine.\nVoulez-vous procéder au relevé du km ?\nVous devrez peut-être contacter votre administrateur si vous n'avez pas les droits d'acces.")){
                <?php /*if(in_array("view",$rights_maintenance)): echo "window.open('?page=maintenances&subpage=releveKms&action=new&idvgch='+$('#id-vehicule-vg').val()+'&dch='+$('#date-vg').val());"; 
                else : ?>
                showError("Vous n'avez pas les droits!\nContactez votre administrateur.")
                <?php endif;*/ ?>
                return false
            }else{
                return false;
            }*/
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-voyage').serialize() + '&trajets-voyage=' + JSON.stringify(trajets),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau voyage créee!!")
                $('#modal-new-voyage').modal('hide')
                $('#form-new-voyage *').val('')
                trajets = []
                location.reload()
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }

    function saveVoyagePrestataire() {
        var valid = true
        ;['#date-vg', '#id-prestataire-vg', '#id-entite-vg', '#id-region-vg', '#typechargement-vg', '#qtechargement-vg'].forEach((sel) => {
            $(sel).removeClass('is-invalid')
            if ($(sel).val() === '' || $(sel).val() === null) {
                valid = false
                $(sel).addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-voyage').notify("Tous les champs en rouge sont obligatoire!!!", {
                position: 'top'
            })
            return false
        }
        if (trajets.length == 0) {
            $('#trajet-list-vg').parent().parent().parent().notify("Vous n'avez ajouté aucun trajet à ce voyage", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: 'date-vge=' + $('#date-vg').val()
                + '&id-prestataire-vge=' + $('#id-prestataire-vg').val()
                + '&id-entite-vge=' + $('#id-entite-vg').val()
                + '&id-region-vg=' + $('#id-region-vg').val()
                + '&typechargement-vge=' + $('#typechargement-vg').val()
                + '&qtechargement-vge=' + $('#qtechargement-vg').val()
                + '&chauffeur-vge=' + encodeURIComponent($('#chauffeur-vg').val() || '')
                + '&convoyeur-vge=' + encodeURIComponent($('#id-convoyeur-vg').val() || '')
                + '&numero-scelle-vge=' + encodeURIComponent($('#numero-scelle-vg').val() || '')
                + '&trajets-voyage=' + JSON.stringify(trajets),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau voyage prestataire créé!!")
                $('#modal-new-voyage').modal('hide')
                trajets = []
                location.reload()
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
    $('#date-vg').change((e)=>{
        $.ajax({
            type:'post',
            data:'dateV='+$(e.currentTarget).val(),
            dataType:'json'
        }).done((e)=>{
            if(e.count=='0'){
                $('#date-check').val(1)
            } else {
                $('#date-check').val(0)
            }
        }).fail((jqXHR)=>{
            showError(jqXHR.responseJSON?.error || "Erreur lors de la vérification")
        })
    })

    $('#typechargement-vg').change((e)=>{
        let min=$('#typechargement-vg option[value="'+$('#typechargement-vg').val()+'"]').attr('val-min')
        let max=$('#typechargement-vg option[value="'+$('#typechargement-vg').val()+'"]').attr('val-max')
        $('#qtechargement-vg').attr('min',min).val(min)
        $('#qtechargement-vg').attr('max',max)
    })
</script>