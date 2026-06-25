<?php function getTableauObjectifs()
{
    global $con;
    global $rights_voyage;
    $repo = new ObjectifRepository($con);
    $dateFrom = isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01');
    $dateTo = isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t');
    $rows = $repo->findByDateRangeAndRegionsWithNames($dateFrom, $dateTo, getContextRegions(), getContextEntities());
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Date</th><th>Région</th><th>Entité</th><th>Objectif voyages</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['date_objectif_periode']) . "</td><td>" . h($r['nom_region'] ?? '—') . "</td><td>" . h($r['nom_entite'] ?? '—') . "</td><td>" . h($r['objectif']) . "</td><td><div class='btn-group'>".(in_array('upd',$rights_voyage) ? "<button class='btn btn-light' type='button' title='Modifier l'objectif " . h($r['date_objectif_periode']) . "' onclick='showModalUpdateObjectif(\"".$r['id_objectif_periode']."\")'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array('del',$rights_voyage) ? "<button class='btn btn-danger' title='Supprimer le objectif " . h($r['date_objectif_periode']) . "' onclick='deleteObjectif(\"".$r['id_objectif_periode']."\")'><i class='fa fa-times'></i></button>" : "")."</div></td></tr>";
        $i++;
    endforeach;
    $form="<form method='post' action='?page=voyages&subpage=listeObjectifsVoyages' class='row'><div class='col-4'><div class='form-floating'><input type='date' value='".(isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01'))."' class='form-control' id='date-f' name='date-f'><label for='date-f'>Date début</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='".(isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t'))."'><label for='date-t'>Date fin</label></div></div><div class='col-4'><button class='btn btn-primary'>Afficher</button></div></form>";
    $tableau .= "</tbody></table>";
    return $form."<hr>".$tableau;
}
?>
<?php include('modalNewObjectif.php'); ?>
<?php /* POST handled by ObjectifController — see controllers/router.php */ ?>
<?php if(isset($_GET['action']) && $_GET['action']=='new'): ?>
<script>
    setTimeout(()=>{openModalObjectif()},500)
</script>
<?php endif; ?>
<script>
    function showModalUpdateObjectif(id) {
        $('#modal-upd-objectif').modal('show')
        $('#id-objectif').val(id)
        $.ajax({
            type: 'post',
            data: 'id-objectif-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#date-objectif-display').html(e.data.date_objectif_periode);
                $('#date-upd-objectif').val(e.data.date_objectif_periode);
                $('#objectif-upd').val(e.data.objectif)
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement");
        })
    }
    function updateObjectif($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-objectif').serialize(),
                dataType:'json'
            }).done((e)=>{
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location="?page=voyages&subpage=listeObjectifsVoyages"
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function deleteObjectif(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-objectif-forDel='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Objectif supprimé!!')
                    location.reload()
                }else{
                    showError(e.error || "Echec de l'operation")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Echec de l'operation")
            })
        }
    }
</script>
<div class="modal fade" id="modal-upd-objectif" tabindex="-1" aria-labelledby="modal-upd-objectifLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-objectifLabel">Objectif de voyage du <span id='date-objectif-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-objectif">
                    <div class="form-floating mb-3">
                        <input type="hidden" id="id-objectif" name="id-objectif">
                        <input type="date" id="date-upd-objectif" name="date-upd-objectif" required class="form-control">
                        <label for="date-upd-objectif">Date</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="number" id="objectif-upd" name="objectif-upd" required class="form-control" min="0">
                        <label for="objectif-upd">Objectif du jour</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateObjectif()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>