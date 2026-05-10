<?php /* POST handled by DocumentController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-doc" tabindex="-1" aria-labelledby="modal-docLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modal-docLabel">Document de véhicule (Nouveau)</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" action="#" id="form-new-doc">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" required id="nom-doc" name="nom-doc">
                <label for="nom-doc">Désignation</label>
            </div>
            <div class="form-floating mb-3">
                <input type="number" class="form-control" id="valid-doc" name="valid-doc" min="3" value="3">
                <label for="valid-doc">Validité (en mois)</label>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" onclick="saveDoc()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
    function saveDoc(){
        var valid=true
        $('#form-new-doc *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-doc').notify("Tous les champs en rouge sont obligatoires!",{position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-doc').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess('Enregistrement effectué!')
                location.reload()
            }else if(e.error=='1062'){
                $('#modal-doc').notify('Ce document existe déjà!',{position:'top'})
            } else {
                $('#modal-doc').notify(e.error || "Erreur lors de l'enregistrement !",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-doc').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement !",{position:'top'})
        })
    }

    function openModalDocs(){
        $('#modal-doc').modal('show')
    }
</script>