<?php /* POST handled by VidangeController — see controllers/router.php */ ?>

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
                            <?php $affectationRepo = new AffectationRepository($con);
                            foreach ($affectationRepo->findActiveByRegion((int)$_SESSION['usr-con']['region-sel']) as $r):
                                echo "<option value='" . $r['id_affectation'] . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == $r['id_affectation'] ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                            endforeach;
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
                            <?php $maintenanceRepo = new MaintenanceRepository($con);
                            foreach ($maintenanceRepo->findAllPrestataires() as $r):
                                echo "<option value='" . $r['id_prestataire'] . "'>" . h($r['nom_prestataire']) . "</option>";
                            endforeach;
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
            data:$('#form-new-vidange').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess('Enregistrement effectué')
                location.reload()
            }else{
                $('#modal-new-vidange').notify(e.error || "Erreur lors de l'enregistrement!",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-new-vidange').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!",{position:'top'})
        })
    }
    function openModalVidange() {
        $('#modal-new-vidange').modal('show')
    }
</script>