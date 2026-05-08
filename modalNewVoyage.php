<?php $entityBody = json_decode(file_get_contents('php://input'),true);
if(isset($entityBody['chrelevekms'])):
    $_POST=$entityBody;
    $q=db_select($con,"select * from releve_kms_vehicule where id_affectation_vehicule=(select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?) and date_debut_periode_releve>=? and date_fin_periode_releve<=?", [$_POST['chrelevekms'], $_POST['datevg'], $_POST['finvg']]);
    die("CHECKRELEVE%%%%%%".mysqli_num_rows($q));
endif;
if(isset($_POST['dateV'])):
    $sqlObjVg = "select * from objectif_periode_region where date_objectif_periode=?";
    $paramsObjVg = [$_POST['dateV']];
    if ($_SESSION['usr-con']['region-sel'] != '') { $sqlObjVg .= " and id_region=?"; $paramsObjVg[] = (int)$_SESSION['usr-con']['region-sel']; }
    $q=db_select($con, $sqlObjVg, $paramsObjVg);
    die("checkVoyage%%%%%%".mysqli_num_rows($q));
endif;
if (isset($_POST['trajets'])):
    $trajets = json_decode($_POST['trajets']);
    if (empty($trajets)) $trajets = [''];
    list($placeholders, $trajetParams) = db_in($trajets);
    $q = db_select($con, "select * from destination_voyage where sha1(concat(id_destination,lib_destination)) not in($placeholders) order by lib_destination", $trajetParams);
    $liste = "";
    while ($r = mysqli_fetch_array($q)):
        $liste .= "<option value='" . sha1($r[0] . $r[1]) . "' dest-km='{$r['distance_destination']}'>{$r[1]} ({$r['distance_destination']} km)</option>";
    endwhile;
    die("ListeTrajets%%%%%%$liste");
endif;
if (isset($_POST['id-vehicule-vg'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $nbTrajets = count($_POST['listeTrajets']);
        $q = db_exec($con, "INSERT INTO `voyage` (`id_voyage`, `date_voyage`, `id_affectation`, `qte_carburant`, `convoyeur`, `titre_voyage`, `id_type_chargement`, `qte_chargement`, `commentaire_voyage`) VALUES (NULL, ?, (select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?), ?, ?, ?, (select id_type_chargement from type_chargement_voyage where sha1(concat(id_type_chargement,lib_type_chargement))=?), ?, NULL)", [$_POST['date-vg'], $_POST['id-vehicule-vg'], (int)$_POST['qtecarburant-vg'], $_POST['id-convoyeur-vg'] === '' ? null : $_POST['id-convoyeur-vg'], $_POST['titre-vg'], $_POST['typechargement-vg'], (int)$_POST['qtechargement-vg']]);
        $id_voyage = mysqli_insert_id($con);
        $trajets = $_POST['listeTrajets'];
        for ($i = 0; $i < $nbTrajets; $i++):
            $q = db_exec($con, "INSERT INTO `voyage_vehicule` (`id_voyage_vehicule`, `id_voyage`, `id_destination`, `commentaire_voyage_vehicule`) VALUES (NULL, ?, (select id_destination from destination_voyage where sha1(concat(id_destination,lib_destination))=?), NULL)", [(int)$id_voyage, $trajets[$i]]);
        endfor;
        mysqli_commit($con);
        die("NewVoyage%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewVoyage%%%%%%1062');
        die("NewVoyage%%%%%%0");
    }
endif;
?>
<div class="modal fade" id="modal-new-voyage" tabindex="-1" aria-labelledby="modal-new-voyageLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-voyageLabel">Nouveau voyage</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-voyage">
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
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="id-vehicule-vg" name="id-vehicule-vg">
                                    <?php $sqlVg = "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0";
                                    $paramsVg = [];
                                    if ($_SESSION['usr-con']['region-sel'] != '') { $sqlVg .= " and affectation_vehicule.id_region=?"; $paramsVg[] = (int)$_SESSION['usr-con']['region-sel']; }
                                    $q = db_select($con, $sqlVg, $paramsVg);
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "'>{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                                    endwhile;
                                    ?>
                                </select>
                                <label for="id-vehicule-vg">Véhicule</label>
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
                                <input type="number" class="form-control" id="qtecarburant-vg" value="0" name="qtecarburant-vg" required min="0">
                                <label for="qtecarburant-vg">Carburant consommé (en Litres)</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <select class="form-select" id="typechargement-vg" name="typechargement-vg">
                                    <?php $q = db_select($con, "select * from type_chargement_voyage where 1");
                                    while ($r = mysqli_fetch_array($q)):
                                        echo "<option value='" . sha1($r[0] . $r[1]) . "' val-min='{$r['valeur_min']}' val-max='{$r['valeur_max']}'>{$r[1]}</option>";
                                    endwhile;
                                    ?>
                                </select>
                                <label for="typechargement-vg">Type de chargement</label>
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
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select class="form-select" id="trajet-list-vg" role="trajet">
                                        <?php $q = db_select($con, "select * from destination_voyage where 1 order by lib_destination");
                                        while ($r = mysqli_fetch_array($q)):
                                            echo "<option value='" . sha1($r[0] . $r[1]) . "' dest-km='{$r['distance_destination']}'>{$r[1]} ({$r['distance_destination']} km)</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                    <label for="trajet-list-vg">Trajet</label>
                                </div>
                                <button class="btn btn-primary" onclick="addTrajet()" type="button">Ajouter le trajet</button>
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
    moment.updateLocale('en', {
        week: {
            dow: 1
        }
    });
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
            data: 'trajets=' + JSON.stringify(trajets)
        }).done((e) => {
            let v = e.split('ListeTrajets%%%%%%')[1]
            $('#trajet-list-vg').html(v)
        })
    }

    function rmTrajet(id) {
        for (i = 0; i < trajets.length; i++) {
            console.log(trajets[i])
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

        // Loop to generate characters for the specified length
        for (let i = 0; i < length; i++) {
            const randomInd = Math.floor(Math.random() * characters.length);
            result += characters.charAt(randomInd);
        }
        return result;
    }

    function openModalVoyage() {
        $('#modal-new-voyage').modal('show')
        $('#titre-vg').val('Voyage-<?php echo date('ym'); ?>' + Str_Random(5).toUpperCase())
    }

    async function checkReleveKms(id,dvg,fvg){
        let check=await fetch('',{method:'post',body: JSON.stringify({ chrelevekms: id,datevg:dvg,finvg:fvg,csrf_token:window.CSRF_TOKEN }),headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    }})
        check=await check.text()
        check=check.split('CHECKRELEVE%%%%%%')[1]
        return check==1;
    }

   async function saveVoyage() {
        var valid = true
        $('#form-new-voyage *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
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
                location='?page=voyages&subpage=listeObjectifsVoyages&action=new'
            }
            return false
        }
        valid=await checkReleveKms($('#id-vehicule-vg').val(),moment($('#date-vg').val()).startOf('week').format('YYYY-MM-DD'),moment($('#date-vg').val()).endOf('week').format('YYYY-MM-DD'))
        if(!valid){
            /*if(confirm("Aucun relevé de kilométrage n'a été fait pour ce véhicule cette semaine.\nVoulez-vous procéder au relevé du km ?\nVous devrez peut-être contacter votre administrateur si vous n'avez pas les droits d'acces.")){
                <?php /*if(in_array("view",$rights_maintenance)): echo "window.open('?page=maintenances&subpage=releveKms&action=new&idvgch='+$('#id-vehicule-vg').val()+'&dch='+$('#date-vg').val());"; 
                else : ?>
                alert("Vous n'avez pas les droits!\nContactez votre administrateur.")
                <?php endif;*/ ?>
                return false
            }else{
                return false;
            }*/
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-voyage').serialize() + '&trajets-voyage=' + JSON.stringify(trajets)
        }).done((e) => {
            let v = e.split('NewVoyage%%%%%%')[1]
            if (v == '1') {
                $.notify("Nouveau voyage créee!!", {
                    className: 'success'
                })
                $('#modal-new-voyage').modal('hide')
                $('#form-new-voyage *').val('')
                trajets = []
                location.reload()
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
    $('#date-vg').change((e)=>{
        $.ajax({
            type:'post',
            data:'dateV='+$(e.currentTarget).val()
        }).done((e)=>{
            var v=e.split('checkVoyage%%%%%%')[1]
            if(v=='0'){
                $('#date-check').val(1)
            }
        })
    })

    $('#typechargement-vg').change((e)=>{
        let min=$('#typechargement-vg option[value="'+$('#typechargement-vg').val()+'"]').attr('val-min')
        let max=$('#typechargement-vg option[value="'+$('#typechargement-vg').val()+'"]').attr('val-max')
        console.log(min)
        $('#qtechargement-vg').attr('min',min).val(min)
        $('#qtechargement-vg').attr('max',max)
    })
</script>