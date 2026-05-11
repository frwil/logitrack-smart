<?php function getTableauChauffeurs()
{
    global $con;
    global $rights_affectation;
    $repo = new ChauffeurRepository($con);
    $rows = $repo->findAll();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom Chauffeur</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['nom_chauffeur']) . "</td><td><div class='btn-group'>";
        if (in_array('upd', $rights_affectation)):
            $tableau .= "<button class='btn btn-light' type='button' title='Modifier le chauffeur " . h($r['nom_chauffeur']) . "' onclick='showModalUpdateChauffeur(\"".$r['id_chauffeur']."\")'><i class='fa fa-pencil-alt'></i></button>";
        endif;
        if (in_array('del', $rights_affectation)):
            $tableau .= "<button class='btn btn-danger' title='Supprimer le chauffeur " . h($r['nom_chauffeur']) . "' onclick='deleteChauffeur(\"".$r['id_chauffeur']."\")'><i class='fa fa-times'></i></button>";
        endif;
        $tableau .= "</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewChauffeur.php'); ?>
<?php /* POST /chauffeur handled by ChauffeurController — see controllers/router.php */ ?>
<?php if(isset($_GET['action']) && $_GET['action']=='new'): ?>
<script>
    setTimeout(()=>{openModalChauffeur()},2000)
</script>
<?php endif; ?>
<script>
    function showModalUpdateChauffeur(id) {
        $('#modal-upd-chauffeur').modal('show')
        $('#id-chauffeur').val(id)
        $.ajax({
            type: 'post',
            data: 'id-chauffeur-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                let v = e.data
                $('#nom-chauffeur-display').html(v.nom_chauffeur);
                $('#nom-upd-chauffeur').val(v.nom_chauffeur);
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }
    function updateChauffeur($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-chauffeur').serialize(),
                dataType:'json'
            }).done((e)=>{
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location="?page=affectationVehicules&subpage=listeChauffeurs"
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function deleteChauffeur(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-chauffeur-forDel='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Chauffeur supprimée!!')
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
<div class="modal fade" id="modal-upd-chauffeur" tabindex="-1" aria-labelledby="modal-upd-chauffeurLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-chauffeurLabel">Chauffeur de véhicule <span id='nom-chauffeur-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-chauffeur">
                    <div class="form-floating mb-3">
                        <input type="hidden" id="id-chauffeur" name="id-chauffeur">
                        <input type="text" id="nom-upd-chauffeur" name="nom-upd-chauffeur" required class="form-control">
                        <label for="nom-upd-chauffeur">Nom chauffeur</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateChauffeur()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>