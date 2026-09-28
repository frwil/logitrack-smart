<?php /* POST handled by PrestataireTransportController — see controllers/router.php */ ?>
<div class="modal fade" id="modal-upd-prestataire-transport" tabindex="-1" aria-labelledby="modal-upd-prestataire-transportLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="modal-upd-prestataire-transportLabel">Modifier prestataire de transport</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="form-upd-pt-transport">
            <input type="hidden" id="id-prestataire-transport-upd" name="id-prestataire-transport-upd">
            <div class="form-floating mb-3">
                <input type="text" id="societe-pt-transport-upd" name="societe-pt-transport-upd" required class="form-control">
                <label for="societe-pt-transport-upd">Nom de la société</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="adresse-pt-transport-upd" name="adresse-pt-transport-upd" class="form-control">
                <label for="adresse-pt-transport-upd">Adresse</label>
            </div>
            <div class="form-floating mb-3">
                <input type="text" id="telephone-pt-transport-upd" name="telephone-pt-transport-upd" class="form-control">
                <label for="telephone-pt-transport-upd">Contact téléphonique</label>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
        <button type="button" class="btn btn-primary" onclick="savePTTransportUpd()">Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<script>
    function updPrestataireTransport(id) {
        $('#modal-upd-prestataire-transport').modal('show')
        $('#id-prestataire-transport-upd').val(id)
        $.ajax({
            type:'post',
            data:'id-prestataire-transport-forModal='+id,
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                $('#societe-pt-transport-upd').val(e.nom_societe || '')
                $('#adresse-pt-transport-upd').val(e.adresse_societe || '')
                $('#telephone-pt-transport-upd').val(e.telephone_societe || '')
            }
            else{
                $('#modal-upd-prestataire-transport').notify(e.error || "Erreur lors du chargement",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-upd-prestataire-transport').notify(jqXHR.responseJSON?.error || "Erreur lors du chargement",{position:'top'})
        })
    }
    function savePTTransportUpd(){
        var valid=true
        $('#form-upd-pt-transport *[required]').each((e,el)=>{
            $(el).removeClass('is-invalid')
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-upd-pt-transport').notify('Tous les champs en rouge sont obligatoires!',{ position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-upd-pt-transport').serialize(),
            dataType:'json'
        }).done((e)=>{
            if(e.success){
                showSuccess('Prestataire modifié')
                $('#modal-upd-prestataire-transport').modal('hide')
                if($('#modal-new-voyage').hasClass('show')){
                    // Modification lancée depuis le formulaire Nouveau voyage :
                    // on met à jour le select sur place pour ne pas perdre le voyage en cours.
                    refreshPrestataireOption(e.id, e.label, e.immat)
                    refreshBtnEditPrestataire()
                }
                else{
                    // Modification lancée depuis la liste des prestataires.
                    location.reload()
                }
            }
            else{
                $('#modal-upd-prestataire-transport').notify(e.error || "Erreur lors de l'enregistrement",{position:'top'})
            }
        }).fail((jqXHR)=>{
            $('#modal-upd-prestataire-transport').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement",{position:'top'})
        })
    }
    function delPrestataireTransport(id){
        if(confirm("Êtes-vous sûr de vouloir supprimer ce prestataire ?\nLes voyages déjà enregistrés resteront rattachés à ce prestataire.")){
            $.ajax({
                type:'post',
                data:'id-prestataire-transport-del='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Prestataire supprimé')
                    location.reload()
                }
                else{
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }
</script>
