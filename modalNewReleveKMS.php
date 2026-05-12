<?php
/* POST handled by MaintenanceController — see controllers/router.php */
?>
<div class="modal fade" id="modal-new-relevekms" tabindex="-1" aria-labelledby="modal-new-relevekmsLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-relevekmsLabel">Nouveau relevé de kilométrage</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-relevekms">
                    <div class="mb-3">

                        <label for="vh-releve-kms">Véhicule</label>

                        <select required id="vh-releve-kms" name="vh-releve-kms" onchange="$('#date-releve-kms').change();">
                            <?php $affectationRepo = new AffectationRepository($con);
                            $rows = $affectationRepo->findActiveByContext(getContextRegions(), getContextEntities());
                            foreach ($rows as $r):
                                $hash = $r['id_affectation'];
                                echo "<option value='" . $hash . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == $hash ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                            endforeach;
                            ?>
                        </select>

                    </div>
                    <div class="form-floating mb-3">
                        <input type="month" required id="date-releve-kms" name="date-releve-kms" value="<?php if (isset($_GET['dch'])) echo date('Y-m', strtotime($_GET['dch']));
                                                                                                        else echo date('Y-m'); ?>" class="form-control" onchange="getSemaines($('#vh-releve-kms').val(),this.value,<?php echo isset($_GET['dch']) ? j($_GET['dch']) : "''"; ?>)" <?php if (isset($_GET['dch'])) echo "readonly"; ?>>
                        <label for="date-releve-kms">Période</label>
                    </div>
                    <div class="mb-3">

                        <label for="per-releve-kms">Semaine</label>

                        <select required name="per-releve-kms" id="per-releve-kms" class="no-tom-select">

                        </select>

                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" min="0" value="0" required id="val-releve-kms" name="val-releve-kms" class="form-control">
                        <label for="val-releve-kms">Km (valeur)</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveReleve()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    <?php if (isset($_GET['dch'])) : ?>
        $('#date-releve-kms').change()
        setTimeout(() => {
            $('#val-releve-kms').select()
        }, 3000)

    <?php endif; ?>

    function getWeeksOfMonthWithMoment(year, month) {
        const startOfMonth = moment([year, month - 1]).startOf('month');
        const endOfMonth = moment([year, month - 1]).endOf('month');

        const weeks = [];
        let currentWeekStart = startOfMonth.clone().startOf('week');

        while (currentWeekStart.isSameOrBefore(endOfMonth)) {
            const currentWeekEnd = currentWeekStart.clone().endOf('week');

            weeks.push({
                start: currentWeekStart.format('YYYY-MM-DD'),
                end: currentWeekEnd.format('YYYY-MM-DD'),
            });
            currentWeekStart.add(1, 'week');
        }

        return weeks;
    }

    function openModalReleve() {
        $('#modal-new-relevekms').modal('show')
        $('#modal-new-relevekms').one('shown.bs.modal', function() {
            $('#date-releve-kms').change()
        })
    }
    const releveModal = document.getElementById('modal-new-relevekms')
    if (releveModal) {
        releveModal.addEventListener('hide.bs.modal', event => {
            <?php if (isset($_GET['idvgch'])) : ?>
                self.close()
            <?php endif; ?>
        })
    }

    function getLastKms(id, dt) {
        $.ajax({
            type: 'post',
            data: 'idvhlkms=' + id + '&dtkms=' + dt,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#val-releve-kms').val(e.km)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function saveReleve() {
        var valid = true
        $('#form-new-relevekms *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-relevekms').notify("Tous les champs en rouge sont obligatoire!!!", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-relevekms').serialize() + '&start-per=' + $('#per-releve-kms option[value="' + $('#per-releve-kms').val() + '"]').attr('wk-st') + '&end-per=' + $('#per-releve-kms option[value="' + $('#per-releve-kms').val() + '"]').attr('wk-ed'),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau relevé enregistré!!")
                $('#modal-new-relevekms').modal('hide')
                $('#form-new-relevekms *').val('')
                location.reload()
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }

    function getSemaines(vh, v, dt = '') {
        let d = v.split('-')
        var weeks = getWeeksOfMonthWithMoment(d[0], d[1])
        var options = "";
        $.ajax({
            type: 'post',
            data: 'per-releve=' + JSON.stringify(weeks) + '&id-vh=' + vh,
            dataType: 'json'
        }).done((e) => {
            let v = e.success ? e.data : null
            for (i = 0; i < weeks.length; i++) {
                options += "<option " + (v != null ? (v[i] > 0 ? 'disabled' : '') : '') + " value='Semaine " + (i + 1) + "' wk-st='" + weeks[i].start + "' wk-ed='" + weeks[i].end + "' " + (dt != '' && moment(dt).isBetween(moment(weeks[i].start), moment(weeks[i].end)) ? 'selected' : (dt!='' ? 'disabled' : '')) + ">Semaine " + (i + 1) + " (" + moment(weeks[i].start).format('DD MMM YYYY') + " - " + moment(weeks[i].end).format('DD MMM YYYY') + ")</option>"
            }
            $('#per-releve-kms').html(options)
            getLastKms(vh, dt)
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement des semaines")
        })

    }
</script>