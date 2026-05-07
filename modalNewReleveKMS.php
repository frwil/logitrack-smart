<?php
if (isset($_POST['idvhlkms'])):
    $q = mysqli_query($con, "select max(km_releve) from releve_kms_vehicule where id_affectation_vehicule=(select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))='{$_POST['idvhlkms']}') and date_fin_periode_releve<='{$_POST['dtkms']}' order by date_fin_periode_releve desc");
    $km_releve = 0;
    while ($r = mysqli_fetch_array($q)):
        $km_releve = $r[0] != "" ? $r[0] : 0;
    endwhile;
    die("CHECKLASTKM%%%%%%$km_releve");
endif;
if (isset($_POST['per-releve'])):
    $per = json_decode($_POST['per-releve']);
    $options = array();
    for ($i = 0; $i < count($per); $i++):
        $q = mysqli_query($con, "select * from releve_kms_vehicule where periode_releve='Semaine " . ($i + 1) . "' and id_affectation_vehicule=(select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))='{$_POST['id-vh']}') and date_debut_periode_releve='{$per[$i]->start}' and date_fin_periode_releve='{$per[$i]->end}'");
        $options[$i] = mysqli_num_rows($q);
    endfor;
    die("PERRELEVE%%%%%%" . json_encode($options));
endif;
if (isset($_POST['date-releve-kms'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {

        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
        $q = mysqli_query($con, "INSERT INTO `releve_kms_vehicule` (`id_releve`, `date_releve`, `km_releve`, `id_affectation_vehicule`, `periode_releve`, `date_debut_periode_releve`, `date_fin_periode_releve`,semaine_annee) VALUES (NULL, CURRENT_TIMESTAMP, '{$_POST['val-releve-kms']}', (select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))='{$_POST['vh-releve-kms']}'),'Semaine ".date('W',strtotime($_POST['start-per']))."','{$_POST['start-per']}','{$_POST['end-per']}',weekofyear('{$_POST['start-per']}'))");
        mysqli_commit($con);
        die("NewReleveKMS%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("NewReleveKMS%%%%%%0");
    }
endif;
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
                    <div class="form-floating mb-3">
                        <select required id="vh-releve-kms" name="vh-releve-kms" class="form-select" onchange="$('#date-releve-kms').change();">
                            <?php echo "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0 and affectation_vehicule.id_region " . ($_SESSION['usr-con']['region-sel'] != '' ? "=({$_SESSION['usr-con']['region-sel']})" : "=''");
                            $q = mysqli_query($con, "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0 and affectation_vehicule.id_region " . ($_SESSION['usr-con']['region-sel'] != '' ? "=({$_SESSION['usr-con']['region-sel']})" : "=''"));
                            while ($r = mysqli_fetch_array($q)):
                                echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r[0] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                            endwhile;
                            ?>
                        </select>
                        <label for="vh-releve-kms">Véhicule</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="month" required id="date-releve-kms" name="date-releve-kms" value="<?php if (isset($_GET['dch'])) echo date('Y-m', strtotime($_GET['dch']));
                                                                                                        else echo date('Y-m'); ?>" class="form-control" onchange="getSemaines($('#vh-releve-kms').val(),this.value,'<?php if (isset($_GET['dch'])) echo $_GET['dch'];
                                                                                                                                                                                                                                                                                            else echo ''; ?>')" <?php if (isset($_GET['dch'])) echo "readonly"; ?>>
                        <label for="date-releve-kms">Période</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select required name="per-releve-kms" id="per-releve-kms" class="form-select">

                        </select>
                        <label for="per-releve-kms">Semaine</label>
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
    moment.updateLocale('en', {
        week: {
            dow: 1
        }
    });

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
        $('#date-releve-kms').change()
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
            data: 'idvhlkms=' + id + '&dtkms=' + dt
        }).done((e) => {
            let v = e.split("CHECKLASTKM%%%%%%")[1];
            $('#val-releve-kms').val(v)
        })
    }

    function saveReleve() {
        var valid = true
        $('#form-new-relevekms *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
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
            data: $('#form-new-relevekms').serialize() + '&start-per=' + $('#per-releve-kms option[value="' + $('#per-releve-kms').val() + '"]').attr('wk-st') + '&end-per=' + $('#per-releve-kms option[value="' + $('#per-releve-kms').val() + '"]').attr('wk-ed')
        }).done((e) => {
            let v = e.split('NewReleveKMS%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouveau relevé enregistré!!", {
                    className: 'success'
                })
                $('#modal-new-relevekms').modal('hide')
                $('#form-new-relevekms *').val('')
                location.reload()
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }

    function getSemaines(vh, v, dt = '') {
        let d = v.split('-')
        var weeks = getWeeksOfMonthWithMoment(d[0], d[1])
        var options = "";
        $.ajax({
            type: 'post',
            data: 'per-releve=' + JSON.stringify(weeks) + '&id-vh=' + vh
        }).done((e) => {
            let v
            try {
                v = JSON.parse(e.split('PERRELEVE%%%%%%')[1])
            } catch (err) {
                v = null
            }
            for (i = 0; i < weeks.length; i++) {
                options += "<option " + (v != null ? (v[i] > 0 ? 'disabled' : '') : '') + " value='Semaine " + (i + 1) + "' wk-st='" + weeks[i].start + "' wk-ed='" + weeks[i].end + "' " + (dt != '' && moment(dt).isBetween(moment(weeks[i].start), moment(weeks[i].end)) ? 'selected' : (dt!='' ? 'disabled' : '')) + ">Semaine " + (i + 1) + " (" + moment(weeks[i].start).format('DD MMM YYYY') + " - " + moment(weeks[i].end).format('DD MMM YYYY') + ")</option>"
            }
            $('#per-releve-kms').html(options)
            getLastKms(vh, dt)
        })

    }
</script>