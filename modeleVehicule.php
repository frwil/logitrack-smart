<?php function getTableauModeleVehicules()
{
    global $con;
    global $rights_vehicule;
    $q = mysqli_query($con, "SELECT * FROM `modele_vehicule` WHERE 1");
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom modele</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['nom_modele_vehicule']}</td><td><div class='btn-group'>".(in_array("upd",$rights_vehicule) ? "<button class='btn btn-light' type='button' onclick='showModalUpdateModele({$r[0]})' title='Modifier la modele {$r[1]}'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array("del",$rights_vehicule) ? "<button class='btn btn-danger' type='button' onclick='deleteModele({$r[0]})' title='Supprimer la modele {$r[1]}'><i class='fa fa-times'></i></button>" : "")."</div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}
include("modalNewModele.php");
?>
<?php if (isset($_POST['id-modele-forModal'])):
    $q = mysqli_query($con, "select * from modele_vehicule where id_modele_vehicule={$_POST['id-modele-forModal']}");
    while ($r = mysqli_fetch_array($q)):
        $modele = $r;
    endwhile;
    die("UpdModele%%%%%%" . json_encode($modele));
endif;
if (isset($_POST['id-modele'])):
    $_POST['nom-upd-modele'] = trim(strtoupper($_POST['nom-upd-modele']));
    $keys = array_keys($_POST);
    for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "update modele_vehicule set nom_modele_vehicule='{$_POST['nom-upd-modele']}' where id_modele_vehicule={$_POST['id-modele']}");
        mysqli_commit($con);
        die("UpdModele%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdModele%%%%%%0");
    }
endif;
if(isset($_POST['id-modele-forDel'])):
    $q=mysqli_query($con,"delete from modele_vehicule where id_modele_vehicule={$_POST['id-modele-forDel']}");
    if($q) die("UpdModele%%%%%%1");
    die("UpdModele%%%%%%0");
endif;
?>
<script>
    function showModalUpdateModele(id) {
        $('#modal-upd-modele').modal('show')
        $('#id-modele').val(id)
        $.ajax({
            type: 'post',
            data: 'id-modele-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdModele%%%%%%')[1]
            v = JSON.parse(v);
            $('#nom-modele-display').html(v.nom_modele_vehicule);
            $('#nom-upd-modele').val(v.nom_modele_vehicule);
        })
    }

    function updateModele() {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-modele').serialize()
            }).done((e) => {
                let v = e.split('UpdModele%%%%%%')[1]
                if (v == '1') {
                    $.notify('Modification effectuée!!', {
                        className: 'success'
                    })
                    location.reload()
                } else {
                    $.notify("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteModele(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-modele-forDel='+id
            }).done((e)=>{
                let v=e.split('UpdModele%%%%%%')[1]
                if(v=='1'){
                    $.notify('Modele supprimée!!',{
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