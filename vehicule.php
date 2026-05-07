<?php function getTableauVehicules()
{
    global $con;
    global $rights_vehicule;
    $q = mysqli_query($con, "SELECT * FROM `vehicule` left join marque_vehicule on vehicule.id_marque=marque_vehicule.id_marque left join modele_vehicule on vehicule.id_modele_vehicule=modele_vehicule.id_modele_vehicule WHERE 1");
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Immatriculation</th><th>Marque</th><th>Modèle</th><th>Chassis</th><th>1ère utilisation</th><th>Expir. carte grise</th><th>Nb. places</th><th>Carburant</th><th>Puissance</th><th>Capacité</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r['immatriculation_vehicule']}</td><td>{$r['nom_marque']}</td><td>{$r['nom_modele_vehicule']}</td><td>{$r['chassis_vehicule']}</td><td>" . date('d M Y', strtotime($r['premiere_utilisation'])) . "</td><td>" . date('d M Y', strtotime($r['expiration_carte_grise'])) . "</td><td>{$r['nb_place']}</td><td>{$r['type_carburant']}</td><td>{$r['puissance_vehicule']} CV</td><td>{$r['capacite_consommation_vehicule']}</td><td><div class='btn-group'>" . (in_array('upd', $rights_vehicule) ? "<button class='btn btn-primary' title='Modifier' data-bs-toggle='modal' data-bs-target='#modal-upd-vh' data-bs-immat='" . sha1($r[0] . $r[1]) . "'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array('del', $rights_vehicule) ? "<button class='btn btn-danger' title='Supprimer' onclick='delVehicule(\"{$r['immatriculation_vehicule']}\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}
if (isset($_POST['im-vh-upd'])):
    $q = mysqli_query($con, "select *,(select sha1(concat(id_marque,nom_marque)) from marque_vehicule mv where mv.id_marque=vehicule.id_marque) as id_m,(select sha1(concat(id_modele_vehicule,nom_modele_vehicule)) from modele_vehicule mdv where mdv.id_modele_vehicule=vehicule.id_marque) as id_md from vehicule where sha1(concat(id_vehicule,immatriculation_vehicule))='{$_POST['im-vh-upd']}'");
    $liste = array();
    while ($r = mysqli_fetch_array($q)):
        $liste = $r;
    endwhile;
    unset($liste[0]);
    unset($liste['id_vehicule']);
    die("LISTEVH%%%%%%" . json_encode($liste));
endif;
if(isset($_POST['vh-del-id'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q=mysqli_query($con,"delete from vehicule where immatriculation_vehicule='{$_POST['vh-del-id']}'");
        mysqli_commit($con);
        die("DELVH%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("DELVH%%%%%%0");
    }
endif;
if (isset($_POST['immat-vh-upd'])) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, $_POST[$keys[$i]]);
        $q = mysqli_query($con, "update vehicule set puissance_vehicule='{$_POST['puissance-vh-upd']}', id_marque=(select id_marque from marque_vehicule where sha1(concat(id_marque,nom_marque))='{$_POST['marque-vh-upd']}'),id_modele_vehicule=(select id_modele_vehicule from modele_vehicule where sha1(concat(id_modele_vehicule,nom_modele_vehicule))='{$_POST['modele-vh-upd']}'),chassis_vehicule='{$_POST['chassis-vh-upd']}',premiere_utilisation='{$_POST['dutil-vh-upd']}',expiration_carte_grise='{$_POST['dexpir-vh-upd']}',puissance_vehicule='{$_POST['puissance-vh-upd']}',capacite_consommation_vehicule='{$_POST['capacite-vh-upd']}',nb_place='{$_POST['nbplace-vh-upd']}',type_carburant='{$_POST['tcarb-vh-upd']}' where immatriculation_vehicule='{$_POST['immat-vh-upd']}'");
        $q = mysqli_query($con, "replace into qualification_permis_vehicule (id_vehicule,id_type_permis) values((select id_vehicule from vehicule where immatriculation_vehicule='{$_POST['immat-vh-upd']}'),(select id_type_permis from type_permis_vehicule where sha1(concat(id_type_permis,lib_type_permis))='{$_POST['qualif-permis-upd']}'))");
        mysqli_commit($con);
        die("UPDVH%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UPDVH%%%%%%0");
    }
}
?>
<?php include('modalNewVehicule.php'); ?>
<?php include('modalNewMarque.php'); ?>
<?php include('modalNewModele.php'); ?>
<div class="modal fade" id="modal-upd-vh" tabindex="-1" aria-labelledby="modal-upd-vehiculeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-upd-vehiculeLabel">Véhicule <span id="vh-immatr"></span></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-upd-vehicule">
                    <div class="row">
                        <div class="form-floating mb-3 col-6">
                            <input type="text" id="immat-vh-upd" name="immat-vh-upd" readonly required class="form-control">
                            <label for="immat-vh-upd">Immatriculation</label>
                        </div>
                        <div class="col-6">
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select type="text" id="marque-vh-upd" name="marque-vh-upd" required class="form-select">
                                        <?php $q = mysqli_query($con, "select * from marque_vehicule where 1");
                                        while ($r = mysqli_fetch_array($q)):
                                            echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                    <label for="marque-vh-upd">Marque</label>
                                </div>
                                <button class="btn btn-primary" type="button" title="Ajouter une marque" onclick="openModalMarque()"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select type="text" id="modele-vh-upd" name="modele-vh-upd" required class="form-select">
                                        <?php $q = mysqli_query($con, "select * from modele_vehicule where 1");
                                        while ($r = mysqli_fetch_array($q)):
                                            echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                    <label for="modele-vh-upd">Modele</label>
                                </div>
                                <button class="btn btn-primary" type="button" title="Ajouter un modèle de véhicule" onclick="openModalModele()"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="text" id="chassis-vh-upd" name="chassis-vh-upd" required class="form-control">
                            <label for="chassis-vh-upd">N° de chassis</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="date" id="dutil-vh-upd" name="dutil-vh-upd" required class="form-control">
                            <label for="dutil-vh-upd">Date de 1ère utilisation</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="date" id="dexpir-vh-upd" name="dexpir-vh-upd" required class="form-control">
                            <label for="dexpir-vh-upd">Date expir. carte grise</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="number" id="puissance-vh-upd" name="puissance-vh-upd" required class="form-control">
                            <label for="puissance-vh-upd">Puissance</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="number" id="capacite-vh-upd" name="capacite-vh-upd" required class="form-control">
                            <label for="capacite-vh-upd">Capacité</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="nbplace-vh-upd" name="nbplace-vh-upd" required class="form-select">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="7">7</option>
                            </select>
                            <label for="nbplace-vh-upd">Nb. places</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="tcarb-vh-upd" name="tcarb-vh-upd" required class="form-select">
                                <option value="GASOIL">Gasoil</option>
                                <option value="SUPER">Super</option>
                            </select>
                            <label for="tcarb-vh-upd">Type de carburant</label>
                        </div>
                        <hr>
                        <div class="form-floating mb-3 col-6">
                            <select id="qualif-permis-upd" name="qualif-permis-upd" required class="form-select">
                                <?php $q = mysqli_query($con, "select * from type_permis_vehicule");
                                while ($r = mysqli_fetch_array($q)) :
                                    echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                endwhile;
                                ?>
                            </select>
                            <label for="qualif-permis-upd">Qualification de permis</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updVehicule()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function updVehicule() {
        var valid = true
        $('#form-upd-vehicule *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
            }
        })
        if (!valid) {
            $.notify('Les champs en rouge sont obligatoires')
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-upd-vehicule').serialize()
        }).done((e) => {
            let v = e.split('UPDVH%%%%%%')[1]
            if (v == '1') {
                alert('Enregistrement effectué!!!')
                location.reload()
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
    const modalUpdVH = document.getElementById('modal-upd-vh')
    if (modalUpdVH) {
        modalUpdVH.addEventListener('show.bs.modal', event => {
            // Button that triggered the modal
            const id = event.relatedTarget.getAttribute('data-bs-immat')
            $.ajax({
                type: 'post',
                data: 'im-vh-upd=' + id
            }).done((e) => {
                let v = e.split("LISTEVH%%%%%%")[1]
                v = JSON.parse(v)
                $('#vh-immatr').html(v.immatriculation_vehicule)
                $('#immat-vh-upd').val(v.immatriculation_vehicule)
                $('#marque-vh-upd').val(v.id_m)
                $('#modele-vh-upd').val(v.id_md)
                $('#chassis-vh-upd').val(v.chassis_vehicule)
                $('#dutil-vh-upd').val(v.premiere_utilisation)
                $('#dexpir-vh-upd').val(v.expiration_carte_grise)
                $('#puissance-vh-upd').val(v.puissance_vehicule)
                $('#capacite-vh-upd').val(v.capacite_consommation_vehicule)
                $('#nbplace-vh-upd').val(v.nb_place)
                $('#tcarb-vh-upd').val(v.type_carburant)
            })
        })
    }

    function delVehicule(id){
        if(confirm("Etes-vous sûr de vouloir supprimer ?")){
            $.ajax({
                type:'post',
                data:'vh-del-id='+id
            }).done((e)=>{
                let v=e.split('DELVH%%%%%%')[1]
                if(v=='1'){
                    alert('Véhicule supprimé!')
                    location.reload()
                }
                $.notify("Echec de l'opération!")
            })
        }
    }
</script>