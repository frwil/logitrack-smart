<?php
if (isset($_POST['immat-vh'])) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $keys=array_keys($_POST);
        for($i=0;$i<count($keys);$i++) $_POST[$keys[$i]]=mysqli_real_escape_string($con,$_POST[$keys[$i]]);
        $q = mysqli_query($con, "INSERT INTO `vehicule` (`id_vehicule`, `puissance_vehicule`, `chassis_vehicule`, `premiere_utilisation`, `expiration_carte_grise`, `nb_place`, `type_carburant`, `id_marque`, `id_modele_vehicule`, `id_entite`, `immatriculation_vehicule`,capacite_consommation_vehicule) VALUES (NULL, {$_POST['puissance-vh']}, " . ($_POST['chassis-vh'] == '' ? "NULL" : "'{$_POST['chassis-vh']}'") . ", " . ($_POST['dutil-vh'] == '' ? 'NULL' : "'{$_POST['dutil-vh']}'") . "," . ($_POST['dexpir-vh'] == '' ? "NULL" : "'{$_POST['dexpir-vh']}'") . ", {$_POST['nbplace-vh']}, '{$_POST['tcarb-vh']}', {$_POST['marque-vh']}, {$_POST['modele-vh']}, NULL, '{$_POST['immat-vh']}',{$_POST['capacite-vh']})");
        $q=mysqli_query($con,"INSERT INTO `qualification_permis_vehicule` (`id_qualification_permis`, `id_vehicule`, `id_type_permis`) VALUES (NULL, (select id_vehicule from vehicule where immatriculation_vehicule='{$_POST['immat-vh']}'), (select id_type_permis from type_permis_vehicule where sha1(concat(id_type_permis,lib_type_permis))='{$_POST['qualif-permis']}'))");
        mysqli_commit($con);
        die("NewVehicule%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == '1062') die('NewVehicule%%%%%%1062');
        die("NewVehicule%%%%%%0".$e->getCode());
    }
}
?>
<div class="modal fade" id="modal-new-vehicule" tabindex="-1" aria-labelledby="modal-new-vehiculeLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-vehiculeLabel">Nouveau Véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-vehicule">
                    <div class="row">
                        <div class="form-floating mb-3 col-6">
                            <input type="text" id="immat-vh" name="immat-vh" required class="form-control">
                            <label for="immat-vh">Immatriculation</label>
                        </div>
                        <div class="col-6">
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select type="text" id="marque-vh" name="marque-vh" required class="form-select">
                                        <?php $q = mysqli_query($con, "select * from marque_vehicule where 1");
                                        while ($r = mysqli_fetch_array($q)):
                                            echo "<option value='{$r[0]}'>{$r[1]}</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                    <label for="marque-vh">Marque</label>
                                </div>
                                <button class="btn btn-primary" type="button" title="Ajouter une marque" onclick="openModalMarque()"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="input-group mb-3">
                                <div class="form-floating">
                                    <select type="text" id="modele-vh" name="modele-vh" required class="form-select">
                                        <?php $q = mysqli_query($con, "select * from modele_vehicule where 1");
                                        while ($r = mysqli_fetch_array($q)):
                                            echo "<option value='{$r[0]}'>{$r[1]}</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                    <label for="modele-vh">Modele</label>
                                </div>
                                <button class="btn btn-primary" type="button" title="Ajouter un modèle de véhicule" onclick="openModalModele()"><i class="fa fa-plus"></i></button>
                            </div>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="text" id="chassis-vh" name="chassis-vh" required class="form-control">
                            <label for="chassis-vh">N° de chassis</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="date" id="dutil-vh" name="dutil-vh" required class="form-control">
                            <label for="dutil-vh">Date de 1ère utilisation</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="date" id="dexpir-vh" name="dexpir-vh" required class="form-control">
                            <label for="dexpir-vh">Date expir. carte grise</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="number" id="puissance-vh" name="puissance-vh" required class="form-control">
                            <label for="puissance-vh">Puissance</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <input type="number" id="capacite-vh" name="capacite-vh" required class="form-control">
                            <label for="capacite-vh">Capacité</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="nbplace-vh" name="nbplace-vh" required class="form-select">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="7">7</option>
                            </select>
                            <label for="nbplace-vh">Nb. places</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="tcarb-vh" name="tcarb-vh" required class="form-select">
                                <option value="GASOIL">Gasoil</option>
                                <option value="SUPER">Super</option>
                            </select>
                            <label for="tcarb-vh">Type de carburant</label>
                        </div>
                        <hr>
                        <div class="form-floating mb-3 col-6">
                            <select id="qualif-permis" name="qualif-permis" required class="form-select">
                                    <?php $q=mysqli_query($con,"select * from type_permis_vehicule");
                                    while($r=mysqli_fetch_array($q)) :
                                        echo "<option value='".sha1($r[0].$r[1])."'>{$r[1]}</option>";
                                    endwhile;
                                    ?>
                            </select>
                            <label for="qualif-permis">Qualification de permis</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveVehicule()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function saveVehicule() {
        var valid = true
        $('#form-new-vehicule *[required]').each((e, el) => {
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
            data: $('#form-new-vehicule').serialize()
        }).done((e) => {
            let v = e.split('NewVehicule%%%%%%')[1]
            if (v == '1') {
                $.notify("Enregistrement effectué!!!", {
                    className: 'success'
                })
                location.reload()
            } else if (v == '1062') {
                $.notify("Ce véhicule existe déjà!!")
            } else {
                $.notify("Erreur lors de l'enregistrement")
            }
        })
    }
</script>