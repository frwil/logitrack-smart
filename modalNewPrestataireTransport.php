<?php /* POST handled by PrestataireTransportController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-prestataire-transport" tabindex="-1" aria-labelledby="modal-new-prestataire-transportLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modal-new-prestataire-transportLabel">Nouveau prestataire de transport</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-new-pt-transport">
            <div class="form-floating mb-3">
                <input type="text" id="societe-pt-transport" name="societe-pt-transport" required class="form-control">
                <label for="societe-pt-transport">Nom de la société</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="immat-pt-transport" name="immat-pt-transport" required class="form-control">
                <label for="immat-pt-transport">Immatriculation</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="adresse-pt-transport" name="adresse-pt-transport" class="form-control">
                <label for="adresse-pt-transport">Adresse</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="telephone-pt-transport" name="telephone-pt-transport" class="form-control">
                <label for="telephone-pt-transport">Contact téléphonique</label>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" onclick="savePTTransport()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
    function savePTTransport(){
        var valid=true
        $('#modal-new-prestataire-transport *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-pt-transport').notify('Tous les champs en rouge sont obligatoires!',{ position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-pt-transport').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                $('#id-prestataire-vg').append(new Option(e.label, e.id, true, true))
                $('#form-new-pt-transport *').val('')
                $('#modal-new-prestataire-transport').modal('hide')
                showSuccess('Prestataire enregistré')
            }
            else{
                $('#modal-new-prestataire-transport').notify(e.error || "Erreur lors de l'enregistrement",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-new-prestataire-transport').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement",{position:'top'})
        })
    }
    function openModalPrestataireTransport() {
        $('#modal-new-prestataire-transport').modal('show')
    }
</script>
