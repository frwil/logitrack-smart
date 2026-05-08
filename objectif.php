<?php function getTableauObjectifs()
{
    global $con;
    global $rights_voyage;
    $sqlObj = "SELECT * FROM `objectif_periode_region` WHERE 1 and date_objectif_periode between ? and ? and id_region=?";
    $paramsObj = [isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01'), isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t'), (int)$_SESSION['usr-con']['region-sel']];
    $q = db_select($con, $sqlObj, $paramsObj);
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Date</th><th>Objectif voyages</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['date_objectif_periode']}</td><td>{$r['objectif']}</td><td><div class='btn-group'>".(in_array('upd',$rights_voyage) ? "<button class='btn btn-light' type='button' title='Modifier l'objectif {$r[1]}' onclick='showModalUpdateObjectif(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-pencil-alt'></i></button>" : "").(in_array('del',$rights_voyage) ? "<button class='btn btn-danger' title='Supprimer le objectif {$r[1]}' onclick='deleteObjectif(\"".sha1($r[0].$r[1])."\")'><i class='fa fa-times'></i></button>" : "")."</div></td></tr>";
        $i++;
    endwhile;
    $form="<form method='post' action='?page=voyages&subpage=listeObjectifsVoyages' class='row'><div class='col-4'><div class='form-floating'><input type='date' value='".(isset($_POST['date-f']) ? $_POST['date-f'] : date('Y-m-01'))."' class='form-control' id='date-f' name='date-f'><label for='date-f'>Date début</label></div></div><div class='col-4'><div class='form-floating'><input type='date' id='date-t' name='date-t' class='form-control' value='".(isset($_POST['date-t']) ? $_POST['date-t'] : date('Y-m-t'))."'><label for='date-t'>Date fin</label></div></div><div class='col-4'><button class='btn btn-primary'>Afficher</button></div></form>";
    $tableau .= "</tbody></table>";
    return $form."<hr>".$tableau;
}
?>
<?php include('modalNewObjectif.php'); ?>
<?php if (isset($_POST['id-objectif-forModal'])):
    $q = db_select($con, "select * from objectif_periode_region where sha1(concat(id_objectif_periode,date_objectif_periode))=?", [$_POST['id-objectif-forModal']]);
    while ($r = mysqli_fetch_array($q)):
        $objectif = $r;
    endwhile;
    die("UpdObjectif%%%%%%" . json_encode($objectif));
endif;
if (isset($_POST['id-objectif'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "update objectif_periode_region set date_objectif_periode=?,objectif=? where sha1(concat(id_objectif_periode,date_objectif_periode))=?", [$_POST['date-upd-objectif'], $_POST['objectif-upd'], $_POST['id-objectif']]);
        mysqli_commit($con);
        die("UpdObjectif%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UpdObjectif%%%%%%0");
    }
endif;
if(isset($_POST['id-objectif-forDel'])):
    $q=db_exec($con,"delete from objectif_periode_region where sha1(concat(id_objectif_periode,date_objectif_periode))=?", [$_POST['id-objectif-forDel']]);
    if($q) die("UpdObjectif%%%%%%1");
    die("UpdObjectif%%%%%%0");
endif;
?>
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
            data: 'id-objectif-forModal=' + id
        }).done((e) => {
            let v = e.split('UpdObjectif%%%%%%')[1]
            v = JSON.parse(v);
            $('#date-objectif-display').html(v.date_objectif_periode);
            $('#date-upd-objectif').val(v.date_objectif_periode);
            $('#objectif-upd').val(v.objectif)
        })
    }
    function updateObjectif($id){
        if(confirm("Etes-vous sûr de vouloir modifier ?")){
            $.ajax({
                type:'post',
                data:$('#form-upd-objectif').serialize()
            }).done((e)=>{
                let v = e.split('UpdObjectif%%%%%%')[1]
                if (v == '1') {
                    showSuccess('Modification effectuée!!')
                    location="?page=voyages&subpage=listeObjectifsVoyages"
                } else {
                    showError("Erreur lors de la modificaiton")
                }
            })
        }
    }

    function deleteObjectif(id){
        if(confirm("Etes-vous sûr de vouloir supprimer?")){
            $.ajax({
                type:'post',
                data:'id-objectif-forDel='+id
            }).done((e)=>{
                let v=e.split('UpdObjectif%%%%%%')[1]
                if(v=='1'){
                    showSuccess('Objectif supprimé!!')
                    location.reload()
                }else{
                    showError("Echec de l'opération")
                }
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