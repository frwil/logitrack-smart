<?php if(isset($_POST['lib-type'])):
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_begin_transaction($con);
try {
    $q=db_exec($con,"INSERT INTO `type_permis_vehicule` (`id_type_permis`, `lib_type_permis`, `desc_type_permis`) VALUES (NULL, ?, ?)", [$_POST['lib-type'], $_POST['desc-type'] === '' ? null : $_POST['desc-type']]);
    mysqli_commit($con);
    die("NEWDL%%%%%%1");
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($con);
    if($e->getCode()==1062) die("NEWDL%%%%%%1062");
    die("NEWDL%%%%%%0");
}
endif;
?>
<div class="modal fade" id="modal-driveLicence" tabindex="-1" aria-labelledby="modal-driveLicenceLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modal-driveLicenceLabel">Catégorie de permis (Nouveau)</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" action="#" id="form-new-drivelicence">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" required id="lib-type" name="lib-type">
                <label for="lib-type">Catégorie de permis</label>
            </div>
            <div class="form-floating mb-3">
                <textarea class="form-control" id="desc-type" name="desc-type"></textarea>
                <label for="desc-type">Description</label>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" onclick="saveDriveLicence()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
    function saveDriveLicence(){
        var valid=true
        $('#form-new-drivelicence *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-drivelicence').notify("Tous les champs en rouge sont obligatoires!",{position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-drivelicence').serialize()
        }).done((e)=>{
            let v=e.split('NEWDL%%%%%%')[1]
            if(v=='1'){
                showSuccess('Enregistrement effectué!')
                location.reload()
            }else if(v=='1062'){
                $('#modal-driveLicence').notify('Cette catégorie de permis existe déjà!',{position:'top'})
            }
            $('#modal-driveLicence').notify("Erreur lors de l'enregistrement !",{position:'top'})
        })
    }

    function openModalDriveLicence(){
        $('#modal-driveLicence').modal('show')
    }
</script>