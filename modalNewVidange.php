<?php if (isset($_POST['vh-vd'])) :
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $codeVid = 'VID-'.sha1($_POST['vh-vd'].date('Y-m-d h:i:s'));
        $q = db_exec($con, "INSERT INTO `vidange_vehicule` (`id_vidange_vehicule`, `code_vidange`, `id_affectation_vehicule`, `date_vidange`, `km_vidange`, `km_prochaine_vidange`, `id_prestataire`, `commentaire_vidange`, `date_save_vidange`) VALUES (NULL, ?, (select id_affectation from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?), ?, ?, ?, (select id_prestataire from prestataire_intervention where sha1(concat(id_prestataire,nom_prestataire))=?), ?, CURRENT_TIMESTAMP)", [$codeVid, $_POST['vh-vd'], $_POST['date-vd'], $_POST['km-av-vd'], $_POST['km-next-vd'], $_POST['id-pt-vd'], $_POST['comment-vd'] === '' ? null : $_POST['comment-vd']]);
        mysqli_commit($con);
        die("NEWVIDANGE%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("NEWVIDANGE%%%%%%0");
    }
endif;
?>

<div class="modal fade" id="modal-new-vidange" tabindex="-1" aria-labelledby="modal-new-vidangeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-vidangeLabel">Nouvelle Vidange</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-new-vidange">
                    <div class="form-floating mb-3">
                        <input type="date" id="date-vd" name="date-vd" required class="form-control">
                        <label for="date-vd">Date Vidange</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select id="vh-vd" name="vh-vd" class="form-select">
                            <?php $sqlVd = "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0";
                            $paramsVd = [];
                            if ($_SESSION['usr-con']['region-sel'] != '') { $sqlVd .= " and affectation_vehicule.id_region=?"; $paramsVd[] = (int)$_SESSION['usr-con']['region-sel']; }
                            $q = db_select($con, $sqlVd, $paramsVd);
                            while ($r = mysqli_fetch_array($q)):
                                echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r[0] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                            endwhile;
                            ?>
                        </select>
                        <label for="vh-vd">Véhicule</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="km-av-vd" name="km-av-vd" required min="0" value="0">
                        <label for="km-av-vd">Km (avant vidange)</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" id="km-next-vd" name="km-next-vd" required min="0" value="0">
                        <label for="km-av-vd">Km (prochaine vidange)</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select class="form-select" id="id-pt-vd" name="id-pt-vd">
                            <?php $q=db_select($con,"select * from prestataire_intervention");
                            while($r=mysqli_fetch_array($q)):
                                echo "<option value='".sha1($r[0].$r[1])."'>{$r[1]}</option>";
                            endwhile;
                            ?>
                        </select>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="comment-vd" name="comment-vd"></textarea>
                        <label for="comment-vd">Commentaire</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveVidange()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveVidange(){
        var valid=true
        $('#form-new-vidange *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#modal-new-vidange .modal-body').notify("Tous les champs en rouge sont obligatoires",{position:'top'})
            return false
        }
        if(parseInt($('#km-av-vd').val())>=parseInt($('#km-next-vd').val())){
            $('#form-new-vidange').notify("Le km de prochaine vidange doit être supérieur au km avant vidange",{position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-vidange').serialize()
        }).done((e)=>{
            let v=e.split('NEWVIDANGE%%%%%%')[1]
            if(v=='1'){
                alert('Enregistrement effectué')
                location.reload()
            }else{
                $('#modal-new-vidange').notify("Erreur lors de l'enregistrement!",{position:'top'})
            }
        })
    }
    function openModalVidange() {
        $('#modal-new-vidange').modal('show')
    }
</script>