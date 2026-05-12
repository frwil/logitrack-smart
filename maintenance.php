<?php
if (!in_array('view', $rights_maintenance)) : echo "<script>location='index.php'</script>";
endif;
function getTableauReleveKMS()
{
    global $con;
    global $rights_maintenance;
    if (in_array('viewReleveKms', $rights_maintenance)):
        $repo = new MaintenanceRepository($con);
        if (isset($_POST['date-f'])) {
            $rows = $repo->findReleveKmsByContext(getContextRegions(), getContextEntities(), $_POST['date-f'], $_POST['date-t']);
        } else {
            $rows = $repo->findReleveKmsByContext(getContextRegions(), getContextEntities());
        }
        $table = "<table id='table-releve-kms' class='no-datatable' style='display:none'><thead><tr><th>Véhicule</th><th>Région</th><th>Date Relevé</th><th>Kms</th></tr></thead><tbody>";
        foreach ($rows as $r):
            $table .= "<tr><td>" . h($r['immatriculation_vehicule']) . " - " . h($r['nom_chauffeur']) . "</td><td>" . h($r['nom_region']) . "</td><td>" . h($r['periode_releve']) . " (" . ($r['date_debut_periode_releve'] ? date('d M Y', strtotime($r['date_debut_periode_releve'])) : '') . "-" . ($r['date_fin_periode_releve'] ? date('d M Y', strtotime($r['date_fin_periode_releve'])) : '') . ")</td><td>" . h($r['km_releve']) . "</td></tr>";
        endforeach;
        $table .= "</tbody></table><div id='output' style='margin: 30px;'></div>";
        include("modalNewReleveKMS.php");
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=releveKms&action=new'>Nouveau Relevé</a>&nbsp;<button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-upd-relevekms'>Modifier Relevé</button>&nbsp;<div style='display:inline-block;padding:15px'><form class='row' style='width:700px' method='post' action='#'><div class='col-5 form-floating' style='padding-left:5px'><input type='month' id='date-f' name='date-f' value='" . (isset($_POST['date-f']) ? h($_POST['date-f']) : date('Y-m')) . "' class='form-control'><label for='date-f'>Date début</label></div><div class='col-5 form-floating' style='padding-left:5px'><input type='month' id='date-t' name='date-t' value='" . (isset($_POST['date-t']) ? h($_POST['date-t']) : date('Y-m')) . "' class='form-control'><label for='date-t'>Date fin</label></div><div class='col-2' style='padding:10px'><button class='btn btn-primary'>Afficher</button></div></form></div><hr>$table";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauVidange()
{
    global $con;
    global $rights_maintenance;
    if (in_array('viewVidange', $rights_maintenance)):
        $repo = new MaintenanceRepository($con);
        $rows = $repo->findVidangesByContext(getContextRegions(), getContextEntities());
        $table = "<table id='table-suivi-vidanges' class='table table-striped'><thead><tr><th>Véhicule</th><th>Date dernière vidange</th><th>Kms (avant vidange)</th><th>Kms (prochaine vidange)</th><th>Kms actuel (dernier relevé)</th><th>Statut</th><th></th></tr></thead><tbody>";
        $danger = 0;
        $success = 0;
        foreach ($rows as $r):
            if ($r['kms_actuel'] > $r['km_prochaine_vidange'] - 1500):
                $danger++;
            else :
                $success++;
            endif;
            $table .= "<tr><td class='text-bg-" . ($r['kms_actuel'] > $r['km_prochaine_vidange'] - 1500 ? "danger" : "success") . "'>" . h($r['immatriculation_vehicule']) . " - " . h($r['nom_chauffeur']) . "</td><td>" . date('d M Y', strtotime($r['date_vidange'])) . "</td><td>" . h($r['km_vidange']) . "</td><td>" . h($r['km_prochaine_vidange']) . "</td><td>" . h($r['kms_actuel']) . "</td><td>" . ($r['kms_actuel'] > $r['km_prochaine_vidange'] - 1500 ? "<i class='fa fa-times text-danger'></i>&nbsp;Alerte" : "<i class='fa fa-check text-success'></i>&nbsp;Ok") . "</td><td><div class='btn-group'>" . (in_array("updVidange", $rights_maintenance) ? "<button class='btn btn-light btn-sm' data-bs-toggle='modal' data-bs-target='#modal-upd-vidange' data-bs-id-vd='" . h($r['code_vidange']) . "'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("historyVidange", $rights_maintenance) ? "<button class='btn btn-light btn-sm' title='historique des vidanges' onclick='showHistory(\"" . h($r['code_vidange']) . "\")'><i class='fa fa-file'></i></button>" : "") . (in_array("delVidange", $rights_maintenance) ? "<button class='btn btn-danger btn-sm' onclick='delVidange(\"" . $r['id_vidange_vehicule'] . "\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        endforeach;
        $table .= "</tbody></table><div id='output' style='margin: 30px;'></div>";
        $stats = "<table class='no-datatable'><tbody><tr><td><span class='badge text-bg-danger' style='width:100px'><i class='fa fa-times'></i>Alerte</span></td><td>$danger</td></tr><tr><td><span class='badge text-bg-success' style='width:100px'><i class='fa fa-check'></i>Ok</span></td><td>$success</td></tr></tbody></table>";
        $btns = '';
        if (in_array('savePrestataire', $rights_maintenance)) {
            $btns .= "<a class='btn btn-primary' href='?page=maintenances&subpage=prestataire&action=new'>Nouveau Prestataire</a>&nbsp;";
        }
        if (in_array('saveVidange', $rights_maintenance)) {
            $btns .= "<a class='btn btn-primary' href='?page=maintenances&subpage=suiviVidanges&action=new'>Nouvelle vidange</a>&nbsp;";
        }
        return "$btns<hr>$table<hr>$stats";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauPrestataire()
{
    global $con;
    global $rights_maintenance;
    if (in_array("viewPrestataire", $rights_maintenance)):
        $repo = new MaintenanceRepository($con);
        $rows = $repo->findAllPrestataires();
        $table = "<table id='table-prestataire' class='table table-striped'><thead><tr><th>Prestataire</th><th>Contact</th><th>Localisation</th><th></th></tr></thead><tbody>";
        foreach ($rows as $r):
            $hash = $r['id_prestataire'];
            $table .= "<tr><td>" . h($r['nom_prestataire']) . "</td><td>" . h($r['contact_prestataire']) . "</td><td>" . h($r['localisation_prestataire']) . "</td><td><div class='btn-group'>" . (in_array("updPrestataire", $rights_maintenance) ? "<button class='btn btn-light' title='Modifier le prestataire' data-bs-toggle='modal' data-bs-target='#modal-upd-prestataire' data-bs-id-pt='$hash'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("delPrestataire", $rights_maintenance) ? "<button class='btn btn-danger' title='Supprimer' onclick='delPrestataire(\"$hash\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        endforeach;
        return $table . "</tbody></table>";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauCentreCout()
{
    global $con;
    global $rights_maintenance;
    if (in_array("viewCentreCout", $rights_maintenance)):
        $repo = new MaintenanceRepository($con);
        $rows = $repo->findAllCentresCouts();
        $table = "<table id='table-centrecouts' class='table table-striped'><thead><tr><th>Centre de coûts</th><th></th></tr></thead><tbody>";
        foreach ($rows as $r):
            $hash = $r['id_centre_cout'];
            $table .= "<tr><td>" . h($r['lib_centre_cout']) . "</td><td><div class='btn-group'>" . (in_array("updCentreCout", $rights_maintenance) ? "<button class='btn btn-light' title='Modifier le centre de coûts' data-bs-toggle='modal' data-bs-target='#modal-upd-centrecout' data-bs-id-cc='$hash'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("delCentreCout", $rights_maintenance) ? "<button class='btn btn-danger' title='Supprimer' onclick='delCentreCout(\"$hash\")'><i class='fa fa-times'></i></button>" : "") . "</div>";
        endforeach;
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=centreCouts&action=new'>Nouveau Centre de coûts</a><hr>" . $table . "</tbody></table>";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}
function getTableauBonsReparation()
{
    global $con;
    global $rights_maintenance;
    if (in_array("viewBonsReparation", $rights_maintenance)):
        $repo = new MaintenanceRepository($con);
        $rows = $repo->findAllBonsReparationByContext(getContextRegions(), getContextEntities());
        $table = "<table id='table-bons-reparation' class='table table-striped responsive'><thead><tr><th>N°</th><th>Véhicule</th><th>Date d'entrée</th><th>Diagnostic</th><th>Type d'exécution</th><th>Prestataire</th><th>Montant</th><th>Opération additionnelle</th><th>Montant opération</th><th>Montant réel</th><th>Destination</th><th>Durée réparation</th><th>Date de justification</th><th>Centre de coûts</th><th>Date prévue de sortie</th><th>Date effective de fin des travaux</th><th>Observations</th><th></th></tr></thead><tbody>";
        foreach ($rows as $r):
            $hash = $r['id_bon_reparation'];
            $table .= "<tr><td>" . h($r['num_bon_reparation']) . "</td><td>" . h($r['immatriculation_vehicule']) . " - " . h($r['nom_chauffeur']) . "</td><td>" . ($r['date_entree'] ? date('d-m-Y', strtotime($r['date_entree'])) : '') . "</td><td>" . h($r['diagnostic']) . "</td><td>" . ($r['type_execution'] == '0' ? "Interne" : "Externe") . "</td><td>" . h($r['nom_prestataire']) . "</td><td>" . h($r['montant_reparation']) . "</td><td>" . h($r['lib_plus_ou_moins_value']) . "</td><td>" . h($r['plus_ou_moins_value_valeur']) . "</td><td>" . ($r['montant_reparation'] + $r['plus_ou_moins_value_valeur'] * ($r['type_plus_ou_moins_value'] == 0 ? 1 : -1)) . "</td><td>" . h($r['destination_bon']) . "</td><td>" . h($r['duree_reparation']) . "</td><td>" . ($r['date_justification'] == '' ? "" : date('d-m-Y', strtotime($r['date_justification']))) . "</td><td>" . h($r['lib_centre_cout']) . "</td><td>" . ($r['date_prevue_sortie'] == "" ? "" : date('d-m-Y', strtotime($r['date_prevue_sortie']))) . "</td><td>" . ($r['date_fin_reparation'] == "" ? "" : date('d-m-Y', strtotime($r['date_fin_reparation']))) . "</td><td>" . h($r['observations']) . "</td><td><div class='btn-group'>" . (in_array("updBonsReparation", $rights_maintenance) ? "<button class='btn btn-light' title='Modifier' data-bs-toggle='modal' data-bs-target='#modal-upd-bonsReparation' data-bs-id-br='$hash'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array("delBonsReparation", $rights_maintenance) ? "<button class='btn btn-danger' title='Supprimer' onclick='delBonsReparation(\"$hash\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        endforeach;
        return "<a class='btn btn-primary' href='?page=maintenances&subpage=suiviBonsReparation&action=new'>Nouveau Bon de réparation</a><hr>" . $table . "<tfoot></tfoot></tbody></table>";
    else :
        return "<div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>";
    endif;
}

function getDashboardCards()
{
    global $con;
    $repo = new MaintenanceRepository($con);
    $regionIds = getContextRegions();
    $entiteIds = getContextEntities();

    $vidangeAlerts = $repo->countVidangeAlerts($regionIds, $entiteIds);
    $activeRepairs = $repo->countActiveRepairs($regionIds, $entiteIds);
    $monthlyCost = $repo->sumMonthlyCost($regionIds, $entiteIds);
    $immobilized = $repo->countImmobilizedVehicles($regionIds, $entiteIds);

    $html = '<div class="row g-3 mb-3">';

    $alertClass = $vidangeAlerts['alertes'] > 0 ? 'lt-stat-danger' : 'lt-stat-success';
    $html .= '<div class="col-md-3"><div class="lt-card lt-stat-card ' . $alertClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-exclamation-triangle"></i></div>';
    $html .= '<div class="lt-stat-value">' . $vidangeAlerts['alertes'] . ' / ' . $vidangeAlerts['total'] . '</div>';
    $html .= '<div class="lt-stat-label">Alertes vidange</div>';
    $html .= '</div></div>';

    $repairClass = $activeRepairs > 0 ? 'lt-stat-warning' : 'lt-stat-success';
    $html .= '<div class="col-md-3"><div class="lt-card lt-stat-card ' . $repairClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-tools"></i></div>';
    $html .= '<div class="lt-stat-value">' . $activeRepairs . '</div>';
    $html .= '<div class="lt-stat-label">Bons de réparation ouverts</div>';
    $html .= '</div></div>';

    $html .= '<div class="col-md-3"><div class="lt-card lt-stat-card">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-euro-sign"></i></div>';
    $html .= '<div class="lt-stat-value">' . number_format($monthlyCost, 0, ',', ' ') . ' ' . devise() . '</div>';
    $html .= '<div class="lt-stat-label">Coût maintenance (mois)</div>';
    $html .= '</div></div>';

    $rate = $immobilized['total'] > 0 ? round($immobilized['immobilises'] / $immobilized['total'] * 100) : 0;
    $immobClass = $rate > 20 ? 'lt-stat-danger' : ($rate > 10 ? 'lt-stat-warning' : 'lt-stat-success');
    $html .= '<div class="col-md-3"><div class="lt-card lt-stat-card ' . $immobClass . '">';
    $html .= '<div class="lt-stat-icon"><i class="fa fa-truck"></i></div>';
    $html .= '<div class="lt-stat-value">' . $immobilized['immobilises'] . ' / ' . $immobilized['total'] . '</div>';
    $html .= '<div class="lt-stat-label">Véhicules immobilisés</div>';
    $html .= '</div></div>';

    $html .= '</div>';
    return $html;
}

/* POST handled by MaintenanceController — see controllers/router.php */
?>
<?php if (isset($_GET['subpage']) && $_GET['subpage'] == 'releveKms') : ?>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("saveReleveKms", $rights_maintenance)): ?>
        <script>
            setTimeout(() => {
                openModalReleve()
            }, 2000)
        </script>
    <?php endif; ?>
    <div class="modal fade" id="modal-upd-relevekms" tabindex="-1" aria-labelledby="modal-upd-relevekmsLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-relevekmsLabel">Relevé de kilométrage <span id='relevekmsdisplay'></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="#" id="form-upd-relevekms">
                        <div class="form-floating mb-3">
                            <input type="month" required id="date-upd-releve-kms" name="date-upd-releve-kms" value="<?php if (isset($_GET['dch'])) echo h(date('Y-m', strtotime($_GET['dch'])));
                                                                                                                    else echo date('Y-m'); ?>" class="form-control" onchange="getSemainesToUpd(this.value)" <?php if (isset($_GET['dch'])) echo "readonly"; ?>>
                            <label for="date-upd-releve-kms">Période</label>
                        </div>
                        <div class="mb-3">

                            <label for="per-upd-releve-kms">Semaine</label>

                            <select required name="per-upd-releve-kms" id="per-upd-releve-kms" class="no-tom-select" onchange="$('#vh-upd-releve-kms').change()">

                            </select>

                        </div>
                        <div class="mb-3">

                            <label for="vh-upd-releve-kms">Véhicule</label>

                            <select required id="vh-upd-releve-kms" name="vh-upd-releve-kms" onchange="getKmsPeriode(this.value,$('#per-upd-releve-kms').val())">
                                <?php $affRepo = new AffectationRepository($con);
                                foreach ($affRepo->findActiveByContext(getContextRegions(), getContextEntities()) as $r):
                                    $affHash = $r['id_affectation'];
                                    echo "<option value='" . $affHash . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == $affHash ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>

                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" min="0" value="0" required id="val-upd-releve-kms" name="val-upd-releve-kms" class="form-control">
                            <label for="val-upd-releve-kms">Km (valeur)</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updateReleve()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const modalUpdRel = document.getElementById('modal-upd-relevekms')
        if (modalUpdRel) {
            modalUpdRel.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                getSemainesToUpd($('#date-upd-releve-kms').val())
            })
        }
        $(function() {
            google.charts.load("current", {
                packages: ["corechart", "charteditor"]
            });
            google.charts.setOnLoadCallback(function() {
                var tpl = $.pivotUtilities.aggregatorTemplates;
                var derivers = $.pivotUtilities.derivers;
                var renderers = $.extend($.pivotUtilities.renderers,
                    $.pivotUtilities.gchart_renderers);
                $("#output").pivotUI($("#table-releve-kms"), {
                    rows: ["Véhicule", "Région"],
                    cols: ["Date Relevé"],
                    aggregators: {
                        "Kms": function() {
                            return tpl.sum()(["Kms"])
                        }
                    },
                    renderers: renderers,
                    rendererName: "Table Barchart",
                    filter: (e) => {
                        var selNames = <?php echo json_encode($_SESSION['usr-con']['region-sel-names'] ?? []); ?>;
                        return selNames.some(function(n) { return e["Région"].toLowerCase() == n.toLowerCase(); })
                    }
                });
            });
        });

        function getSemainesToUpd(p) {
            $.ajax({
                type: 'post',
                data: 'semPer=' + p + '-01',
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    $('#per-upd-releve-kms').html(e.html)
                } else {
                    showError(e.error || "Erreur lors du chargement des semaines")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement des semaines")
            })
        }

        function getKmsPeriode(v, p) {
            $.ajax({
                type: 'post',
                data: 'vhPer=' + v + '&perSem=' + p,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    $('#val-upd-releve-kms').val(e.kms)
                } else {
                    showError(e.error || "Erreur lors du chargement du kilométrage")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement du kilométrage")
            })
        }

        function updateReleve() {
            var valid = true
            $('#modal-upd-relevekms *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                $(el).closest('.ts-wrapper').removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                    $(el).closest('.ts-wrapper').addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#form-upd-relevekms').notify("Tous les champs en rouge sont obligatoires!", {
                    postion: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: 'updRel=' + $('#per-upd-releve-kms').val() + '&vhRel=' + $('#vh-upd-releve-kms').val() + '&kmsRel=' + $('#val-upd-releve-kms').val(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Enregistrement effectué!')
                    location.reload()
                } else {
                    $('#form-upd-relevekms').notify(e.error || "Erreur lors de l'enregistrement", {
                        position: 'top'
                    })
                }
            }).fail((jqXHR) => {
                $('#form-upd-relevekms').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement", {
                    position: 'top'
                })
            })
        }
    </script>
    <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'prestataire') :
    if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("savePrestataire", $rights_maintenance)):
        include("modalNewPrestataire.php");
    ?>
        <script>
            setTimeout(() => {
                openModalPrestataire()
            }, 2000)
        </script>
    <?php endif; ?>
    <div class="modal fade" id="modal-upd-prestataire" tabindex="-1" aria-labelledby="modal-upd-prestataireLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-prestataireLabel">Prestataire : <span id='id-prestataire'></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-upd-pt">
                        <div class="form-floating mb-3">
                            <input type="text" id="nom-upd-pt" name="nom-upd-pt" required class="form-control">
                            <input type="hidden" id="id-upd-pt" name="id-upd-pt">
                            <label for="nom-upd-pt">Nom Prestataire</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" id="contact-upd-pt" name="contact-upd-pt" class="form-control">
                            <label for="contact-upd-pt">Contact</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="localisation-upd-pt" name="localisation-upd-pt">
                            <label for="localisation-upd-pt">Localisation</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updatePT()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        const modalUpdPT = document.getElementById('modal-upd-prestataire')
        if (modalUpdPT) {
            modalUpdPT.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const id = event.relatedTarget.getAttribute('data-bs-id-pt')
                $.ajax({
                    type: 'post',
                    data: 'c-pt-s=' + id,
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        let v = e.data
                        $('#nom-upd-pt').val(v.nom_prestataire)
                        $('#contact-upd-pt').val(v.contact_prestataire)
                        $('#localisation-upd-pt').val(v.localisation_prestataire)
                        $('#id-prestataire').html(v.nom_prestataire)
                        $('#id-upd-pt').val(v.id_prestataire)
                    } else {
                        showError(e.error || "Erreur lors du chargement")
                    }
                }).fail((jqXHR) => {
                    showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
                })
            })
        }

        function updatePT() {
            var valid = true
            $('#form-upd-pt *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                $(el).closest('.ts-wrapper').removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                    $(el).closest('.ts-wrapper').addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#form-upd-pt').notify("Tous les champs en rouge sont obligatoires!", {
                    position: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: $('#form-upd-pt').serialize(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Enregistrement effectué')
                    location.reload()
                } else {
                    $('#modal-upd-prestataire .modal-body').notify(e.error || "Erreur lors de l'enregistrement!", {
                        position: 'top'
                    })
                }
            }).fail((jqXHR) => {
                $('#modal-upd-prestataire .modal-body').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            })
        }

        function delPrestataire(id) {
            if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                $.ajax({
                    type: 'post',
                    data: 'del-pt-id=' + id,
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        showSuccess('Suppression effectuée!')
                        location.reload()
                    } else {
                        showError(e.error || "Erreur lors de la suppression!")
                    }
                }).fail((jqXHR) => {
                    showError(jqXHR.responseJSON?.error || "Erreur lors de la suppression!")
                })
            }
        }
    </script>
    <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'suiviVidanges') : include("modalNewVidange.php");
    if (isset($_GET['action']) && $_GET['action'] == 'new'):
    ?>
        <script>
            setTimeout(() => {
                openModalVidange()
            }, 2000)
        </script>
    <?php endif;
    ?>
    <div id="table-history-div">
        <table class="table table-striped no-datatable" id="table-history" style="display:none">
            <thead>
                <tr>
                    <th colspan='6'></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
    <div class="modal fade" id="modal-upd-vidange" tabindex="-1" aria-labelledby="modal-upd-vidangeLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-vidangeLabel">Vidange <span id="code-vidange"></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-upd-vidange">
                        <div class="form-floating mb-3">
                            <input type="date" id="date-upd-vd" name="date-upd-vd" required class="form-control">
                            <label for="date-vd">Date Vidange</label>
                        </div>
                        <div class="mb-3">

                            <label for="vh-upd-vd">Véhicule</label>

                            <select id="vh-upd-vd" name="vh-upd-vd" required>
                                <?php $affRepo = new AffectationRepository($con);
                                foreach ($affRepo->findActiveByContext(getContextRegions(), getContextEntities()) as $r):
                                    $affHash = $r['id_affectation'];
                                    echo "<option value='" . $affHash . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == $affHash ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                endforeach;
                                ?>
                            </select>

                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="km-upd-av-vd" name="km-upd-av-vd" required min="0" value="0">
                            <label for="km-upd-av-vd">Km (avant vidange)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="km-upd-next-vd" name="km-upd-next-vd" required min="0" value="0">
                            <label for="km-upd-av-vd">Km (prochaine vidange)</label>
                        </div>
                        <div class="mb-3">

                            <label for="id-upd-pt-vd">Prestataire</label>

                            <select id="id-upd-pt-vd" name="id-upd-pt-vd" required>
                                <?php $ptRepo = new MaintenanceRepository($con);
                                foreach ($ptRepo->findAllPrestataires() as $r):
                                    echo "<option value='" . $r['id_prestataire'] . "'>" . h($r['nom_prestataire']) . "</option>";
                                endforeach;
                                ?>
                            </select>

                        </div>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="comment-upd-vd" name="comment-upd-vd"></textarea>
                            <label for="comment-upd-vd">Commentaire</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updateVidange()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        const modalUpdVidange = document.getElementById('modal-upd-vidange')
        if (modalUpdVidange) {
            modalUpdVidange.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const id = event.relatedTarget.getAttribute('data-bs-id-vd')
                $('#code-vidange').html(id)
                $.ajax({
                    type: 'post',
                    data: 'c-vd-s=' + id,
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        let v = e.data
                        $('#date-upd-vd').val(v.date_vidange)
                        $('#vh-upd-vd option[value="' + v.id_affectation_vehicule + '"]').prop('selected', true)
                        $('#km-upd-av-vd').val(v.km_vidange)
                        $('#km-upd-next-vd').val(v.km_prochaine_vidange)
                        $('#id-upd-pt-vd option[value="' + v.id_prestataire + '"]').prop('selected', true)
                        $('#comment-upd-vd').val(v.commentaire_vidange)
                    } else {
                        showError(e.error || "Erreur lors du chargement")
                    }
                }).fail((jqXHR) => {
                    showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
                })

            })
        }

        function updateVidange() {
            var valid = true
            $('#form-upd-vidange *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                $(el).closest('.ts-wrapper').removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                    $(el).closest('.ts-wrapper').addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#modal-upd-vidange .modal-body').notify("Tous les champs en rouge sont obligatoires", {
                    position: 'top'
                })
                return false
            }
            if (parseFloat($('#km-upd-av-vd').val()) >= parseFloat($('#km-upd-next-vd').val())) {
                $('#form-upd-vidange').notify("Le km de prochaine vidange doit être supérieur au km avant vidange", {
                    position: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: $('#form-upd-vidange').serialize() + '&c-upd-vd=' + $('#code-vidange').html(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Enregistrement effectué')
                    location.reload()
                } else {
                    $('#modal-upd-vidange').notify(e.error || "Erreur lors de l'enregistrement!", {
                        position: 'top'
                    })
                }
            }).fail((jqXHR) => {
                $('#modal-upd-vidange').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            })
        }

        function showHistory(code) {
            $.ajax({
                type: 'post',
                data: 'cd-vd-hist=' + code,
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    $('#table-history tbody').html(e.html)
                    exportToExcel('table-history', 'historique_vidange_' + code.replace(/-/g, '_'))
                } else {
                    showError(e.error || "Erreur lors du chargement de l'historique")
                }
            }).fail((jqXHR) => {
                showError(jqXHR.responseJSON?.error || "Erreur lors du chargement de l'historique")
            })
        }
        function delVidange(id){
            if(confirm("Etes-vous sûr de vouloir supprimer ?")) {
                $.ajax({
                    type: 'post',
                    data: 'del-vd-id=' + id,
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        showSuccess('Suppression effectuée!')
                        location.reload()
                    } else {
                        showError(e.error || "Erreur lors de la suppression!")
                    }
                }).fail((jqXHR) => {
                    showError(jqXHR.responseJSON?.error || "Erreur lors de la suppression!")
                })
            }
        }

        function tableToXLSX(tableId) {
            const table = document.getElementById(tableId);
            const data = [];

            // Récupérer les données du tableau
            for (let i = 0; i < table.rows.length; i++) {
                const row = table.rows[i];
                const rowData = [];
                for (let j = 0; j < row.cells.length; j++) {
                    rowData.push(row.cells[j].innerText);
                }
                data.push(rowData);
            }

            // Créer un objet workbook et worksheet
            const workbook = XLSX.utils.book_new();
            const worksheet = XLSX.utils.aoa_to_sheet(data);

            // Ajouter le worksheet au workbook
            XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");

            // Générer le fichier XLSX
            const excelBuffer = XLSX.write(workbook, {
                bookType: "xlsx",
                type: "array"
            });

            return excelBuffer;
        }

        function downloadXLSX(excelBuffer, filename = "export.xlsx") {
            const blob = new Blob([excelBuffer], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        }

        function exportToExcel(tableId, fn = '') {
            const excelBuffer = tableToXLSX(tableId);
            downloadXLSX(excelBuffer, fn);
        }
    </script>
    <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'centreCouts') :
    include("modalNewCentreCout.php");
    if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("saveCentreCout", $rights_maintenance)):
    ?>
        <script>
            setTimeout(() => {
                openModalCentreCout()
            }, 2000)
        </script>
    <?php endif;
    ?>
    <div class="modal fade" id="modal-upd-centrecout" tabindex="-1" aria-labelledby="modal-upd-centrecoutLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal-upd-centrecoutLabel">Centre de coût : <span id='id-centrecout'></span></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="form-upd-cc">
                        <div class="form-floating mb-3">
                            <input type="text" id="nom-upd-cc" name="nom-upd-cc" required class="form-control">
                            <input type="hidden" id="id-upd-cc" name="id-upd-cc">
                            <label for="nom-upd-cc">Désignation Centre de coût</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" onclick="updateCC()">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateCC() {
            var valid = true
            $('#form-upd-cc *[required]').each((e, el) => {
                $(el).removeClass('is-invalid')
                $(el).closest('.ts-wrapper').removeClass('is-invalid')
                if ($(el).val() == '') {
                    valid = false
                    $(el).addClass('is-invalid')
                    $(el).closest('.ts-wrapper').addClass('is-invalid')
                }
            })
            if (!valid) {
                $('#form-upd-cc').notify("Tous les champs en rouge sont obligatoires!", {
                    position: 'top'
                })
                return false
            }
            $.ajax({
                type: 'post',
                data: $('#form-upd-cc').serialize(),
                dataType: 'json'
            }).done((e) => {
                if (e.success) {
                    showSuccess('Enregistrement effectué')
                    location.reload()
                } else {
                    $('#modal-upd-centrecout .modal-body').notify(e.error || "Erreur lors de l'enregistrement!", {
                        position: 'top'
                    })
                }
            }).fail((jqXHR) => {
                $('#modal-upd-centrecout .modal-body').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement!", {
                    position: 'top'
                })
            })
        }
        const modalUpdCC = document.getElementById('modal-upd-centrecout')
        if (modalUpdCC) {
            modalUpdCC.addEventListener('show.bs.modal', event => {
                // Button that triggered the modal
                const id = event.relatedTarget.getAttribute('data-bs-id-cc')
                $.ajax({
                    type: 'post',
                    data: 'c-cc-s=' + id,
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        let v = e.data
                        $('#nom-upd-cc').val(v.lib_centre_cout)
                        $('#id-centrecout').html(v.lib_centre_cout)
                        $('#id-upd-cc').val(v.id_centre_cout)
                    } else {
                        showError(e.error || "Erreur lors du chargement")
                    }
                }).fail((jqXHR) => {
                    showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
                })
            })
        }

        function delCentreCout(id) {
            if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                $.ajax({
                    type: 'post',
                    data: 'del-cc-id=' + id,
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        showSuccess('Suppression effectuée!')
                        location.reload()
                    } else {
                        showError(e.error || "Erreur lors de la suppression!")
                    }
                }).fail((jqXHR) => {
                    showError(jqXHR.responseJSON?.error || "Erreur lors de la suppression!")
                })
            }
        }
    </script>
    <?php elseif (isset($_GET['subpage']) && $_GET['subpage'] == 'suiviBonsReparation') : include("modalNewSuiviBonsReparation.php"); include("modalUpdBonsReparation.php"); ?>
        <script>
            function delBonsReparation(id) {
                if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                    $.ajax({
                        type: 'post',
                        data: 'del-br-id=' + id,
                        dataType: 'json'
                    }).done((e) => {
                        if (e.success) {
                            showSuccess('Opération effectuée!')
                            location.reload()
                        } else {
                            showError(e.error || "Echec de l'opération!")
                        }
                    }).fail((jqXHR) => {
                        showError(jqXHR.responseJSON?.error || "Echec de l'opération!")
                    })
                }
            }
        </script>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'new' && in_array("saveBonsReparation", $rights_maintenance)): ?>
        <script>
            setTimeout(() => {
                openModalSuiviBonsReparation()
            }, 2000)
        </script>
    <?php endif;
    ?>
<?php endif; ?>
<style>
    .modal.show .modal-dialog {
        color: inherit !important;
        padding: inherit !important;
        border-radius: 0.5rem;
        position: relative
    }

    .ts-wrapper {
        display: block
    }
</style>
<?php
function getDashboardCharts()
{
    $html = '<div class="row g-3 mb-3">';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Évolution des coûts (12 mois)</h2></div>';
    $html .= '<div id="chart-budget" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Coûts par centre</h2></div>';
    $html .= '<div id="chart-centres" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-md-4"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Répartition par type</h2></div>';
    $html .= '<div id="chart-type" style="height: 300px;"></div></div></div>';
    $html .= '<div class="col-md-8"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Durée moyenne par diagnostic</h2></div>';
    $html .= '<div id="chart-diagnostic" style="height: 300px;"></div></div></div>';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Comparaison des prestataires</h2></div>';
    $html .= '<div id="chart-providers" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-md-6"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Véhicules à pannes récurrentes (6 mois)</h2></div>';
    $html .= '<div id="table-recurrence" style="max-height:350px;overflow-y:auto;"></div></div></div>';
    $html .= '<div class="row g-3 mb-3">';
    $html .= '<div class="col-12"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Alertes documents (30 jours)</h2></div>';
    $html .= '<div id="table-docs-expiration" style="max-height:250px;overflow-y:auto;"></div></div></div>';
    $html .= '</div>';
    $html .= '<div class="row g-3 mb-3">';
    $html .= '<div class="col-md-7"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Impact chauffeur sur la maintenance</h2></div>';
    $html .= '<div id="chart-chauffeur-impact" style="height: 350px;"></div></div></div>';
    $html .= '<div class="col-md-5"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Conflits réparation / voyages</h2></div>';
    $html .= '<div id="table-repair-conflicts" style="max-height:350px;overflow-y:auto;"></div></div></div>';
    $html .= '</div>';
    $html .= '<div class="row g-3 mb-3">';
    $html .= '<div class="col-12"><div class="lt-card"><div class="lt-card-header"><h2 class="lt-card-title">Coût au kilomètre par véhicule</h2></div>';
    $html .= '<div id="chart-costkm"></div></div></div>';
    $html .= '</div>';
    $html .= '<script>
    google.charts.load("current", {packages: ["corechart", "table"]});
    google.charts.setOnLoadCallback(function() {
        $.ajax({type:"post", data:"load-dashboard-all=1", dataType:"json"})
        .done(function(resp) {
            var d = resp.data;
            // Budget projection — LineChart
            if (d.budget && d.budget.length) {
                var dt = new google.visualization.DataTable();
                dt.addColumn("string", "Mois");
                dt.addColumn("number", "Coût (' . devise() . ')");
                d.budget.forEach(function(r) { dt.addRow([r.mois, parseFloat(r.total)]); });
                new google.visualization.LineChart(document.getElementById("chart-budget"))
                    .draw(dt, {title:"Évolution mensuelle des coûts", curveType:"function", legend:"none", colors:["#5D54A4"], chartArea:{width:"85%", height:"75%"}});
            }
            // Cost by centre — ColumnChart
            if (d.centres && d.centres.length) {
                var dtC = new google.visualization.DataTable();
                dtC.addColumn("string", "Centre de coûts");
                dtC.addColumn("number", "Coût total (' . devise() . ')");
                dtC.addColumn("number", "Nb bons");
                dtC.addColumn({type:"string", role:"tooltip", p:{html:true}});
                d.centres.forEach(function(r) {
                    var cout = parseFloat(r.total_cout);
                    var nb = parseInt(r.nb_bons);
                    dtC.addRow([r.lib_centre_cout, cout, nb, r.lib_centre_cout + ": " + cout.toLocaleString() + " (' . devise() . ') - " + nb + " bon(s)"]);
                });
                new google.visualization.ColumnChart(document.getElementById("chart-centres"))
                    .draw(dtC, {title:"Répartition des coûts par centre", legend:"none", colors:["#5D54A4"], chartArea:{width:"75%", height:"75%"}, hAxis:{title:"Centre de coûts"}, vAxis:{title:"Coût total (' . devise() . ')"}});
            }
            // Execution type — PieChart
            if (d.typeExec && d.typeExec.length) {
                var dtT = new google.visualization.DataTable();
                dtT.addColumn("string", "Type");
                dtT.addColumn("number", "Coût");
                d.typeExec.forEach(function(r) {
                    dtT.addRow([r.type_execution == "0" ? "Interne" : "Externe", parseFloat(r.total_cout)]);
                });
                new google.visualization.PieChart(document.getElementById("chart-type"))
                    .draw(dtT, {title:"Coûts par type d\'exécution", colors:["#5D54A4","#7C78B8"], chartArea:{width:"80%", height:"70%"}, pieHole:0.4});
            }
            // Duration by diagnostic — BarChart
            if (d.diagnostics && d.diagnostics.length) {
                var dtD = new google.visualization.DataTable();
                dtD.addColumn("string", "Diagnostic");
                dtD.addColumn("number", "Durée moyenne (j)");
                dtD.addColumn("number", "Nb bons");
                d.diagnostics.forEach(function(r) {
                    dtD.addRow([r.diagnostic, parseFloat(r.duree_moyenne), parseInt(r.nb_bons)]);
                });
                new google.visualization.BarChart(document.getElementById("chart-diagnostic"))
                    .draw(dtD, {title:"Durée moyenne de réparation par diagnostic", legend:"none", colors:["#3D3486"], chartArea:{width:"65%", height:"75%"}});
            }
            // Provider comparison — ColumnChart
            if (d.providers && d.providers.length) {
                var dtP = new google.visualization.DataTable();
                dtP.addColumn("string", "Prestataire");
                dtP.addColumn("number", "Nb réparations");
                dtP.addColumn("number", "Durée moy (j)");
                dtP.addColumn("number", "Coût moyen (' . devise() . ')");
                d.providers.forEach(function(r) { dtP.addRow([r.nom_prestataire, parseInt(r.nb_reparations), parseFloat(r.duree_moyenne), parseFloat(r.cout_moyen)]); });
                new google.visualization.ColumnChart(document.getElementById("chart-providers"))
                    .draw(dtP, {title:"Performance par prestataire", colors:["#5D54A4","#7C78B8","#3D3486"], chartArea:{width:"80%", height:"70%"}});
            }
            // Recurrence — Table
            if (d.recurrence && d.recurrence.length) {
                var dtR = new google.visualization.DataTable();
                dtR.addColumn("string", "Véhicule");
                dtR.addColumn("number", "Nb pannes");
                dtR.addColumn("number", "Coût total (' . devise() . ')");
                dtR.addColumn("number", "Durée moy (j)");
                dtR.addColumn("string", "Dernière panne");
                d.recurrence.forEach(function(r) {
                    dtR.addRow([r.immatriculation_vehicule, parseInt(r.nb_pannes), parseFloat(r.total_cout), parseFloat(r.duree_moyenne), r.derniere_panne]);
                });
                new google.visualization.Table(document.getElementById("table-recurrence"))
                    .draw(dtR, {showRowNumber:true, width:"100%", height:"100%", page:"enable", pageSize:10, sortColumn:1, sortAscending:false});
            } else {
                $("#table-recurrence").html("<div class=\"p-3 text-muted\">Aucun véhicule avec pannes récurrentes sur les 6 derniers mois.</div>");
            }
            // Cost per km — Table
            if (d.costPerKm && d.costPerKm.length) {
                var dtK = new google.visualization.DataTable();
                dtK.addColumn("string", "Véhicule");
                dtK.addColumn("number", "Coût total (' . devise() . ')");
                dtK.addColumn("number", "Km parcourus");
                dtK.addColumn("number", "Coût/km (' . devise() . ')");
                d.costPerKm.forEach(function(r) {
                    var km = parseFloat(r.km_max) - parseFloat(r.km_min);
                    var coutKm = km > 0 ? parseFloat(r.total_cout) / km : 0;
                    dtK.addRow([r.immatriculation_vehicule, parseFloat(r.total_cout), km, coutKm]);
                });
                new google.visualization.Table(document.getElementById("chart-costkm"))
                    .draw(dtK, {showRowNumber:true, width:"100%", height:"100%", page:"enable", pageSize:15});
            }
            // Docs expiration — Table
            if (d.docsExpiration && d.docsExpiration.length) {
                var dtE = new google.visualization.DataTable();
                dtE.addColumn("string", "Véhicule");
                dtE.addColumn("string", "Document");
                dtE.addColumn("string", "Expiration");
                dtE.addColumn("number", "Jours restants");
                dtE.addColumn("string", "Réf");
                d.docsExpiration.forEach(function(r) {
                    dtE.addRow([r.immatriculation_vehicule, r.nom_document, r.date_expiration_document, parseInt(r.jours_restants), r.ref_document]);
                });
                new google.visualization.Table(document.getElementById("table-docs-expiration"))
                    .draw(dtE, {showRowNumber:false, width:"100%", height:"100%", sortColumn:3, sortAscending:true, page:"enable", pageSize:10});
            } else {
                $("#table-docs-expiration").html("<div class=\"p-3 text-success fw-bold\"><i class=\"fa fa-check-circle\"></i> Aucun document n\'expire dans les 30 prochains jours.</div>");
            }
            // Chauffeur impact — BarChart
            if (d.chauffeurImpact && d.chauffeurImpact.length) {
                var dtH = new google.visualization.DataTable();
                dtH.addColumn("string", "Chauffeur");
                dtH.addColumn("number", "Coût total (' . devise() . ')");
                dtH.addColumn("number", "Nb pannes");
                dtH.addColumn("number", "Durée moy (j)");
                d.chauffeurImpact.forEach(function(r) {
                    dtH.addRow([r.nom_chauffeur, parseFloat(r.total_cout), parseInt(r.nb_pannes), parseFloat(r.duree_moyenne)]);
                });
                new google.visualization.BarChart(document.getElementById("chart-chauffeur-impact"))
                    .draw(dtH, {title:"Coûts de maintenance par chauffeur", legend:"none", colors:["#5D54A4"], chartArea:{width:"60%", height:"75%"}, hAxis:{title:"Coût total (' . devise() . ')"}});
            }
            // Repair conflicts — Table
            if (d.repairConflicts && d.repairConflicts.length) {
                var dtX = new google.visualization.DataTable();
                dtX.addColumn("string", "Véhicule");
                dtX.addColumn("string", "Chauffeur");
                dtX.addColumn("string", "Bon réparation");
                dtX.addColumn("string", "Sortie prévue");
                dtX.addColumn("string", "Voyage");
                dtX.addColumn("string", "Date voyage");
                dtX.addColumn("number", "Décalage (j)");
                d.repairConflicts.forEach(function(r) {
                    dtX.addRow([r.immatriculation_vehicule, r.nom_chauffeur, r.num_bon_reparation, r.date_prevue_sortie, r.titre_voyage, r.date_voyage, parseInt(r.decalage_jours)]);
                });
                new google.visualization.Table(document.getElementById("table-repair-conflicts"))
                    .draw(dtX, {showRowNumber:false, width:"100%", height:"100%", sortColumn:6, sortAscending:false, page:"enable", pageSize:10});
            } else {
                $("#table-repair-conflicts").html("<div class=\"p-3 text-success fw-bold\"><i class=\"fa fa-check-circle\"></i> Aucun conflit détecté entre réparations en cours et voyages planifiés.</div>");
            }
        });
        // Lazy-load health scores & upcoming vidanges (separate calls to prevent blocking page load)
        $.ajax({type:"post", data:"load-health-scores=1", dataType:"json"})
        .done(function(e) { if (e.html) $("#health-scores-section").html(e.html); })
        .fail(function() { $("#health-scores-section").html("<div class=\"alert alert-warning\">Erreur lors du chargement des scores de santé.</div>"); });
        $.ajax({type:"post", data:"load-upcoming-vidanges=1", dataType:"json"})
        .done(function(e) { if (e.html) $("#upcoming-vidanges-section").html(e.html); })
        .fail(function() { $("#upcoming-vidanges-section").html("<div class=\"alert alert-warning\">Erreur lors du chargement de la planification.</div>"); });
    });
    </script>';
    return $html;
}
function getHealthScores()
{
    global $con;
    $repo = new MaintenanceRepository($con);
    $rows = $repo->vehicleHealthScores(getContextRegions(), getContextEntities());
    if (!count($rows)) return '<div class="alert alert-info">Aucun véhicule actif trouvé.</div>';

    $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Score de santé des véhicules</h2></div>';
    $html .= '<table id="table-health-scores" class="table table-striped no-datatable"><thead><tr>
        <th>Véhicule</th><th>Chauffeur</th><th>Km actuel</th><th>Proch. vidange</th>
        <th>Pannes (6 mois)</th><th>Coût total (' . devise() . ')</th><th>Score</th><th>État</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $score = (int)$r['score'];
        $color = $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger');
        $etat = $score >= 70 ? 'Bon' : ($score >= 40 ? 'Moyen' : 'Critique');
        $html .= '<tr>
            <td>' . h($r['immatriculation_vehicule']) . '</td>
            <td>' . h($r['nom_chauffeur']) . '</td>
            <td>' . ($r['km_actuel'] ?? '-') . '</td>
            <td>' . ($r['km_prochaine_vidange'] ?? '-') . '</td>
            <td>' . (int)($r['nb_pannes_6mois'] ?? 0) . '</td>
            <td>' . number_format((float)($r['total_cout'] ?? 0), 0, ',', ' ') . '</td>
            <td><span class="lt-badge lt-badge-' . $color . '">' . $score . '/100</span></td>
            <td><span class="text-' . $color . ' fw-bold">' . $etat . '</span></td></tr>';
    }
    $html .= '</tbody></table></div>';
    $html .= '<script>$("#table-health-scores").DataTable({order:[[6,"asc"]], pageLength:25, destroy:true});</script>';
    return $html;
}

function getUpcomingVidanges()
{
    global $con;
    $repo = new MaintenanceRepository($con);
    $rows = $repo->upcomingVidanges(60, getContextRegions(), getContextEntities());
    if (!count($rows)) return '<div class="alert alert-info">Aucune vidange à prévoir dans les 60 jours.</div>';

    $html = '<div class="lt-card mb-3"><div class="lt-card-header"><h2 class="lt-card-title">Planification des vidanges à venir (60 jours)</h2></div>';
    $html .= '<table id="table-upcoming-vidanges" class="table table-striped no-datatable"><thead><tr>
        <th>Véhicule</th><th>Chauffeur</th><th>Km actuel</th><th>Proch. vidange</th>
        <th>Km restants</th><th>Km/jour</th><th>Jours estimés</th><th>Urgence</th></tr></thead><tbody>';
    foreach ($rows as $r) {
        $urgenceClass = $r['urgence'] === 'Dépassée' ? 'danger' : ($r['urgence'] === 'Urgent' ? 'warning' : ($r['urgence'] === 'Bientôt' ? 'info' : 'success'));
        $html .= '<tr>
            <td>' . h($r['immatriculation_vehicule']) . '</td>
            <td>' . h($r['nom_chauffeur']) . '</td>
            <td>' . ($r['km_actuel'] ?? '-') . '</td>
            <td>' . ($r['km_prochaine_vidange'] ?? '-') . '</td>
            <td>' . (int)($r['km_restant'] ?? 0) . '</td>
            <td>' . ($r['km_moyen_jour'] ?? '-') . '</td>
            <td>' . (int)($r['jours_estimes'] ?? 0) . '</td>
            <td><span class="lt-badge lt-badge-' . $urgenceClass . '">' . h($r['urgence']) . '</span></td></tr>';
    }
    $html .= '</tbody></table></div>';
    $html .= '<script>$("#table-upcoming-vidanges").DataTable({order:[[6,"asc"]], pageLength:25, destroy:true});</script>';
    return $html;
}
?>