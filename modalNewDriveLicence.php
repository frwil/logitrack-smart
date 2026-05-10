<?php /* POST handled by DriveLicenceController — see controllers/router.php */ ?>
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
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-drivelicence').notify("Tous les champs en rouge sont obligatoires!",{position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-drivelicence').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess('Enregistrement effectué!')
                location.reload()
            }else if(e.error=='1062'){
                $('#modal-driveLicence').notify('Cette catégorie de permis existe déjà!',{position:'top'})
            } else {
                $('#modal-driveLicence').notify(e.error || "Erreur lors de l'enregistrement !",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-driveLicence').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement !",{position:'top'})
        })
    }

    function openModalDriveLicence(){
        $('#modal-driveLicence').modal('show')
    }
</script>