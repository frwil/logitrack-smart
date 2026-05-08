<?php function getTableauVehicules()
{
    global $con;
    global $rights_vehicule;
    $repo = new VehiculeRepository($con);
    $rows = $repo->findAllWithDetails();
    $tableau = "<table class='table table-striped responsive'><thead><tr><th>#</th><th>Immatriculation</th><th>Marque</th><th>Modèle</th><th>Chassis</th><th>1ère utilisation</th><th>Expir. carte grise</th><th>Nb. places</th><th>Carburant</th><th>Puissance</th><th>Capacité</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $tableau .= "<tr><td>$i</td><td>" . h($r['immatriculation_vehicule']) . "</td><td>" . h($r['nom_marque']) . "</td><td>" . h($r['nom_modele_vehicule']) . "</td><td>" . h($r['chassis_vehicule']) . "</td><td>" . ($r['premiere_utilisation'] ? date('d M Y', strtotime($r['premiere_utilisation'])) : '') . "</td><td>" . ($r['expiration_carte_grise'] ? date('d M Y', strtotime($r['expiration_carte_grise'])) : '') . "</td><td>" . h($r['nb_place']) . "</td><td>" . h($r['type_carburant']) . "</td><td>" . h($r['puissance_vehicule']) . " CV</td><td>" . h($r['capacite_consommation_vehicule']) . "</td><td><div class='btn-group'>" . (in_array('upd', $rights_vehicule) ? "<button class='btn btn-primary' title='Modifier' data-bs-toggle='modal' data-bs-target='#modal-upd-vh' data-bs-immat='" . $r['id_vehicule'] . "'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array('del', $rights_vehicule) ? "<button class='btn btn-danger' title='Supprimer' onclick='delVehicule(\"" . h($r['immatriculation_vehicule']) . "\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}
/* POST /vehicule handled by VehiculeController — see controllers/router.php */
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
                                    <select type="text" id="marque-vh-upd" name="marque-vh-upd" required>
                                        <?php $marqueRepo = new MarqueRepository($con);
                                        foreach ($marqueRepo->findAll() as $r):
                                            echo "<option value='" . $r['id_marque'] . "'>" . h($r['nom_marque']) . "</option>";
                                        endforeach;
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
                                    <select type="text" id="modele-vh-upd" name="modele-vh-upd" required>
                                        <?php $modeleRepo = new ModeleRepository($con);
                                        foreach ($modeleRepo->findAll() as $r):
                                            echo "<option value='" . $r['id_modele_vehicule'] . "'>" . h($r['nom_modele_vehicule']) . "</option>";
                                        endforeach;
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
                            <select id="nbplace-vh-upd" name="nbplace-vh-upd" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="7">7</option>
                            </select>
                            <label for="nbplace-vh-upd">Nb. places</label>
                        </div>
                        <div class="form-floating mb-3 col-6">
                            <select id="tcarb-vh-upd" name="tcarb-vh-upd" required>
                                <option value="GASOIL">Gasoil</option>
                                <option value="SUPER">Super</option>
                            </select>
                            <label for="tcarb-vh-upd">Type de carburant</label>
                        </div>
                        <hr>
                        <div class="form-floating mb-3 col-6">
                            <select id="qualif-permis-upd" name="qualif-permis-upd" required>
                                <?php $configRepo = new ConfigRepository($con);
                                foreach ($configRepo->findAllTypePermis() as $r):
                                    echo "<option value='" . $r['id_type_permis'] . "'>" . h($r['lib_type_permis']) . "</option>";
                                endforeach;
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
            showError('Les champs en rouge sont obligatoires')
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-upd-vehicule').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Enregistrement effectué!!!')
                location.reload()
            } else {
                showError(e.error||"Erreur lors de l'enregistrement")
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
                data: 'im-vh-upd=' + id,
                dataType: 'json'
            }).done((e) => {
                if (!e.success) { showError(e.error); return }
                var v = e.data
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
                data:'vh-del-id='+id,
                dataType:'json'
            }).done((e)=>{
                if(e.success){
                    showSuccess('Véhicule supprimé!')
                    location.reload()
                } else {
                showError(e.error||"Echec de l'opération!") }
            })
        }
    }
</script>