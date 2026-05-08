<?php /* POST handled by CentreCoutController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-new-centrecout" tabindex="-1" aria-labelledby="modal-new-centrecoutLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modal-new-centrecoutLabel">Nouveau Centre de coûts</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-new-cc">
            <div class="form-floating mb-3">
                <input type="text" id="nom-cc" name="nom-cc" required class="form-control">
                <label for="nom-cc">Désignation Centre de coûts</label>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" onclick="saveCC()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
    function saveCC(){
        var valid=true
        $('#form-new-cc *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-cc').notify('Tous les champs en rouge sont obligatoires!',{ position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-cc').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess('Enregistrement effectué')
                setTimeout(()=>{$('#form-new-cc *').val(''); $('#modal-new-centrecout').modal('hide')},2000)
                <?php if(isset($_GET['extpage'])) : ?> self.close() <?php endif; ?>
            }else if(e.error=='1062'){
                $('#modal-new-centrecout').notify("Ce centre de coût existe déjà",{position:'top'})
            }
            else{
                $('#modal-new-centrecout').notify(e.error || "Erreur lors de l'enregistrement",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-new-centrecout').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement",{position:'top'})
        })
    }
    function openModalCentreCout() {
        $('#modal-new-centrecout').modal('show')
    }
</script>