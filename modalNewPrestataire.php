<?php /* POST handled by PrestataireController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-prestataire" tabindex="-1" aria-labelledby="modal-new-prestataireLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modal-new-prestataireLabel">Nouveau Prestataire</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-new-pt">
            <div class="form-floating mb-3">
                <input type="text" id="nom-pt" name="nom-pt" required class="form-control">
                <label for="nom-pt">Nom Prestataire</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="contact-pt" name="contact-pt" class="form-control">
                <label for="contact-pt">Contact</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="localisation-pt" name="localisation-pt">
                <label for="localisation-pt">Localisation</label>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" onclick="savePT()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
    function savePT(){
        var valid=true
        $('#modal-new-prestataire *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-pt').notify('Tous les champs en rouge sont obligatoires!',{ position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-pt').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess('Enregistrement effectué')
                setTimeout(()=>{$('#modal-new-prestataire *').val(''); $('#modal-new-prestataire').modal('hide')},2000)
            }else if(e.error=='1062'){
                $('#modal-new-prestataire').notify("Ce prestataire existe déjà",{position:'top'})
            }
            else{
                $('#modal-new-prestataire').notify(e.error || "Erreur lors de l'enregistrement",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-new-prestataire').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement",{position:'top'})
        })
    }
    function openModalPrestataire() {
        $('#modal-new-prestataire').modal('show')
    }
</script>