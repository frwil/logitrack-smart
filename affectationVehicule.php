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
                $('#nom-affectation-display').html('#' + v.id_affectation);
                $('#id-vehicule-upd-aff').val(v.id_vehicule);
                $('#id-chauffeur-upd-aff').val(v.id_chauffeur);
                $('#id-typeutilisation-upd-aff').val(v.id_type_utilisation);
                $('#id-modeutilisation-upd-aff').val(v.id_mode_utilisation);
                $('#id-entite-upd-aff').val(v.id_entite);
                $('#id-region-upd-aff').val(v.id_region);
                $('#objet-upd-aff').val(v.objet_affectation);
                $('#date-debut-upd-aff').val(v.date_debut_affectation);
                $('#date-fin-upd-aff').val(v.date_fin_affectation);
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }
    function updateAffectation(){
        var valid = true
        $('#form-upd-affectation *[required]').each((e, el) => {
            $(el).removeClass('is-invalid')
            $(el).closest('.ts-wrapper').removeClass('is-invalid')
            if ($(el).val() == '') {
                valid = false
                $(el).addClass('is-invalid')
                $(el).closest('.ts-wrapper').addClass('is-invalid')
            }
        })
        if (!valid) {
            showError('Tous les champs en rouge sont obligatoires!!!')
            return false
        }
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
        if(confirm("Etes-vous sûr de vouloir supprimer cette affectation ? Les données associées (voyages, vidanges, etc.) seront conservées.")){
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
                    <input type="hidden" id="id-affectation" name="id-affectation">
                    <div class="mb-3">
                        <label for="id-vehicule-upd-aff">Véhicule</label>
                        <select id="id-vehicule-upd-aff" name="id-vehicule-upd-aff" required>
                            <?php $vehiculeRepo = new VehiculeRepository($con);
                            foreach ($vehiculeRepo->findAllWithDetails() as $r):
                                echo "<option value='" . $r['id_vehicule'] . "'>" . h($r['immatriculation_vehicule']) . " - " . h($r['nom_marque']) . "</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="id-chauffeur-upd-aff">Chauffeur</label>
                        <select id="id-chauffeur-upd-aff" name="id-chauffeur-upd-aff" required>
                            <?php $chauffeurRepo = new ChauffeurRepository($con);
                            foreach ($chauffeurRepo->findAll() as $r):
                                echo "<option value='" . $r['id_chauffeur'] . "'>" . h($r['nom_chauffeur']) . "</option>";
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="id-typeutilisation-upd-aff">Type utilisation</label>
                                <select id="id-typeutilisation-upd-aff" name="id-typeutilisation-upd-aff" required>
                                    <?php $typeUtilRepo = new TypeUtilisationRepository($con);
                                    foreach ($typeUtilRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_type_utilisation'] . "'>" . h($r['lib_type_utilisation']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="id-modeutilisation-upd-aff">Mode utilisation</label>
                                <select id="id-modeutilisation-upd-aff" name="id-modeutilisation-upd-aff" required>
                                    <?php $modeUtilRepo = new ModeUtilisationRepository($con);
                                    foreach ($modeUtilRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_mode_utilisation'] . "'>" . h($r['lib_mode_utilisation']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="id-entite-upd-aff">Entité</label>
                                <select id="id-entite-upd-aff" name="id-entite-upd-aff" required>
                                    <?php $entiteRepo = new EntiteRepository($con);
                                    foreach ($entiteRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_entite'] . "'>" . h($r['nom_entite']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label for="id-region-upd-aff">Région</label>
                                <select id="id-region-upd-aff" name="id-region-upd-aff" required>
                                    <?php $regionRepo = new RegionRepository($con);
                                    foreach ($regionRepo->findAll() as $r):
                                        echo "<option value='" . $r['id_region'] . "'>" . h($r['nom_region']) . "</option>";
                                    endforeach;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="objet-upd-aff" name="objet-upd-aff"></textarea>
                                <label for="objet-upd-aff">Objet d'affectation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="date-debut-upd-aff" name="date-debut-upd-aff">
                                <label for="date-debut-upd-aff">Date début affectation</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="date-fin-upd-aff" name="date-fin-upd-aff">
                                <label for="date-fin-upd-aff">Date fin affectation</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="updateAffectation()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>