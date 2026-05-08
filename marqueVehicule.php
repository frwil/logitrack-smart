<?php function getTableauMarqueVehicules()
{
    global $con;
    global $rights_vehicule;
    $repo = new MarqueRepository($con);
    $rows = $repo->findAll();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom marque</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['nom_marque']) . "</td><td><div class='btn-group'>".(in_array('upd',$rights_vehicule) ? "<button class='btn btn-light' type='button' onclick='showModalUpdateMarque(" . $r['id_marque'] . ")' title='Modifier la marque " . h($r['nom_marque']) . "'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array("del",$rights_vehicule) ? "<button class='btn btn-danger' type='button' onclick='deleteMarque(" . $r['id_marque'] . ")' title='Supprimer la marque " . h($r['nom_marque']) . "'><i class='fa fa-times'></i></button>" : "")."</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
include("modalNewMarque.php");
?>
<?php /* POST handled by MarqueController — see controllers/router.php */ ?>
<script>
    function showModalUpdateMarque(id) {
        $('#modal-upd-marque').modal('show')
        $('#id-marque').val(id)
        $.ajax({
            type: 'post',
            data: 'id-marque-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#nom-marque-display').html(e.data.nom_marque);
                $('#nom-upd-marque').val(e.data.nom_marque);
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement");
        })
    }

    function updateMarque() {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-marque').serialize(),
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

    function deleteMarque(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-marque-forDel='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Marque supprimée!!')
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
<div class="modal fade" id="modal-upd-marque" tabindex="-1" aria-labelledby="modal-upd-marqeLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-marqueLabel">Marque de véhicule <span id='nom-marque-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-marque">
                    <div class="form-floating mb-3">
                        <input type="hidden" id="id-marque" name="id-marque">
                        <input type="text" id="nom-upd-marque" name="nom-upd-marque" required class="form-control">
                        <label for="nom-upd-marque">Libellé marque</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateMarque()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>