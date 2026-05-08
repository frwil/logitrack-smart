<?php function getTableauAffectations()
{
    global $con;
    global $rights_affectation;
    $regionIds = array_map('intval', explode(',', $_SESSION['usr-con']['users_region']));
    [$placeholders, $params] = db_in($regionIds);
    $q = db_select($con, "SELECT * FROM `affectation_vehicule` left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on region.id_region=affectation_vehicule.id_region left join entite on entite.id_entite=affectation_vehicule.id_entite left join type_utilisation_vehicule on type_utilisation_vehicule.id_type_utilisation=affectation_vehicule.id_type_utilisation left join mode_utilisation_vehicule on mode_utilisation_vehicule.id_mode_utilisation=affectation_vehicule.id_mode_utilisation left join marque_vehicule on marque_vehicule.id_marque=vehicule.id_marque left join modele_vehicule on modele_vehicule.id_modele_vehicule=vehicule.id_modele_vehicule WHERE 1 and affectation_vehicule.id_region in ($placeholders) order by date_affectation desc", $params);
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Immatriculation</th><th>Chauffeur</th><th>Région</th><th>Entité</th><th>Type d'utilisation</th><th>Mode d'utilisation</th><th>Objet d'affectation</th><th>Date début</th><th>Date fin</th><th>Clôturé</th><th></th></tr></thead><tbody>";
    $i = 1;
    //$user_rights = $_SESSION['usr-con']['users-rights'];
    //$rights_affectation = explode(',', isRightObjectAllowed('affectationVehicules', $user_rights));
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['immatriculation_vehicule']} ({$r['nom_marque']}-{$r['nom_modele_vehicule']})</td><td>{$r['nom_chauffeur']}</td><td>{$r['nom_region']}</td><td>{$r['nom_entite']}</td><td>{$r['lib_type_utilisation']}</td><td>{$r['nom_mode_utilisation']}</td><td>".($r['objet_affectation']=='' ? 'Non défini' : $r['objet_affectation'])."</td><td>".date('d M Y',strtotime($r['date_debut_affectation']))."</td><td>".($r['date_fin_affectation']=='' ? 'Non définie' : date('d M Y',strtotime($r['date_fin_affectation'])))."</td><td>".($r['is_ferme']=='1' ? '<span class="badge text-bg-success">Oui</span>' : '<span class="badge text-bg-warning">En cours</span></div>')."</td><td><div class='btn-group'>".($r['is_ferme']=='1' ? '' : (in_array('upd',$rights_affectation) ? "<button class='btn btn-sm btn-secondary' title='Clôturer l\"affectation' onclick='closeAffectation(\"".sha1($r[0].$r['id_vehicule'])."\")'><i class='fa fa-check'></i></button>" : "").(in_array('del',$rights_affectation) ? "<button class='btn btn-sm btn-danger' title='Supprimer cette affectation' onclick='deleteAffectation(\"".(sha1($r[0].$r['id_vehicule']))."\")'><i class='fa fa-times'></i></button>" : ""))."</div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewAffectation.php'); ?>
<?php if (isset($_POST['id-affectation-forModal'])):
    $q = db_select($con, "select * from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?", [$_POST['id-affectation-forModal']]);
    while ($r = mysqli_fetch_array($q)):
        $affectation = $r;
    endwhile;
    die("UpdAffectation%%%%%%" . json_encode($affectation));
endif;
if (isset($_POST['id-affectation'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update affectation_vehicule set nom_chauffeur=? where sha1(concat(id_affectation,id_vehicule))=?", [$_POST['nom-upd-chauffeur'], $_POST['id-affectation']]);
        mysqli_commit($con);
        die("UpdAffectation%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdAffectation%%%%%%0");
    }
endif;
if(isset($_POST['id-affectation-forDel'])):
    $q = db_exec($con, "delete from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?", [$_POST['id-affectation-forDel']]);
    if($q) die("UpdAffectation%%%%%%1");
    die("UpdAffectation%%%%%%0");
endif;
if(isset($_POST['id-aff-toClose'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update affectation_vehicule set is_ferme=1,date_fin_affectation=CURRENT_DATE where sha1(concat(id_affectation,id_vehicule))=?", [$_POST['id-aff-toClose']]);
        mysqli_commit($con);
        die("CloseAffectation%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("CloseAffectation%%%%%%0");
    }
endif;
?>
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
            data: 'id-affectation-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdAffectation%%%%%%')[1]
            v = JSON.parse(v);
            // $('#nom-affectation-display').html(v.nom_chauffeur);
            // $('#nom-upd-affectation').val(v.nom_chauffeur);
        })
    }
    function updateAffectation($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-affectation').serialize()
            }).done((e)=>{
                let v = e.split('UpdAffectation%%%%%%')[1]
                if (v == '1') {
                    showSuccess('Modification effectuée!!')
                    location="?page=affectationVehicules&subpage=listeAffectationsVehicules"
                } else {
                    showError("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteAffectation(id){
        if(confirm("Etes-vous sûr de vouloir supprimer? Cette action est irréversible et entrainera la suppression de tous les voyages fait durant cette période par ce véhicule")){
            $.ajax({
                type:'post',
                data:'id-affectation-forDel='+id
            }).done((e)=>{
                let v=e.split('UpdAffectation%%%%%%')[1]
                if(v=='1'){
                    showSuccess('Affectation supprimée!!')
                    setTimeout(()=>{location.reload()},3000)
                }else{
                    showError("Echec de l'opération")
                }
            })
        }
    }

    function closeAffectation(id){
        if(confirm("Etes-vous sûr de vouloir clôturer l'affectation de ce véhicule ? L'action sera irréversible")){
            $.ajax({
                type:'post',
                data:'id-aff-toClose='+id
            }).done((e)=>{
                let v=e.split('CloseAffectation%%%%%%')[1]
                if(v=='1'){
                    showSuccess('Affectation clôturée!!')
                    setTimeout(()=>{location.reload()},3000)
                }else{
                    showError("Erreur lors de l'opération!")
                }
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