<?php function getTableauMarqueVehicules()
{
    global $con;
    global $rights_vehicule;
    $q = db_select($con, "SELECT * FROM `marque_vehicule` WHERE 1", []);
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Nom marque</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['nom_marque']}</td><td><div class='btn-group'>".(in_array('upd',$rights_vehicule) ? "<button class='btn btn-light' type='button' onclick='showModalUpdateMarque({$r[0]})' title='Modifier la marque {$r[1]}'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array("del",$rights_vehicule) ? "<button class='btn btn-danger' type='button' onclick='deleteMarque({$r[0]})' title='Supprimer la marque {$r[1]}'><i class='fa fa-times'></i></button>" : "")."</div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}
include("modalNewMarque.php");
?>
<?php if (isset($_POST['id-marque-forModal'])):
    $q = db_select($con, "select * from marque_vehicule where id_marque=?", [(int)$_POST['id-marque-forModal']]);
    while ($r = mysqli_fetch_array($q)):
        $marque = $r;
    endwhile;
    die("UpdMarque%%%%%%" . json_encode($marque));
endif;
if (isset($_POST['id-marque'])):
    $_POST['nom-upd-marque'] = trim(strtoupper($_POST['nom-upd-marque']));
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update marque_vehicule set nom_marque=? where id_marque=?", [$_POST['nom-upd-marque'], (int)$_POST['id-marque']]);
        mysqli_commit($con);
        die("UpdMarque%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdMarque%%%%%%0");
    }
endif;
if(isset($_POST['id-marque-forDel'])):
    $q=db_exec($con,"delete from marque_vehicule where id_marque=?", [(int)$_POST['id-marque-forDel']]);
    if($q) die("UpdMarque%%%%%%1");
    die("UpdMarque%%%%%%0");
endif;
?>
<script>
    function showModalUpdateMarque(id) {
        $('#modal-upd-marque').modal('show')
        $('#id-marque').val(id)
        $.ajax({
            type: 'post',
            data: 'id-marque-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdMarque%%%%%%')[1]
            v = JSON.parse(v);
            $('#nom-marque-display').html(v.nom_marque);
            $('#nom-upd-marque').val(v.nom_marque);
        })
    }

    function updateMarque() {
        if (confirm("Etes-vous sûr de vouloir modifier ?")) {
            $.ajax({
                type: 'post',
                data: $('#form-upd-marque').serialize()
            }).done((e) => {
                let v = e.split('UpdMarque%%%%%%')[1]
                if (v == '1') {
                    showSuccess('Modification effectuée!!')
                    location.reload()
                } else {
                    showError("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteMarque(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-marque-forDel='+id
            }).done((e)=>{
                let v=e.split('UpdMarque%%%%%%')[1]
                if(v=='1'){
                    showSuccess('Marque supprimée!!')
                    location.reload()
                }else{
                    showError("Echec de l'opération")
                }
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