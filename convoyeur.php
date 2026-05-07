<?php function getTableauConvoyeurs()
{
    global $con;
    $q = mysqli_query($con, "SELECT * FROM `convoyeur` WHERE 1");
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom Convoyeur</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['nom_convoyeur']}</td><td><div class='btn-group'><button class='btn btn-light' type='button' title='Modifier le convoyeur {$r[1]}' onclick='showModalUpdateConvoyeur(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-pencil-alt'></i></button><button class='btn btn-danger' title='Supprimer le convoyeur {$r[1]}' onclick='deleteConvoyeur(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-times'></i></button></div></td></tr>";
        $i++;
    endwhile;
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
<?php if (isset($_POST['id-convoyeur-forModal'])):
    $q = mysqli_query($con, "select * from convoyeur where sha1(concat(id_convoyeur,nom_convoyeur))='{$_POST['id-convoyeur-forModal']}'");
    while ($r = mysqli_fetch_array($q)):
        $convoyeur = $r;
    endwhile;
    die("UpdConvoyeur%%%%%%" . json_encode($convoyeur));
endif;
if (isset($_POST['id-convoyeur'])):
    $_POST['nom-upd-convoyeur'] = trim(strtoupper($_POST['nom-upd-convoyeur']));
    $keys = array_keys($_POST);
    for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "update convoyeur set nom_convoyeur='{$_POST['nom-upd-convoyeur']}' where sha1(concat(id_convoyeur,nom_convoyeur))='{$_POST['id-convoyeur']}'");
        mysqli_commit($con);
        die("UpdConvoyeur%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdConvoyeur%%%%%%0");
    }
endif;
if(isset($_POST['id-convoyeur-forDel'])):
    $q=mysqli_query($con,"delete from convoyeur where sha1(concat(id_convoyeur,nom_convoyeur))='{$_POST['id-convoyeur-forDel']}'");
    if($q) die("UpdConvoyeur%%%%%%1");
    die("UpdConvoyeur%%%%%%0");
endif;
?>
<script>
    function showModalUpdateConvoyeur(id) {
        $('#modal-upd-convoyeur').modal('show')
        $('#id-convoyeur').val(id)
        $.ajax({
            type: 'post',
            data: 'id-convoyeur-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdConvoyeur%%%%%%')[1]
            v = JSON.parse(v);
            $('#nom-convoyeur-display').html(v.nom_convoyeur);
            $('#nom-upd-convoyeur').val(v.nom_convoyeur);
        })
    }
    function updateConvoyeur($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-convoyeur').serialize()
            }).done((e)=>{
                let v = e.split('UpdConvoyeur%%%%%%')[1]
                if (v == '1') {
                    $.notify('Modification effectuée!!', {
                        className: 'success'
                    })
                    location="?page=affectationVehicules&subpage=listeConvoyeurs"
                } else {
                    $.notify("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteConvoyeur(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-convoyeur-forDel='+id
            }).done((e)=>{
                let v=e.split('UpdConvoyeur%%%%%%')[1]
                if(v=='1'){
                    $.notify('Convoyeur supprimée!!',{
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