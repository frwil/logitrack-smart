<?php function getTableauAffectations()
{
    global $con;
    global $rights_affectation;
    $repo = new AffectationRepository($con);
    $rows = $repo->findAllByContext(getContextRegions(), getContextEntities());
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Immatriculation</th><th>Chauffeur</th><th>Région</th><th>Entité</th><th>Type d'utilisation</th><th>Mode d'utilisation</th><th>Objet d'affectation</th><th>Date début</th><th>Date fin</th><th>Clôturé</th><th></th></tr></thead><tbody>";
    $i = 1;
    //$user_rights = $_SESSION['usr-con']['users-rights'];
    //$rights_affectation = explode(',', isRightObjectAllowed('affectationVehicules', $user_rights));
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_marque']) . "-" . h($r['nom_modele_vehicule']) . ")</td><td>" . h($r['nom_chauffeur']) . "</td><td>" . h($r['nom_region']) . "</td><td>" . h($r['nom_entite']) . "</td><td>" . h($r['lib_type_utilisation']) . "</td><td>" . h($r['nom_mode_utilisation']) . "</td><td>".($r['objet_affectation']=='' ? 'Non défini' : h($r['objet_affectation']))."</td><td>".($r['date_debut_affectation'] ? date('d M Y',strtotime($r['date_debut_affectation'])) : '')."</td><td>".($r['date_fin_affectation']=='' ? 'Non définie' : date('d M Y',strtotime($r['date_fin_affectation'])))."</td><td>".($r['is_ferme']=='1' ? '<span class="badge text-bg-success">Oui</span>' : '<span class="badge text-bg-warning">En cours</span></div>')."</td><td><div class='btn-group'>".($r['is_ferme']=='1' ? '' : (in_array('upd',$rights_affectation) ? "<button class='btn btn-sm btn-secondary' title='Clôturer l\"affectation' onclick='closeAffectation(\"".$r['id_affectation']."\")'><i class='fa fa-check'></i></button>" : "").(in_array('del',$rights_affectation) ? "<button class='btn btn-sm btn-danger' title='Supprimer cette affectation' onclick='deleteAffectation(\"".($r['id_affectation'])."\")'><i class='fa fa-times'></i></button>" : ""))."</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewAffectation.php'); ?>
<?php /* POST handled by AffectationController — see controllers/router.php */ ?>
<?php if(isset($_GET['action']) && $_GET['action']=='new'): ?>
<script>
    setTimeout(()=>{openModalAffectation()},2000)
</script>
<?php endif; ?>
<script>
    function showModalUpdateAffectation(id) {
        $('#modal-upd-affectation').modal('show')
        $('#id-affectation').val(id)
        $.ajax({
            type: 'post',
            data: 'id-affectation-forModal=' + id,
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                let v = e.data
                // $('#nom-affectation-display').html(v.nom_chauffeur);
                // $('#nom-upd-affectation').val(v.nom_chauffeur);
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }
    function updateAffectation($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-affectation').serialize(),
                dataType:'json'
            }).done((e)=>{
                if (e.success) {
                    showSuccess('Modification effectuée!!')
                    location="?page=affectationVehicules&subpage=listeAffectationsVehicules"
                } else {
                    showError(e.error || "Erreur lors de la modification")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Erreur lors de la modification")
            })
        }
    }

    function deleteAffectation(id){
        if(confirm("Etes-vous sûr de vouloir supprimer? Cette action est irréversible et entrainera la suppression de tous les voyages fait durant cette période par ce véhicule")){
            $.ajax({
                type:'post',
                data:'id-affectation-forDel='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Affectation supprimée!!')
                    setTimeout(()=>{location.reload()},3000)
                }else{
                    showError(e.error || "Echec de l'opération")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Echec de l'opération")
            })
        }
    }

    function closeAffectation(id){
        if(confirm("Etes-vous sûr de vouloir clôturer l'affectation de ce véhicule ? L'action sera irréversible")){
            $.ajax({
                type:'post',
                data:'id-aff-toClose='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Affectation clôturée!!')
                    setTimeout(()=>{location.reload()},3000)
                }else{
                    showError(e.error || "Erreur lors de l'opération!")
                }
            }).fail((jqXHR)=>{
                showError(jqXHR.responseJSON?.error || "Erreur lors de l'opération!")
            })
        }
    }
</script>
<div class="modal fade" id="modal-upd-affectation" tabindex="-1" aria-labelledby="modal-upd-affectationLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-affectationLabel">Affectation de véhicule <span id='nom-affectation-display'></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-affectation">
                    
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateAffectation()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>