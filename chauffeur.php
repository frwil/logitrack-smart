<?php function getTableauChauffeurs()
{
    global $con;
    $q = mysqli_query($con, "SELECT * FROM `chauffeur` WHERE 1");
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom Chauffeur</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['nom_chauffeur']}</td><td><div class='btn-group'><button class='btn btn-light' type='button' title='Modifier le chauffeur {$r[1]}' onclick='showModalUpdateChauffeur(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-pencil-alt'></i></button><button class='btn btn-danger' title='Supprimer le chauffeur {$r[1]}' onclick='deleteChauffeur(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-times'></i></button></div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}
?>
<?php include('modalNewChauffeur.php'); ?>
<?php if (isset($_POST['id-chauffeur-forModal'])):
    $q = mysqli_query($con, "select * from chauffeur where sha1(concat(id_chauffeur,nom_chauffeur))='{$_POST['id-chauffeur-forModal']}'");
    while ($r = mysqli_fetch_array($q)):
        $chauffeur = $r;
    endwhile;
    die("UpdChauffeur%%%%%%" . json_encode($chauffeur));
endif;
if (isset($_POST['id-chauffeur'])):
    $_POST['nom-upd-chauffeur'] = trim(strtoupper($_POST['nom-upd-chauffeur']));
    $keys = array_keys($_POST);
    for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "update chauffeur set nom_chauffeur='{$_POST['nom-upd-chauffeur']}' where sha1(concat(id_chauffeur,nom_chauffeur))='{$_POST['id-chauffeur']}'");
        mysqli_commit($con);
        die("UpdChauffeur%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdChauffeur%%%%%%0");
    }
endif;
if(isset($_POST['id-chauffeur-forDel'])):
    $q=mysqli_query($con,"delete from chauffeur where sha1(concat(id_chauffeur,nom_chauffeur))='{$_POST['id-chauffeur-forDel']}'");
    if($q) die("UpdChauffeur%%%%%%1");
    die("UpdChauffeur%%%%%%0");
endif;
?>
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
            data: 'id-chauffeur-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdChauffeur%%%%%%')[1]
            v = JSON.parse(v);
            $('#nom-chauffeur-display').html(v.nom_chauffeur);
            $('#nom-upd-chauffeur').val(v.nom_chauffeur);
        })
    }
    function updateChauffeur($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-chauffeur').serialize()
            }).done((e)=>{
                let v = e.split('UpdChauffeur%%%%%%')[1]
                if (v == '1') {
                    $.notify('Modification effectuée!!', {
                        className: 'success'
                    })
                    location="?page=affectationVehicules&subpage=listeChauffeurs"
                } else {
                    $.notify("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteChauffeur(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-chauffeur-forDel='+id
            }).done((e)=>{
                let v=e.split('UpdChauffeur%%%%%%')[1]
                if(v=='1'){
                    $.notify('Chauffeur supprimée!!',{
                        className:'success'
                    })
                    location.reload()
                }else{
                    $.notify("Echec de l'opération")
                }
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