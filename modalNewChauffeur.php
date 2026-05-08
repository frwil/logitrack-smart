<?php
/* POST handled by ChauffeurController — see controllers/router.php */
if (isset($_POST['refresh-marque'])):
    $marqueRepo = new MarqueRepository($con);
    $liste = "";
    foreach ($marqueRepo->findAll() as $r):
        $liste .= "<option value='" . h($r['id_marque']) . "'>" . h($r['nom_marque']) . "</option>";
    endforeach;
    die(json_encode(['success' => true, 'html' => $liste]));
endif;
?>
<div class="modal fade" id="modal-new-chauffeur" tabindex="-1" aria-labelledby="modal-new-chauffeurLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-new-chauffeurLabel">Nouveau chauffeur de véhicule</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-chauffeur">
                    <div class="form-floating mb-3">
                        <input type="text" id="nom-chauffeur" name="nom-chauffeur" required class="form-control">
                        <label for="nom-chauffeur">Nom chauffeur</label>
                    </div>
                    <div class="mb-3">

                        <label for="type-permis">Cat. de permis (la plus élevée)</label>

                        <select id="type-permis" name="type-permis" required>
                            <?php $configRepo = new ConfigRepository($con);
                            foreach ($configRepo->findAllTypePermis() as $r):
                                echo "<option value='" . $r['id_type_permis'] . "'>" . h($r['lib_type_permis']) . "</option>";
                            endforeach;
                            ?>
                        </select>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" onclick="saveChauffeur()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<script>
    function openModalChauffeur() {
        $('#modal-new-chauffeur').modal('show')
    }

    function refreshChauffeurOptions() {
        $.ajax({
            type: 'post',
            data: 'refresh-chauffeur=1',
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                $('#marque-vh').html(e.html)
            } else {
                showError(e.error || "Erreur lors du chargement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
        })
    }

    function saveChauffeur() {
        if ($('#nom-chauffeur').val() == '') {
            showError("Le champ est obligatoire!");
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-chauffeur').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess("Nouveau chauffeur créee!!")
                $('#modal-new-chauffeur').modal('hide')
                $('#form-new-chauffeur *').val('')
                refreshChauffeurOptions()
                location = "?page=affectationVehicules&subpage=listeChauffeurs"
            } else if (e.error == '1062') {
                showError("Ce chauffeur existe déjà")
            } else {
                showError(e.error || "Erreur lors de l'enregistrement")
            }
        }).fail((jqXHR) => {
            showError(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement")
        })
    }
</script>