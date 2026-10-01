<?php /* Cartes stats des voyages prestataires externes — inclus depuis voyage.php et config.php */ ?>
<?php
// Portée de la page (barre de filtres voyages) : tout | flotte | externe | comparaison.
// Hors page voyages (config.php), pas de filtre de portée : radio libre.
$vpPageScope = function_exists('getVoyagesScope') ? getVoyagesScope() : 'tout';
// Période d'analyse (barre de filtres voyages) : défaut mois courant (aussi sur config.php).
[$vpDateFrom, $vpDateTo] = function_exists('getVoyagesPeriod') ? getVoyagesPeriod() : [date('Y-m-01'), date('Y-m-t')];
$vpPeriodSuffix = (function_exists('getVoyagesPeriodIsCustom') && getVoyagesPeriodIsCustom()) ? ' (période)' : ' (mois)';
$vpRepo = new VoyagePrestataireRepository($con);
$vpStats = $vpRepo->statsExternesBetween(getContextRegions(), getContextEntities(), null, $vpDateFrom, $vpDateTo);
$voyageRepo = new VoyageRepository($con);

// La carte Voyages est masquée en flotte et en comparaison : le nombre de voyages
// est déjà affiché par les cartes du tableau de bord pour ces portées.
$vpShowVoyagesCard = !in_array($vpPageScope, ['flotte', 'comparaison'], true);

if ($vpPageScope === 'comparaison') {
    $vpFleet = $voyageRepo->statsQteFlotteBetween(getContextRegions(), getContextEntities(), null, $vpDateFrom, $vpDateTo);
    $vpQteDisplay = $vpFleet['total_qte_fmt'] . ' / ' . $vpStats['total_qte_fmt'];
    $vpUnite = '';
    $vpQteLabel = 'Qtés transportées' . $vpPeriodSuffix . ' flotte / externes';
} elseif ($vpPageScope === 'flotte') {
    $vpFleet = $voyageRepo->statsQteFlotteBetween(getContextRegions(), getContextEntities(), null, $vpDateFrom, $vpDateTo);
    $vpQteDisplay = $vpFleet['total_qte_fmt'];
    $vpUnite = $vpFleet['unite'];
    $vpQteLabel = 'Qtés transportées' . $vpPeriodSuffix;
} else {
    $vpNbDisplay = number_format($vpStats['nb_voyages'], 0, ',', ' ');
    $vpQteDisplay = $vpStats['total_qte_fmt'];
    $vpUnite = $vpStats['unite'];
    $vpVoyagesLabel = 'Voyages prestataires' . $vpPeriodSuffix;
    $vpQteLabel = 'Qtés transportées' . $vpPeriodSuffix;
}
// Le radio est libre uniquement en portée « tout » ; sinon figé sur la portée de la page
// (externe → Prestataires, flotte → Flotte, comparaison → Les 2).
$vpRadioLocked = $vpPageScope !== 'tout';
$vpRadioChecked = $vpPageScope === 'tout' ? 'externe' : ($vpPageScope === 'comparaison' ? 'tout' : $vpPageScope);
?>
<div class="row g-3 mb-3">
    <?php if ($vpShowVoyagesCard): ?>
    <div class="col-md">
        <div class="lt-card lt-stat-card">
            <div class="lt-stat-icon"><i class="fa fa-handshake"></i></div>
            <div class="lt-stat-value"><span id="stat-vp-voyages"><?= $vpNbDisplay ?></span></div>
            <div class="lt-stat-label" id="stat-vp-voyages-label"><?= h($vpVoyagesLabel) ?></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md">
        <div class="lt-card lt-stat-card">
            <div class="lt-stat-icon"><i class="fa fa-boxes"></i></div>
            <div class="lt-stat-value"><span id="stat-vp-qte"><?= $vpQteDisplay ?></span><span id="stat-vp-unite" class="fs-6 ms-1"><?= h($vpUnite) ?></span></div>
            <div class="lt-stat-label"><span id="stat-vp-qte-label"><?= h($vpQteLabel) ?></span>
                <div class="btn-group btn-group-sm mt-1 w-100" role="group" aria-label="Portée des quantités transportées">
                    <input type="radio" class="btn-check" name="scope-stat-prestataires" id="scope-pt-externe" value="externe"<?= $vpRadioChecked === 'externe' ? ' checked' : '' ?><?= $vpRadioLocked ? ' disabled' : '' ?>>
                    <label class="btn btn-outline-primary" for="scope-pt-externe">Prestataires</label>
                    <input type="radio" class="btn-check" name="scope-stat-prestataires" id="scope-pt-flotte" value="flotte"<?= $vpRadioChecked === 'flotte' ? ' checked' : '' ?><?= $vpRadioLocked ? ' disabled' : '' ?>>
                    <label class="btn btn-outline-primary" for="scope-pt-flotte">Flotte</label>
                    <input type="radio" class="btn-check" name="scope-stat-prestataires" id="scope-pt-tout" value="tout"<?= $vpRadioChecked === 'tout' ? ' checked' : '' ?><?= $vpRadioLocked ? ' disabled' : '' ?>>
                    <label class="btn btn-outline-primary" for="scope-pt-tout">Les 2</label>
                </div>
                <select id="type-chargement-stat" class="form-select form-select-sm mt-1">
                    <option value="0">Tous (mixte)</option>
                    <?php
                    foreach ($voyageRepo->findAllTypesChargement() as $t):
                        echo "<option value='" . $t['id_type_chargement'] . "'>" . h($t['lib_type_chargement']) . "</option>";
                    endforeach;
                    ?>
                </select>
            </div>
        </div>
    </div>
</div>
<script>
    const vpPageScope = '<?= $vpPageScope ?>';
    const vpPeriodSuffix = '<?= h($vpPeriodSuffix) ?>';
    // Période de la page (barre de filtres) — sur la page configuration, mois courant par défaut.
    const vpDateFrom = '<?= h($vpDateFrom) ?>';
    const vpDateTo = '<?= h($vpDateTo) ?>';

    function getStatScope() {
        if (vpPageScope !== 'tout') return vpPageScope;
        return $('input[name="scope-stat-prestataires"]:checked').val() || 'externe';
    }

    // Recharge les stats du bloc selon la portée (bouton radio, figé hors portée « tout »)
    // et le type de chargement — sur la même période que la page.
    function loadStatsPrestataires() {
        $.ajax({
            type: 'post',
            data: 'load-stats-prestataires=1&type-chargement-stat=' + $('#type-chargement-stat').val()
                + '&scope-stat-prestataires=' + getStatScope()
                + '&date-from-stat=' + vpDateFrom
                + '&date-to-stat=' + vpDateTo,
            dataType: 'json'
        }).done((res) => {
            if (res.success) {
                $('#stat-vp-qte').text(res.total_qte_fmt)
                $('#stat-vp-unite').text(res.unite || '')
                $('#stat-vp-voyages').text(res.nb_voyages)
                const voyageLabels = {
                    externe: 'Voyages prestataires' + vpPeriodSuffix,
                    flotte: 'Voyages flotte' + vpPeriodSuffix,
                    tout: 'Voyages' + vpPeriodSuffix
                }
                const qteLabels = {
                    externe: 'Qtés transportées' + vpPeriodSuffix,
                    flotte: 'Qtés transportées' + vpPeriodSuffix,
                    tout: 'Qtés transportées' + vpPeriodSuffix,
                    comparaison: 'Qtés transportées' + vpPeriodSuffix + ' flotte / externes'
                }
                $('#stat-vp-voyages-label').text(voyageLabels[res.scope] || voyageLabels.externe)
                $('#stat-vp-qte-label').text(qteLabels[res.scope] || qteLabels.externe)
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
