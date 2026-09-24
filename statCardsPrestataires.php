<?php /* Cartes stats des voyages prestataires externes — inclus depuis voyage.php et config.php */ ?>
<?php
$vpRepo = new VoyagePrestataireRepository($con);
$vpStats = $vpRepo->statsExternes(getContextRegions(), getContextEntities(), null);
?>
<div class="row g-3 mb-3">
    <div class="col-md">
        <div class="lt-card lt-stat-card">
            <div class="lt-stat-icon"><i class="fa fa-handshake"></i></div>
            <div class="lt-stat-value"><?= number_format($vpStats['nb_voyages'], 0, ',', ' ') ?></div>
            <div class="lt-stat-label">Voyages prestataires (mois)</div>
        </div>
    </div>
    <div class="col-md">
        <div class="lt-card lt-stat-card">
            <div class="lt-stat-icon"><i class="fa fa-boxes"></i></div>
            <div class="lt-stat-value"><span id="stat-vp-qte"><?= $vpStats['total_qte_fmt'] ?></span><span id="stat-vp-unite" class="fs-6 ms-1"><?= h($vpStats['unite']) ?></span></div>
            <div class="lt-stat-label">Qtés transportées prestataires (mois)
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
    $('#type-chargement-stat').change((e) => {
        $.ajax({
            type: 'post',
            data: 'load-stats-prestataires=1&type-chargement-stat=' + $('#type-chargement-stat').val(),
            dataType: 'json'
        }).done((res) => {
            if (res.success) {
                $('#stat-vp-qte').text(res.total_qte_fmt)
                $('#stat-vp-unite').text(res.unite || '')
            } else {
                showError(res.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    })
</script>
