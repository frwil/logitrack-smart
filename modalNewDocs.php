<?php if(isset($_POST['nom-doc'])):
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_begin_transaction($con);
try {
    $q=db_exec($con,"INSERT INTO `document_vehicule` (`id_document`, `nom_document`, `validite_document`) VALUES (NULL, ?, ?)", [$_POST['nom-doc'], $_POST['valid-doc']]);
    mysqli_commit($con);
    die("NEWDOC%%%%%%1");
} catch (mysqli_sql_exception $e) {
    mysqli_rollback($con);
    if($e->getCode()==1062) die("NEWDOC%%%%%%1062");
    die("NEWDOC%%%%%%0");
}
endif;
?>
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
            if($(el).val()==''){
                valid=false
                $(el).addClass('is-invalid')
            }
        })
        if(!valid){
            $('#form-new-doc').notify("Tous les champs en rouge sont obligatoires!",{position:'top'})
            return false
        }
        $.ajax({
            type:'post',
            data:$('#form-new-doc').serialize()
        }).done((e)=>{
            let v=e.split('NEWDOC%%%%%%')[1]
            if(v=='1'){
                showSuccess('Enregistrement effectué!')
                location.reload()
            }else if(v=='1062'){
                $('#modal-doc').notify('Ce document existe déjà!',{position:'top'})
            }
            $('#modal-doc').notify("Erreur lors de l'enregistrement !",{position:'top'})
        })
    }

    function openModalDocs(){
        $('#modal-doc').modal('show')
    }
</script>