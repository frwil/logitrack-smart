<?php function getTableauModeleVehicules()
{
    global $con;
    global $rights_vehicule;
    $repo = new ModeleRepository($con);
    $rows = $repo->findAll();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom modele</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['nom_modele_vehicule']) . "</td><td><div class='btn-group'>".(in_array("upd",$rights_vehicule) ? "<button class='btn btn-light' type='button' onclick='showModalUpdateModele(" . $r['id_modele_vehicule'] . ")' title='Modifier la modele " . h($r['nom_modele_vehicule']) . "'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array("del",$rights_vehicule) ? "<button class='btn btn-danger' type='button' onclick='deleteModele(" . $r['id_modele_vehicule'] . ")' title='Supprimer la modele " . h($r['nom_modele_vehicule']) . "'><i class='fa fa-times'></i></button>" : "")."</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
include("modalNewModele.php");
?>
<?php /* POST handled by ModeleController — see controllers/router.php */ ?>
<script>
    function showModalUpdateModele(id) {
        $('#modal-upd-modele').modal('show')
        $('#id-modele').val(id)
        $.ajax({
            type: 'post',
            data: 'id-modele-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#nom-modele-display').html(e.data.nom_modele_vehicule);
                $('#nom-upd-modele').val(e.data.nom_modele_vehicule);
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement");
        })
    }

    function updateModele() {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-modele').serialize(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location.reload()
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function deleteModele(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-modele-forDel='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Modele supprimé!!')
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
<div class="modal fade" id="modal-upd-modele" tabindex="-1" aria-labelledby="modal-upd-marqeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-modeleLabel">Modele de véhicule <span id='nom-modele-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-modele">
                    <div class="form-floating mb-3">
                        <input type="hidden" id="id-modele" name="id-modele">
                        <input type="text" id="nom-upd-modele" name="nom-upd-modele" required class="form-control">
                        <label for="nom-upd-modele">Libellé modele</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateModele()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>