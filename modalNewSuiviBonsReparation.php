<?php if (isset($_POST['num-br'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $_POST['num-br'] = strtoupper(trim($_POST['num-br']));
        $_POST['diagnostic-br'] = strtoupper(trim($_POST['diagnostic-br']));
        $_POST['destination-br'] = strtoupper(trim($_POST['destination-br']));
        $_POST['observation-br'] = strtoupper(trim($_POST['observation-br']));
        $q = db_exec($con, "INSERT INTO `bons_reparation` (`id_bon_reparation`, `num_bon_reparation`, `id_affectation_vehicule`, `date_entree`, `diagnostic`, `type_execution`, `id_prestataire`, `montant_reparation`, `id_plus_ou_moins_value`, `plus_ou_moins_value_valeur`, `destination_bon`, `duree_reparation`, `date_justification`, `id_centre_cout`, `date_prevue_sortie`, `date_fin_reparation`, `observations`, `cloture_reparation`) VALUES (NULL, ?, (select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?), ?, ?, ?, (select id_prestataire from prestataire_intervention where sha1(concat(id_prestataire,nom_prestataire))=?), ?, (select id_plus_ou_moins_value from plus_ou_moins_value where sha1(concat(id_plus_ou_moins_value,lib_plus_ou_moins_value))=?), ?, ?, ?, ?, (select id_centre_cout from centre_couts where sha1(concat(id_centre_cout,lib_centre_cout))=?), ?, ?, ?, '0')", [$_POST['num-br'], $_POST['vh-br'], $_POST['date-entree-br'], $_POST['diagnostic-br'], $_POST['type-execution-br'], $_POST['prestataire-br'], $_POST['montant-br'], $_POST['plus-moins-br'], $_POST['plus-moins-val-br'], $_POST['destination-br'], $_POST['duree-br'], $_POST['date-justif-br'], $_POST['centrecout-br'], $_POST['date-prevue-br'] === '' ? null : $_POST['date-prevue-br'], $_POST['date-fin-br'] === '' ? null : $_POST['date-fin-br'], $_POST['observation-br'] === '' ? null : $_POST['observation-br']]);
        mysqli_commit($con);
        die("NEWBR%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NEWBR%%%%%%1062');
        die("NEWBR%%%%%%0");
    }
endif;
if (isset($_POST['load-cc-br'])):
    $q = db_select($con, "select * from centre_couts");
    $options = "";
    while ($r = mysqli_fetch_array($q)):
        $options .= "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
    endwhile;
    if (mysqli_num_rows($q) == 0) $options = "<option value=''></option>";
    die("LOADCCBR%%%%%%$options");
endif;
?>
<div class="modal fade" id="modal-new-suiviBonsReparation" tabindex="-1" aria-labelledby="modal-new-suiviBonsReparationLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-suiviBonsReparationLabel">Nouveau suivi des réparations</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-br" class="row">
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="num-br" name="num-br" readonly required class="form-control" value="BR-<?php echo date('YmdHi-s'); ?>">
                            <label for="num-br">N° Bon de réparation</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <select id="vh-br" name="vh-br" class="form-select">
                                <?php $sqlBr = "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0";
                                $paramsBr = [];
                                if ($_SESSION['usr-con']['region-sel'] != '') { $sqlBr .= " and affectation_vehicule.id_region=?"; $paramsBr[] = (int)$_SESSION['usr-con']['region-sel']; }
                                $q = db_select($con, $sqlBr, $paramsBr);
                                while ($r = mysqli_fetch_array($q)):
                                    echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r[0] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                                endwhile;
                                ?>
                            </select>
                            <label for="vh-br">Véhicule</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="date-entree-br" name="date-entree-br" required value="<?php echo date('Y-m-d'); ?>">
                            <label for="date-entree-br">Date d'entrée</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="diagnostic-br" name="diagnostic-br" required></textarea>
                            <label for="diagnostic-br">Diagnostic</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="type-execution-br" name="type-execution-br">
                                <option value="0">Interne</option>
                                <option value="1">Externe</option>
                            </select>
                            <label for="type-execution-br">Type d'exécution</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group mb-3">
                            <div class="form-floating">
                                <select class="form-select" id="prestataire-br" name="prestataire-br">
                                    <?php $q = db_select($con, "select * from prestataire_intervention");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                    endwhile;
                                    if (mysqli_num_rows($q) == 0) echo "<option value=''></option>";
                                    ?>
                                </select>
                                <label for="prestataire-br">Prestataire</label>
                            </div>
                            <a class="btn btn-primary" style="padding:15px" href="?page=maintenances&subpage=prestataire&action=new&extpage" target="blank" title="Nouveau prestataire"><i class="fa fa-plus"></i></a>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="montant-br" name="montant-br" required min="0" value="0" class="form-control">
                            <label for="montant-br">Montant réparation</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="plus-moins-br" name="plus-moins-br">
                                <?php $q = db_select($con, "select * from plus_ou_moins_value");
                                while ($r = mysqli_fetch_array($q)):
                                    echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                endwhile;
                                if (mysqli_num_rows($q) == 0) echo "<option value=''></option>";
                                ?>
                            </select>
                            <label for="plus-moins-br">Type Valeur additionnelle</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="plus-moins-val-br" name="plus-moins-val-br" required value="0" min="0" class="form-control">
                            <label for="plus-moins-val-br">Valeur additionnelle</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="text" id="destination-br" name="destination-br" required class="form-control">
                            <label for="destination-br">Destination</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="number" id="duree-br" name="duree-br" required value="0" min="0" class="form-control">
                            <label for="duree-br">Durée (en jours)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-justif-br" name="date-justif-br" required class="form-control">
                            <label for="date-justif-br">Date Justification</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="input-group mb-3">
                            <div class="form-floating">
                                <select class="form-select" id="centrecout-br" name="centrecout-br" required>
                                    <?php $q = db_select($con, "select * from centre_couts");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                    endwhile;
                                    if (mysqli_num_rows($q) == 0) echo "<option value=''></option>";
                                    ?>
                                </select>
                                <label for="centrecout-br">Centre de coûts</label>
                            </div>
                            <button class="btn btn-primary" style="padding:15px" onclick="var win = window.open('?page=maintenances&subpage=centreCouts&action=new&extpage', '_blank'); setTimeout(()=>{win.addEventListener('beforeunload', function(event) {loadCentreCouts()})},5000);" type="button" title="Nouveau centre de coûts"><i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" name="date-prevue-br" id="date-prevue-br" class="form-control">
                            <label for="date-prevue-br">Date prévue sortie</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <input type="date" name="date-fin-br" id="date-fin-br" class="form-control">
                            <label for="date-fin-br">Date effective de fin</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="observation-br" name="observation-br"></textarea>
                            <label for="observation-br">Observations</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveBR()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveBR() {
        var valid = true
        $('#form-new-br *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
            }
        })
        if (!valid) {
            $('#form-new-br').notify("Tous les champs en rouge sont obligatoires!", {
                position: 'top'
            })
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-br').serialize()
        }).done((e) => {
            let v = e.split('NEWBR%%%%%%')[1]
            if (v == '1') {
                showSuccess('Enregistrement effectué!')
                location.reload()
            } else {
                $('#form-new-br').notify("Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            }
        })
    }

    function openModalSuiviBonsReparation() {
        $('#modal-new-suiviBonsReparation').modal('show')
    }

    function loadCentreCouts() {
        $.ajax({
            type: 'post',
            data: 'load-cc-br=1'
        }).done((e) => {
            let v = e.split('LOADCCBR%%%%%%')[1]
            $('#centrecout-br').html(v)
        })
    }
</script>