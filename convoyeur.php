<?php function getTableauConvoyeurs()
{
    global $con;
    $repo = new ConvoyeurRepository($con);
    $rows = $repo->findAll();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom Convoyeur</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['nom_convoyeur']) . "</td><td><div class='btn-group'><button class='btn btn-light' type='button' title='Modifier le convoyeur " . h($r['nom_convoyeur']) . "' onclick='showModalUpdateConvoyeur(\"".sha1($r['id_convoyeur'].$r['nom_convoyeur'])."\")'><i class='fa fa-pencil-alt'></i></button><button class='btn btn-danger' title='Supprimer le convoyeur " . h($r['nom_convoyeur']) . "' onclick='deleteConvoyeur(\"".sha1($r['id_convoyeur'].$r['nom_convoyeur'])."\")'><i class='fa fa-times'></i></button></div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewConvoyeur.php'); ?>
<?php if(isset($_GET['action']) && $_GET['action']=='new'): ?>
<script>
    setTimeout(()=>{openModalConvoyeur()},2000)
</script>
<?php endif; ?>
<?php /* POST /convoyeur handled by ConvoyeurController — see controllers/router.php */ ?>
<script>
    function showModalUpdateConvoyeur(id) {
        $('#modal-upd-convoyeur').modal('show')
        $('#id-convoyeur').val(id)
        $.ajax({
            type: 'post',
            data: 'id-convoyeur-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                let v = e.data
                $('#nom-convoyeur-display').html(v.nom_convoyeur);
                $('#nom-upd-convoyeur').val(v.nom_convoyeur);
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }
    function updateConvoyeur($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-convoyeur').serialize(),
                dataType:'json'
            }).done((e)=>{
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location="?page=affectationVehicules&subpage=listeConvoyeurs"
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function deleteConvoyeur(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-convoyeur-forDel='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Convoyeur supprimée!!')
                    location.reload()
                }else{
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }
</script>
<div class="modal fade" id="modal-upd-convoyeur" tabindex="-1" aria-labelledby="modal-upd-convoyeurLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-convoyeurLabel">Convoyeur de véhicule <span id='nom-convoyeur-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-convoyeur">
                    <div class="form-floating mb-3">
                        <input type="hidden" id="id-convoyeur" name="id-convoyeur">
                        <input type="text" id="nom-upd-convoyeur" name="nom-upd-convoyeur" required class="form-control">
                        <label for="nom-upd-convoyeur">Nom convoyeur</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateConvoyeur()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>