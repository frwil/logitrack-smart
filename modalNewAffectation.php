<?php
if (isset($_POST['id-vehicule-aff'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q=db_select($con,"select id_vehicule from affectation_vehicule where id_vehicule in(select id_vehicule from vehicule where sha1(concat(id_vehicule,immatriculation_vehicule))=?) and is_ferme=0", [$_POST['id-vehicule-aff']]);
        if(mysqli_num_rows($q)==0):
        $q = db_exec($con, "INSERT INTO `affectation_vehicule` (`id_affectation`, `id_vehicule`, `id_chauffeur`, `id_type_utilisation`, `id_mode_utilisation`, `id_entite`, `objet_affectation`, `date_debut_affectation`, `date_fin_affectation`, `id_region`, `date_affectation`, `is_ferme`) VALUES (NULL, (select id_vehicule from vehicule where sha1(concat(id_vehicule,immatriculation_vehicule))=?), (select id_chauffeur from chauffeur where sha1(concat(id_chauffeur,nom_chauffeur))=?), (select id_type_utilisation from type_utilisation_vehicule where sha1(concat(id_type_utilisation,lib_type_utilisation))=?), (select id_mode_utilisation from mode_utilisation_vehicule where sha1(concat(id_mode_utilisation,nom_mode_utilisation))=?), (select id_entite from entite where sha1(concat(id_entite,nom_entite))=?), ?, ?, ?, (select id_region from region where sha1(concat(id_region,nom_region))=?), CURRENT_TIMESTAMP, '0')", [$_POST['id-vehicule-aff'], $_POST['id-chauffeur-aff'], $_POST['id-typeutilisation-aff'], $_POST['id-modeutilisation-aff'], $_POST['id-entite-aff'], $_POST['objet-aff'] === '' ? null : $_POST['objet-aff'], $_POST['date-debut-aff'], $_POST['date-fin-aff'] === '' ? null : $_POST['date-fin-aff'], $_POST['id-region-aff']]);
        mysqli_commit($con);
        die("NewAffectation%%%%%%1");
        else:
            die("NewAffectation%%%%%%1062");
        endif;
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("NewAffectation%%%%%%0");
    }
endif;
if (isset($_POST['refresh-vehicule'])):
    $q = db_select($con, "select * from vehicule");
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
    endwhile;
    die("NewAffectation%%%%%%$liste");
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
                        <select class="form-select" id="id-vehicule-aff" name="id-vehicule-aff" required>
                            <?php $q = db_select($con, "select * from vehicule left join marque_vehicule on vehicule.id_marque=marque_vehicule.id_marque");
                            while ($r = mysqli_fetch_array($q)):
                                echo "<option value='" . sha1($r[0] . $r['immatriculation_vehicule']) . "'>{$r['immatriculation_vehicule']} - {$r['nom_marque']}</option>";
                            endwhile;
                            ?>
                        </select>
                        <label for="id-vehicule-aff">Véhicule</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select class="form-select" id="id-chauffeur-aff" name="id-chauffeur-aff" required>
                            <?php $q = db_select($con, "select * from chauffeur where 1");
                            while ($r = mysqli_fetch_array($q)):
                                echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                            endwhile;
                            ?>
                        </select>
                        <label for="id-chauffeur-aff">Chauffeur</label>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="id-typeutilisation-aff" name="id-typeutilisation-aff" required>
                                    <?php $q = db_select($con, "select * from type_utilisation_vehicule where 1");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                    endwhile;
                                    ?>
                                </select>
                                <label for="id-typeutilisation-aff">Type utilisation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="id-modeutilisation-aff" name="id-modeutilisation-aff" required>
                                    <?php $q = db_select($con, "select * from mode_utilisation_vehicule where 1");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                    endwhile;
                                    ?>
                                </select>
                                <label for="id-modeutilisation-aff">Mode utilisation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="id-entite-aff" name="id-entite-aff" required>
                                    <?php $q = db_select($con, "select * from entite where 1");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                    endwhile;
                                    ?>
                                </select>
                                <label for="id-entite-aff">Entité</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="id-region-aff" name="id-region-aff" required>
                                    <?php $q = db_select($con, "select * from region where 1");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                    endwhile;
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
            data: 'refresh-marque=1'
        }).done((e) => {
            let v = e.split('NewMarque%%%%%%')[1]
            $('#marque-vh').html(v)
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
            $.notify('Tous les champs en rouge sont obligatoires!!!')
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-affectation').serialize()
        }).done((e) => {
            let v = e.split('NewAffectation%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouvelle affectation créee!!", {
                    className: 'success'
                })
                $('#modal-new-affectation').modal('hide')
                $('#form-new-affectation *').val('')
                location="?page=affectationVehicules"
            } else if (v == '1062') {
                $('#form-new-affectation').notify("Vous devez clôturer l'affectation actuelle de ce véhicule avant de le réaffecter",{
                    position:'top'
                })
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>