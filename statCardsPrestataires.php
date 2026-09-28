<?php /* Cartes stats des voyages prestataires externes — inclus depuis voyage.php et config.php */ ?>
<?php
$vpRepo = new VoyagePrestataireRepository($con);
$vpStats = $vpRepo->statsExternes(getContextRegions(), getContextEntities(), null);
?>
<div class="row g-3 mb-3">
    <div class="col-md">
        <div class="lt-card lt-stat-card">
            <div class="lt-stat-icon"><i class="fa fa-handshake"></i></div>
            <div class="lt-stat-value"><span id="stat-vp-voyages"><?= number_format($vpStats['nb_voyages'], 0, ',', ' ') ?></span></div>
            <div class="lt-stat-label" id="stat-vp-voyages-label">Voyages prestataires (mois)</div>
        </div>
    </div>
    <div class="col-md">
        <div class="lt-card lt-stat-card">
            <div class="lt-stat-icon"><i class="fa fa-boxes"></i></div>
            <div class="lt-stat-value"><span id="stat-vp-qte"><?= $vpStats['total_qte_fmt'] ?></span><span id="stat-vp-unite" class="fs-6 ms-1"><?= h($vpStats['unite']) ?></span></div>
            <div class="lt-stat-label">Qtés transportées (mois)
                <div class="btn-group btn-group-sm mt-1 w-100" role="group" aria-label="Portée des quantités transportées">
                    <input type="radio" class="btn-check" name="scope-stat-prestataires" id="scope-pt-externe" value="externe" checked>
                    <label class="btn btn-outline-primary" for="scope-pt-externe">Prestataires</label>
                    <input type="radio" class="btn-check" name="scope-stat-prestataires" id="scope-pt-flotte" value="flotte">
                    <label class="btn btn-outline-primary" for="scope-pt-flotte">Flotte</label>
                    <input type="radio" class="btn-check" name="scope-stat-prestataires" id="scope-pt-tout" value="tout">
                    <label class="btn btn-outline-primary" for="scope-pt-tout">Les 2</label>
                </div>
                <select id="type-chargement-stat" class="form-select form-select-sm mt-1">
                    <option value="0">Tous (mixte)</option>
                    <?php $voyageRepoTypes = new VoyageRepository($con);
                    foreach ($voyageRepoTypes->findAllTypesChargement() as $t):
                        echo "<option value='" . $t['id_type_chargement'] . "'>" . h($t['lib_type_chargement']) . "</option>";
                    endforeach;
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<script>
    // Recharge les stats du bloc selon la portée (bouton radio) et le type de chargement
    function loadStatsPrestataires() {
        $.ajax({
            type: 'post',
            data: 'load-stats-prestataires=1&type-chargement-stat=' + $('#type-chargement-stat').val()
                + '&scope-stat-prestataires=' + ($('input[name="scope-stat-prestataires"]:checked').val() || 'externe'),
            dataType: 'json'
        }).done((res) => {
            if (res.success) {
                $('#stat-vp-qte').text(res.total_qte_fmt)
                $('#stat-vp-unite').text(res.unite || '')
                $('#stat-vp-voyages').text(res.nb_voyages)
                const voyageLabels = { externe: 'Voyages prestataires (mois)', flotte: 'Voyages flotte (mois)', tout: 'Voyages (mois)' }
                $('#stat-vp-voyages-label').text(voyageLabels[res.scope] || voyageLabels.externe)
            } else {
                showError(res.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }
    $('#type-chargement-stat').change(loadStatsPrestataires)
    $('input[name="scope-stat-prestataires"]').change(loadStatsPrestataires)
</script>
