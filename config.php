<script>
    $('div.container-fluid .col-2').remove()
    $('div.container-fluid .col-10').removeClass('col-10').addClass('col-12')
</script>
<?php
// Config sub-module specificity arrays for backward-compatible rights checks
$permisSpecifics  = ['viewPermis','savePermis','updPermis','delPermis'];
$docsSpecifics    = ['viewDocs','saveDocs','updDocs','delDocs'];
$foldersSpecifics = ['viewFolders','saveFolders','updFolders','delFolders'];
$rights_config = [];
if (isRightObjectAllowed('config', $user_rights) != false) {
    $rights_config = explode(',', isRightObjectAllowed('config', $user_rights));
}
?>
<?php function getTableauDriveLicence()
{
    global $con;
    global $rights_config;
    global $permisSpecifics;
    $repo = new ConfigRepository($con);
    $rows = $repo->findAllTypePermis();
    $tableau = "<table class='table table-striped responsive " . ((isset($_GET['action']) && $_GET['action'] == 'tableexport') ? "no-datatable" : "") . "' id='table-drivelicence'><thead><tr><th>#</th><th>Catégorie</th><th>Description</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $hash = $r['id_type_permis'];
        $tableau .= "<tr><td>$i</td><td>" . h($r['lib_type_permis']) . "</td><td>" . h($r['desc_type_permis']) . "</td><td><div class='btn-group'>" . (hasSubRight('updPermis', 'upd', $rights_config, $permisSpecifics) ? "<button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-driveLicence-upd' data-bs-idtype='$hash' title='Modifier'><i class='fa fa-pencil-alt'></i></button>" : "") . (hasSubRight('delPermis', 'del', $rights_config, $permisSpecifics) ? "<button class='btn btn-danger' title='Supprimer' onclick='delDriveLicence(\"$hash\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}

function getTableauDocs()
{
    global $con;
    global $rights_config;
    global $docsSpecifics;
    $repo = new ConfigRepository($con);
    $rows = $repo->findAllDocuments();
    $tableau = "<table class='table table-striped responsive " . ((isset($_GET['action']) && $_GET['action'] == 'tableexport') ? "no-datatable" : "") . "' id='table-docs'><thead><tr><th>#</th><th>Désignation</th><th>Validité (en mois)</th><th></th></tr></thead><tbody>";
    $i = 1;
    foreach ($rows as $r):
        $hash = $r['id_document'];
        $tableau .= "<tr><td>$i</td><td>" . h($r['nom_document']) . "</td><td>" . h($r['validite_document']) . "</td><td><div class='btn-group'>" . (hasSubRight('updDocs', 'upd', $rights_config, $docsSpecifics) ? "<a class='btn btn-primary' href='?page=configuration&subpage=documentslist&action=upd&id=$hash' title='Modifier'><i class='fa fa-pencil-alt'></i></a>" : "") . (hasSubRight('delDocs', 'del', $rights_config, $docsSpecifics) ? "<button class='btn btn-danger' title='Supprimer' onclick='delDoc(\"$hash\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        $i++;
    endforeach;
    $tableau .= "</tbody></table>";
    return $tableau;
}

function getTableauFolder()
{
    global $con;
    global $rights_config;
    global $foldersSpecifics;
    $configRepo = new ConfigRepository($con);
    $folderRows = $configRepo->findAllFoldersByContext(getContextRegions(), getContextEntities());
    $tableau = "<table style='font-size:0.8125rem' class='table table-striped responsive " . ((isset($_GET['action']) && $_GET['action'] == 'tableexport') ? "no-datatable" : "") . "' id='table-folder' ><thead><tr><th>Chassis</th><th>Véhicule</th><th>Marque</th><th>1ère mise en circulation</th><th>Entité</th><th>Places assises</th><th>Source d'énergie</th>";
    $docs = $configRepo->findAllDocuments();
    foreach ($docs as $doc):
        $tableau .= "<th class='text-center'>" . h($doc['nom_document']) . " (" . h($doc['validite_document']) . " mois)<br>Date Expiration : </th>";
    endforeach;
    $tableau .= "<th>Chauffeur</th><th>Qualification de permis</th><th></th></tr></thead><tbody>";
    foreach ($folderRows as $r):
        $tableau .= "<tr ".($r['id_v']!="" ? "style='font-weight:bold' class='doc-saved'" : "")."><td>" . h($r['chassis_vehicule']) . "</td><td>" . h($r['immatriculation_vehicule']) . "</td><td>" . h($r['nom_marque']) . "</td><td>" . h($r['premiere_utilisation']) . "</td><td>" . h($r['nom_entite']) . "</td><td>" . h($r['nb_place']) . "</td><td>" . h($r['type_carburant']) . "</td>";
        $ref_dossier = "";
        foreach ($docs as $doc):
            $fd = $configRepo->findFolderDocument((int)$r['id_vehicule'], (int)$doc['id_document']);
            if ($fd !== null):
                $tableau .= "<td title='Réf : " . h($fd['ref_document']) . "'>" . h($fd['date_expiration_document']) . "</td>";
                $ref_dossier = $fd['ref_dossier'];
            else:
                $tableau .= "<td></td>";
            endif;
        endforeach;
        $permisRows = $configRepo->findPermisByVehiculeId((int)$r['id_vehicule']);
        $permis = implode(',', array_column($permisRows, 'lib_type_permis'));
        $tableau .= "<td>" . h($r['nom_chauffeur']) . "</td><td>" . h($permis) . "</td><td><div class='btn-group'>" . ($ref_dossier != "" ? "<a class='btn btn-info' title='Historique des dossiers' href='?page=configuration&subpage=dossierHistory&vehicle=" . h($r['id_vehicule']) . "'><i class='fa fa-history'></i></a>" : "") . (hasSubRight('updFolders', 'upd', $rights_config, $foldersSpecifics) && $ref_dossier != "" ? "<a class='btn btn-primary' title='Modifier' href='?page=configuration&subpage=folderdetails&action=upd&id=" . h($ref_dossier) . "'><i class='fa fa-pencil-alt'></i></a>" : "") . (hasSubRight('delFolders', 'del', $rights_config, $foldersSpecifics) && $ref_dossier != "" ? "<button class='btn btn-danger' title='Supprimer' onclick='delFolder(\"".h($ref_dossier)."\")'><i class='fa fa-times'></i></button>" : "") . (hasSubRight('saveFolders', 'save', $rights_config, $foldersSpecifics) && $ref_dossier == "" ? "<a class='btn btn-success' title='Ajouter un dossier' href='?page=configuration&subpage=folderdetails&action=new&idvgch=" . h($r['id_affectation']) . "'><i class='fa fa-plus'></i></a>" : "") . "</div></td></tr>";
    endforeach;
    $tableau .= "</tbody></table><div id='output'></div>";
    return $tableau;
}

/* POST handled by ConfigController — see controllers/router.php */ ?>
<?php if (!isset($_GET['subpage'])): ?>
    <div class="row g-3">
        <div class="col-md-3">
            <div class="lt-card text-center">
                <a href="?page=configuration&subpage=drivelicence" class="text-decoration-none">
                    <i class="fa fa-id-card fa-3x mb-3" style="color:var(--lt-primary)"></i>
                </a>
                <h5 class="lt-card-title">Permis de conduire</h5>
                <p class="lt-page-subtitle">Enregistrez, mettez à jour les différentes catégories de permis de conduire applicables pour les véhicules</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lt-card text-center">
                <a href="?page=configuration&subpage=documentslist" class="text-decoration-none">
                    <i class="fa fa-file-alt fa-3x mb-3" style="color:var(--lt-primary)"></i>
                </a>
                <h5 class="lt-card-title">Documents de véhicules</h5>
                <p class="lt-page-subtitle">Enregistrez, mettez à jour les différents documents utilisables dans un véhicule</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lt-card text-center">
                <a href="?page=configuration&subpage=folderdetails" class="text-decoration-none">
                    <i class="fa fa-folder-open fa-3x mb-3" style="color:var(--lt-primary)"></i>
                </a>
                <h5 class="lt-card-title">Dossier de véhicules</h5>
                <p class="lt-page-subtitle">Enregistrez, mettez à jour les différents documents du dossier d'un véhicule</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lt-card text-center">
                <a href="?page=users" class="text-decoration-none">
                    <i class="fa fa-users-cog fa-3x mb-3" style="color:var(--lt-primary)"></i>
                </a>
                <h5 class="lt-card-title">Utilisateurs</h5>
                <p class="lt-page-subtitle">Gérez les utilisateurs, leurs rôles, permissions et affectations aux régions et entités</p>
            </div>
        </div>
        <div class="col-md-3">
            <div class="lt-card text-center">
                <a href="?page=configuration&subpage=parametres" class="text-decoration-none">
                    <i class="fa fa-cogs fa-3x mb-3" style="color:var(--lt-primary)"></i>
                </a>
                <h5 class="lt-card-title">Paramètres</h5>
                <p class="lt-page-subtitle">Configurez les paramètres généraux de l'application (devise, etc.)</p>
            </div>
        </div>
    </div>
<?php else : ?>
    <?php if ($_GET['subpage'] == 'drivelicence') : ?>
        <?php if (!hasSubRight('viewPermis', 'view', $rights_config, $permisSpecifics)): ?>
            <div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>
        <?php else: ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') include("modalNewDriveLicence.php"); ?>
        <div class="lt-page-title">Catégories de Permis de conduire</div>
        <?php if (hasSubRight('savePermis', 'save', $rights_config, $permisSpecifics)): ?>
        <a href="?page=configuration&subpage=drivelicence&action=new" class="btn btn-primary">Nouvelle catégorie de permis</a>&nbsp;
        <?php endif; ?>
        <a href="?page=configuration&subpage=<?php echo h($_GET['subpage']); ?>&action=tableexport&id=table-drivelicence" class="btn btn-primary">Exporter</a>
        <hr>
        <?php echo getTableauDriveLicence(); ?>
        <script>
            function delDriveLicence(id) {
                if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                    $.ajax({
                        type: 'post',
                        data: 'dl-id=' + id,
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
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') : ?>
            <script>
                setTimeout(() => {
                    openModalDriveLicence()
                }, 5000)
            </script>
        <?php endif; ?>
        <div class="modal fade" id="modal-driveLicence-upd" tabindex="-1" aria-labelledby="modal-driveLicenceLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="modal-driveLicenceLabel">Catégorie de permis <span id='lib-type-id'></span></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="#" id="form-upd-drivelicence">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" required id="lib-type-upd" name="lib-type-upd">
                                <input type="hidden" id="id-type-permis" name="id-type-permis">
                                <label for="lib-type-upd">Catégorie de permis</label>
                            </div>
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="desc-type-upd" name="desc-type-upd"></textarea>
                                <label for="desc-type-upd">Description</label>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        <button type="button" class="btn btn-primary" onclick="updDriveLicence()">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            const modalUpdDL = document.getElementById('modal-driveLicence-upd')
            if (modalUpdDL) {
                modalUpdDL.addEventListener('show.bs.modal', event => {
                    // Button that triggered the modal
                    const id = event.relatedTarget.getAttribute('data-bs-idtype')
                    $.ajax({
                        type: 'post',
                        data: 'c-dl-s=' + id,
                        dataType: 'json'
                    }).done((e) => {
                        if (e.success) {
                            let v = e.data
                            $('#lib-type-upd').val(v.lib_type_permis)
                            $('#desc-type-upd').val(v.desc_type_permis)
                            $('#lib-type-id').html(v.lib_type_permis)
                            $('#id-type-permis').val(v.id_dl)
                        } else {
                            showError(e.error || "Erreur lors du chargement")
                        }
                    }).fail((jqXHR) => {
                        showError(jqXHR.responseJSON?.error || "Erreur lors du chargement")
                    })
                })
            }

            function updDriveLicence() {
                var valid = true
                $('#form-upd-drivelicence *[required]').each((e, el) => {
                    $(el).removeClass('is-invalid')
                    $(el).closest('.ts-wrapper').removeClass('is-invalid')
                    if ($(el).val() == '') {
                        valid = false
                        $(el).addClass('is-invalid')
                        $(el).closest('.ts-wrapper').addClass('is-invalid')
                    }
                })
                if (!valid) {
                    $('#form-upd-drivelicence').notify("Tous les champs en rouge sont obligatoires!", {
                        position: 'top'
                    })
                    return false
                }
                $.ajax({
                    type: 'post',
                    data: $('#form-upd-drivelicence').serialize(),
                    dataType: 'json'
                }).done((e) => {
                    if (e.success) {
                        showSuccess('Enregistrement effectué!')
                        location.reload()
                    } else {
                        $('#modal-driveLicence-upd').notify(e.error || "Erreur lors de l'enregistrement !", {
                            position: 'top'
                        })
                    }
                }).fail((jqXHR) => {
                    $('#modal-driveLicence-upd').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement !", {
                        position: 'top'
                    })
                })
            }
        </script>
    <?php endif; ?>
    <?php elseif ($_GET['subpage'] == 'documentslist') : ?>
        <?php if (!hasSubRight('viewDocs', 'view', $rights_config, $docsSpecifics)): ?>
            <div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>
        <?php else: ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') include("modalNewDocs.php"); ?>
        <div class="lt-page-title">Documents de véhicule</div>
        <?php if (hasSubRight('saveDocs', 'save', $rights_config, $docsSpecifics)): ?>
        <a href="?page=configuration&subpage=documentslist&action=new" class="btn btn-primary">Nouveau document de véhicules</a>&nbsp;
        <?php endif; ?>
        <a href="?page=configuration&subpage=<?php echo h($_GET['subpage']); ?>&action=tableexport&id=table-docs" class="btn btn-primary">Exporter</a>
        <hr>
        <?php echo getTableauDocs(); ?>
        <script>
            function delDoc(id) {
                if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                    $.ajax({
                        type: 'post',
                        data: 'id-doc-del=' + id,
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
        <?php if (isset($_GET['action']) && $_GET['action'] == 'upd' && isset($_GET['id']) && $_GET['id'] != "" && isset($con)): ?>
            <?php $configRepo = new ConfigRepository($con);
            $doc = $configRepo->findDocumentByHash($_GET['id']) ?? [];
            ?>
            <div class="modal fade" id="modal-doc-upd" tabindex="-1" aria-labelledby="modal-docupdLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modal-docupdLabel">Document de véhicule</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="#" id="form-upd-doc">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" required id="nom-doc-upd" name="nom-doc-upd" value="<?php echo $doc['nom_document']; ?>">
                                    <input type="hidden" id="id-doc" name="id-doc" value="<?php echo h($_GET['id']); ?>">
                                    <label for="nom-doc-upd">Désignation</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="valid-doc-upd" name="valid-doc-upd" min="3" value="<?php echo $doc['validite_document']; ?>">
                                    <label for="valid-doc-upd">Validité (en mois)</label>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="button" class="btn btn-primary" onclick="updDoc()">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                setTimeout(() => {
                    $('#modal-doc-upd').modal('show')
                }, 2000)

                function updDoc() {
                    var valid = true
                    $('#form-upd-doc *[required]').each((e, el) => {
                        $(el).removeClass('is-invalid')
                        $(el).closest('.ts-wrapper').removeClass('is-invalid')
                        if ($(el).val() == '') {
                            valid = false
                            $(el).addClass('is-invalid')
                            $(el).closest('.ts-wrapper').addClass('is-invalid')
                        }
                    })
                    if (!valid) {
                        $('#form-upd-doc').notify("Tous les champs en rouge sont obligatoires!", {
                            position: 'top'
                        })
                        return false
                    }
                    $.ajax({
                        type: 'post',
                        data: $('#form-upd-doc').serialize(),
                        dataType: 'json'
                    }).done((e) => {
                        if (e.success) {
                            showSuccess('Enregistrement effectué!')
                            location = '?page=configuration&subpage=documentslist'
                        } else {
                            $('#modal-doc-upd').notify(e.error || "Erreur lors de l'enregistrement !", {
                                position: 'top'
                            })
                        }
                    }).fail((jqXHR) => {
                        $('#modal-doc-upd').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement !", {
                            position: 'top'
                        })
                    })
                }
            </script>
        <?php endif; ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') : ?>
            <script>
                setTimeout(() => {
                    openModalDocs()
                }, 5000)
            </script>
        <?php endif; ?>
    <?php endif; ?>
    <?php elseif ($_GET['subpage'] == 'folderdetails') : ?>
        <?php if (!hasSubRight('viewFolders', 'view', $rights_config, $foldersSpecifics)): ?>
            <div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>
        <?php else: ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') include("modalNewFolder.php"); ?>
        <div class="lt-page-title">Dossier de véhicule</div>
        <?php if (hasSubRight('saveFolders', 'save', $rights_config, $foldersSpecifics)): ?>
        <a href="?page=configuration&subpage=folderdetails&action=new" class="btn btn-primary">Nouveau dossier de véhicule</a>&nbsp;
        <?php endif; ?>
        <a href="?page=configuration&subpage=<?php echo h($_GET['subpage']); ?>&action=tableexport&id=table-folder" class="btn btn-primary">Exporter</a>
        <?php
        $configRepo = new ConfigRepository($con);
        $folderStats = $configRepo->getFolderStats(getContextRegions(), getContextEntities());
        ?>
        <div class="row g-3 my-3">
            <div class="col-md-3">
                <div class="lt-card lt-stat-card">
                    <div class="lt-stat-icon"><i class="fa fa-truck"></i></div>
                    <div class="lt-stat-value"><?= $folderStats['total_vehicules'] ?></div>
                    <div class="lt-stat-label">Véhicules</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="lt-card lt-stat-card lt-stat-success">
                    <div class="lt-stat-icon"><i class="fa fa-folder-open"></i></div>
                    <div class="lt-stat-value"><?= $folderStats['avec_dossier'] ?></div>
                    <div class="lt-stat-label">Avec dossier actif</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="lt-card lt-stat-card lt-stat-warning">
                    <div class="lt-stat-icon"><i class="fa fa-folder"></i></div>
                    <div class="lt-stat-value"><?= $folderStats['sans_dossier'] ?></div>
                    <div class="lt-stat-label">Sans dossier</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="lt-card lt-stat-card">
                    <div class="lt-stat-icon"><i class="fa fa-file-alt"></i></div>
                    <div class="lt-stat-value"><?= $folderStats['total_dossiers'] ?></div>
                    <div class="lt-stat-label">Dossiers actifs</div>
                </div>
            </div>
        </div>
        <?php if (in_array('view', $rights_voyage)) include('statCardsPrestataires.php'); ?>
        <hr>
        <div class="alert alert-light border small py-2 mb-2">
            <strong><i class="fa fa-info-circle"></i> Légende :</strong>
            <span class="ms-2 me-3"><span class="badge" style="background:rgb(0,95,119);color:#fff">Ligne colorée</span> = véhicule avec dossier actif</span>
            <span class="me-3"><span class="badge bg-light text-dark border">Ligne normale</span> = véhicule sans dossier</span>
        </div>
        <?php echo getTableauFolder(); ?>
        <div class="alert alert-light border small py-2 mt-2">
            <strong><i class="fa fa-info-circle"></i> Légende :</strong>
            <span class="ms-2 me-3"><span class="badge" style="background:rgb(0,95,119);color:#fff">Ligne colorée</span> = véhicule avec dossier actif</span>
            <span class="me-3"><span class="badge bg-light text-dark border">Ligne normale</span> = véhicule sans dossier</span>
        </div>
        <script>
            function delFolder(ref) {
                if (confirm("Etes-vous sûr de vouloir supprimer ce dossier ?")) {
                    $.ajax({
                        type: 'post',
                        data: 'ref-folder-del=' + ref,
                        dataType: 'json'
                    }).done((e) => {
                        if (e.success) {
                            showSuccess('Dossier supprimé!')
                            location.reload()
                        } else {
                            showError(e.error || "Echec de l'opération")
                        }
                    }).fail((jqXHR) => {
                        showError(jqXHR.responseJSON?.error || "Echec de l'opération")
                    })
                }
            }
        </script>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') : ?>
            <script>
                setTimeout(() => {
                    openModalFolders()
                }, 5000)
            </script>
        <?php elseif (isset($_GET['action']) && $_GET['action'] == 'upd' && (isset($_GET['id']) && $_GET['id'] != "") && isset($con)): ?>
            <?php
            $configRepo = new ConfigRepository($con);
            $folder = $configRepo->findFolderByRef($_GET['id']); ?>
            <div class="modal fade" id="modal-folder" tabindex="-1" aria-labelledby="modal-folderLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="modal-folderLabel">Dossier de véhicule</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="post" action="#" id="form-new-folder">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" required id="ref-folder" name="ref-folder" readonly value="<?php echo h($_GET['id']); ?>">
                                    <label for="ref-folder">Référence dossier</label>
                                </div>
                                <div class="mb-3">

                                    <label for="vh-folder">Véhicule</label>

                                    <select id="vh-folder" name="vh-folder-upd" required>
                                        <?php
                                        $affRepo = new AffectationRepository($con);
                                        $affRows = $affRepo->findActiveByVehiculeAndContext((int)$folder[0]['id_vehicule'], getContextRegions(), getContextEntities());
                                        foreach ($affRows as $r):
                                            echo "<option value='" . $r['id_affectation'] . "' " . ($folder[0]['id_vehicule'] == $r['id_vehicule'] ? 'selected' : '') . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                                        endforeach;
                                        ?>
                                    </select>

                                </div>
                                <div class="mb-3">
                                    <label for="folder-doc">Document</label>
                                    <div class="input-group">
                                        <select id="folder-doc">
                                            <?php foreach ($configRepo->findAllDocuments() as $r):
                                                echo "<option value='" . $r['id_document'] . "'>" . h($r['nom_document']) . "</option>";
                                            endforeach;
                                            ?>
                                        </select>
                                        <div class="form-floating">
                                            <input type="date" id="date-expiry" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                            <label for="date-expiry">Date expiration</label>
                                        </div>
                                        <div class="form-floating">
                                            <input type="text" id="ref-doc" class="form-control">
                                            <label for="ref-doc">Réf. document</label>
                                        </div>
                                        <button class="btn btn-primary" type="button" title="Ajouter au dossier" onclick="addToFolder($('#folder-doc').val(),$('#date-expiry').val(),$('#ref-doc').val())"><i class="fa fa-plus-circle"></i></button>
                                    </div>
                                </div>
                                <hr>
                                <div id="doc-list">

                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="button" class="btn btn-primary" onclick="updFolder()">Enregistrer</button>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                var docs = []
                setTimeout(() => {
                    $('#modal-folder').modal('show')
                }, 3000)

                function populateFolder(doc, dt, refd, dname, fichier) {
                    docname = dname
                    doc = {
                        id: doc,
                        name: docname,
                        dtexpiry: dt,
                        refdoc: refd,
                        fichier: fichier || null
                    }

                    docs.push(doc)
                    liste = "";
                    for (i = 0; i < docs.length; i++) {
                        var fileBadge = '';
                        if (docs[i].fichier) {
                            fileBadge = "<a href='uploads/dossiers/" + docs[i].fichier + "' target='_blank' class='btn btn-sm btn-success' title='Voir le fichier'><i class='fa fa-file'></i></a>";
                        } else {
                            fileBadge = "<span class='text-muted small'>Aucun fichier</span>";
                        }
                        liste += "<div class='input-group' id='" + docs[i].id + "'><input type='hidden' name='doc-list-id[]' value='" + docs[i].id + "'><input class='form-control' type='text' readonly required id='" + docs[i].id + "' name='doc-list-name[]' value='" + docs[i].name + "'><input type='date' class='form-control' readonly required id='dt-" + docs[i].id + "' name='dt-list-name[]' value='" + docs[i].dtexpiry + "'><input type='text' class='form-control' required id='refd-" + docs[i].id + "' name='refd-list-name[]' value='" + docs[i].refdoc + "'><div class='input-group-text' style='min-width:90px'>" + fileBadge + "</div><button class='btn btn-danger' type='button' title='Retirer du dossier' onclick='remToFolder(\"" + docs[i].id + "\")'><i class='fa fa-times'></i></button></div>"
                    }
                    $('#doc-list').html(liste)
                }

                function addToFolder(doc, dt, refd) {
                    if (dt == "") {
                        $('#date-expiry').notify("Ce champs est obligatoire!", {
                            position: 'top'
                        })
                        return false
                    }
                    if (refd == "") {
                        $('#ref-doc').notify("Ce champs est obligatoire!", {
                            position: 'top'
                        })
                        return false
                    }
                    docname = $('#folder-doc option[value="' + doc + '"]').html()
                    doc = {
                        id: doc,
                        name: docname,
                        dtexpiry: dt,
                        refdoc: refd
                    }
                    if (typeof(docs.find(({
                            id
                        }) => id === doc.id)) != "undefined") {
                        /* docs.splice(docs.findIndex(({
                            id
                        }) => id === doc.id), 1) */
                        $('#folder-doc').notify("Ce document a déjà été ajouté!", {
                            position: 'top'
                        })
                    } else {
                        docs.push(doc)
                    }
                    liste = "";
                    for (i = 0; i < docs.length; i++) {
                        liste += "<div class='input-group' id='" + docs[i].id + "'><input type='hidden' name='doc-list-id[]' value='" + docs[i].id + "'><input class='form-control' type='text' readonly required id='" + docs[i].id + "' name='doc-list-name[]' value='" + docs[i].name + "'><input type='date' class='form-control' readonly required id='dt-" + docs[i].id + "' name='dt-list-name[]' value='" + docs[i].dtexpiry + "'><input type='text' class='form-control' readonly required id='refd-" + docs[i].id + "' name='refd-list-name[]' value='" + docs[i].refdoc + "'><div class='input-group-text' style='min-width:90px'><span class='text-muted small'>Aucun fichier</span></div><button class='btn btn-danger' type='button' title='Retirer du dossier' onclick='remToFolder(\"" + docs[i].id + "\")'><i class='fa fa-times'></i></button></div>"
                    }
                    $('#doc-list').html(liste)
                    $('#ref-doc').val('')
                }

                function updFolder() {
                    var valid = true
                    $('#form-new-folder *[required]').each((e, el) => {
                        $(el).removeClass('is-invalid')
                        $(el).closest('.ts-wrapper').removeClass('is-invalid')
                        if ($(el).val() == '') {
                            valid = false
                            $(el).addClass('is-invalid')
                            $(el).closest('.ts-wrapper').addClass('is-invalid')
                        }
                    })
                    if (!valid) {
                        $('#form-new-folder').notify("Tous les champs en rouge sont obligatoires!", {
                            position: 'top'
                        })
                        return false
                    }
                    if (docs.length == 0) {
                        $('#form-new-folder').notify("Vous devez ajouter des documents pour pouvoir créer un dossier", {
                            position: 'top'
                        })
                        return false
                    }
                    $.ajax({
                        type: 'post',
                        data: $('#form-new-folder').serialize(),
                        dataType: 'json'
                    }).done((e) => {
                        if (e.success) {
                            showSuccess('Enregistrement effectué!')
                            location = "?page=configuration&subpage=folderdetails"
                        } else {
                            $('#modal-folder').notify(e.error || "Erreur lors de l'enregistrement !", {
                                position: 'top'
                            })
                        }
                    }).fail((jqXHR) => {
                        $('#modal-folder').notify(jqXHR.responseJSON?.error || "Erreur lors de l'enregistrement !", {
                            position: 'top'
                        })
                    })
                }

                function remToFolder(id) {
                    if (confirm("Etes-vous sûr de vouloir continuer ?")) {
                        docs.splice(docs.findIndex(({
                            id
                        }) => id === docs.id), 1)
                        $('#' + id).remove()
                    }
                }
                <?php for ($i = 0; $i < count($folder); $i++) : ?>
                    populateFolder('<?php echo $folder[$i]['iddoc']; ?>', '<?php echo $folder[$i]['date_expiration_document']; ?>', '<?php echo $folder[$i]['ref_document']; ?>', '<?php echo $folder[$i]['nom_document']; ?>', <?php echo !empty($folder[$i]['fichier']) ? "'" . h($folder[$i]['fichier']) . "'" : 'null'; ?>)
                <?php endfor; ?>
            </script>

        <?php endif; ?>
    <?php endif; ?>
    <?php elseif ($_GET['subpage'] == 'dossierHistory') : ?>
        <?php if (!hasSubRight('viewFolders', 'view', $rights_config, $foldersSpecifics)): ?>
            <div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>
        <?php else:
            $vehicleId = isset($_GET['vehicle']) ? (int)$_GET['vehicle'] : 0;
            if (!$vehicleId): ?>
                <div class='alert alert-danger'>Véhicule non spécifié</div>
            <?php else:
                $configRepo = new ConfigRepository($con);
                $vehicule = $configRepo->findVehiculeInfo($vehicleId);
                if (!$vehicule): ?>
                    <div class='alert alert-danger'>Véhicule introuvable</div>
                <?php else:
                    $allDossiers = $configRepo->findAllDossiersByVehicule($vehicleId);
                    // Group documents by ref_dossier
                    $grouped = [];
                    $dossierMeta = [];
                    foreach ($allDossiers as $d) {
                        $ref = $d['ref_dossier'];
                        $grouped[$ref][] = $d;
                        if (!isset($dossierMeta[$ref])) {
                            $dossierMeta[$ref] = [
                                'is_active' => (bool)$d['is_active'],
                                'id_dossier_vehicule' => $d['id_dossier_vehicule'],
                            ];
                        }
                    }
                    ?>
                    <div class="lt-page-title">
                        <a href="?page=configuration&subpage=folderdetails" class="btn btn-secondary btn-sm me-2">&larr; Retour</a>
                        Historique des dossiers — <?= h($vehicule['immatriculation_vehicule']) ?>
                    </div>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3"><strong>Véhicule :</strong> <?= h($vehicule['immatriculation_vehicule']) ?></div>
                                <div class="col-md-3"><strong>Marque :</strong> <?= h($vehicule['nom_marque']) ?></div>
                                <div class="col-md-3"><strong>Chauffeur :</strong> <?= h($vehicule['nom_chauffeur']) ?></div>
                                <div class="col-md-3"><strong>Entité :</strong> <?= h($vehicule['nom_entite']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php if (empty($grouped)): ?>
                        <div class='alert alert-info'>Aucun dossier trouvé pour ce véhicule.</div>
                    <?php else: ?>
                        <div class="accordion" id="dossierHistoryAccordion">
                        <?php $first = true;
                        foreach ($grouped as $ref => $docs):
                            $meta = $dossierMeta[$ref];
                            $badge = $meta['is_active']
                                ? '<span class="badge bg-success">Actif</span>'
                                : '<span class="badge bg-secondary">Archivé</span>';
                            $collapseId = 'collapse-' . md5($ref);
                            $headingId = 'heading-' . md5($ref);
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="<?= $headingId ?>">
                                    <button class="accordion-button <?= $first ? '' : 'collapsed' ?>" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>">
                                        <?= h($ref) ?> <?= $badge ?>
                                    </button>
                                </h2>
                                <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $first ? 'show' : '' ?>"
                                     data-bs-parent="#dossierHistoryAccordion">
                                    <div class="accordion-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Document</th>
                                                        <th>Date expiration</th>
                                                        <th>Réf. document</th>
                                                        <th>Fichier</th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($docs as $doc): ?>
                                                    <tr>
                                                        <td><?= h($doc['nom_document']) ?></td>
                                                        <td><?= h($doc['date_expiration_document']) ?></td>
                                                        <td><?= h($doc['ref_document']) ?></td>
                                                        <td>
                                                            <?php if (!empty($doc['fichier'])): ?>
                                                                <a href="uploads/dossiers/<?= h($doc['fichier']) ?>" target="_blank" class="btn btn-sm btn-success" title="Télécharger">
                                                                    <i class="fa fa-download"></i> Voir
                                                                </a>
                                                                <?php if (hasSubRight('updFolders', 'upd', $rights_config, $foldersSpecifics)): ?>
                                                                <button class="btn btn-sm btn-danger" title="Supprimer le fichier"
                                                                        onclick="deleteFolderFile('<?= h($ref) ?>', <?= (int)$doc['id_document'] ?>, this)">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <?php if (hasSubRight('updFolders', 'upd', $rights_config, $foldersSpecifics)): ?>
                                                                <button class="btn btn-sm btn-outline-primary" title="Ajouter un fichier"
                                                                        onclick="document.getElementById('file-input-<?= (int)$doc['id_dossier_vehicule_document'] ?>').click()">
                                                                    <i class="fa fa-upload"></i> Ajouter
                                                                </button>
                                                                <input type="file" id="file-input-<?= (int)$doc['id_dossier_vehicule_document'] ?>"
                                                                       class="d-none" accept=".pdf,.png,.jpg,.jpeg,.gif"
                                                                       onchange="uploadFolderFile(<?= (int)$doc['id_document'] ?>, '<?= h($ref) ?>', this)">
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (hasSubRight('updFolders', 'upd', $rights_config, $foldersSpecifics)): ?>
                                                                <a class="btn btn-sm btn-primary" title="Modifier le dossier"
                                                                   href="?page=configuration&subpage=folderdetails&action=upd&id=<?= h($ref) ?>">
                                                                    <i class="fa fa-pencil-alt"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php $first = false;
                        endforeach; ?>
                        </div>
                    <?php endif;
                endif;
            endif;
        endif; ?>
        <script>
        function uploadFolderFile(idDocument, refDossier, fileInput) {
            if (!fileInput.files || !fileInput.files[0]) return;
            var formData = new FormData();
            formData.append('vh-folder-file-upload', '1');
            formData.append('id-document', idDocument);
            formData.append('ref-dossier', refDossier);
            formData.append('fichier', fileInput.files[0]);

            var $btn = $(fileInput).siblings('button');
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                type: 'post',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(e) {
                if (e.success) {
                    showSuccess('Fichier ajouté !');
                    location.reload();
                } else {
                    showError(e.error || "Erreur lors de l'upload");
                    $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Ajouter');
                }
            }).fail(function(jqXHR) {
                showError(jqXHR.responseJSON?.error || "Erreur lors de l'upload");
                $btn.prop('disabled', false).html('<i class="fa fa-upload"></i> Ajouter');
            });
        }

        function deleteFolderFile(refDossier, idDocument, btn) {
            if (!confirm("Supprimer ce fichier ?")) return;
            $(btn).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

            $.ajax({
                type: 'post',
                data: {
                    'vh-folder-file-delete': '1',
                    'ref-dossier': refDossier,
                    'id-document': idDocument
                },
                dataType: 'json'
            }).done(function(e) {
                if (e.success) {
                    showSuccess('Fichier supprimé !');
                    location.reload();
                } else {
                    showError(e.error || "Erreur");
                    $(btn).prop('disabled', false).html('<i class="fa fa-trash"></i>');
                }
            }).fail(function(jqXHR) {
                showError(jqXHR.responseJSON?.error || "Erreur");
                $(btn).prop('disabled', false).html('<i class="fa fa-trash"></i>');
            });
        }
        </script>
        <?php elseif ($_GET['subpage'] == 'parametres') : ?>
            <?php if (!in_array('view', $rights_config)): ?>
                <div class='alert alert-warning'>Vous n'avez pas les droits d'afficher cette page!</div>
            <?php else: ?>
            <div class="lt-page-title">Paramètres généraux</div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <div class="lt-card">
                        <div class="lt-card-header"><h2 class="lt-card-title">Devise</h2></div>
                        <div class="p-3">
                            <form id="form-devise" onsubmit="return false;">
                                <div class="mb-3">
                                    <label for="devise-input" class="form-label">Symbole ou code de la devise</label>
                                    <input type="text" id="devise-input" class="form-control" value="<?php echo h(devise()); ?>" maxlength="10" style="max-width:200px">
                                    <div class="form-text">Ex: F, CFA, €, $, DH</div>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="saveDevise()">Enregistrer</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php if (in_array('backup', $rights_config) || ($_SESSION['usr-con']['is-superadmin'] ?? false)): ?>
                <div class="col-md-6">
                    <div class="lt-card">
                        <div class="lt-card-header"><h2 class="lt-card-title">Sauvegarde base de données</h2></div>
                        <div class="p-3">
                            <p class="text-muted">Téléchargez une sauvegarde complète de la base de données au format SQL.</p>
                            <button type="button" class="btn btn-primary" id="btn-backup-db" onclick="backupDatabase()">
                                <span id="btn-backup-text">Lancer la sauvegarde</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <script>
            function saveDevise() {
                var v = $('#devise-input').val().trim();
                if (!v) { alert('Veuillez saisir une devise'); return; }
                $.ajax({type:'post', contentType:'application/json', data:JSON.stringify({'update-devise':v, csrf_token:window.CSRF_TOKEN}), dataType:'json'})
                .done(function(r) {
                    if (r.success) { alert('Devise enregistrée.'); } else { alert('Erreur.'); }
                })
                .fail(function() { alert('Erreur réseau.'); });
            }

            function backupDatabase() {
                var $btn = $('#btn-backup-db');
                $btn.prop('disabled', true);
                $('#btn-backup-text').text('Sauvegarde en cours...');

                fetch('index.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({'backup-db': '1', csrf_token: window.CSRF_TOKEN})
                })
                .then(function(resp) {
                    if (!resp.ok) throw new Error('HTTP ' + resp.status);
                    var disposition = resp.headers.get('Content-Disposition') || '';
                    var m = disposition.match(/filename="?([^"]+)"?/);
                    var filename = m ? m[1] : 'logitrack_backup.sql';
                    return resp.blob().then(function(blob) { return {blob: blob, filename: filename}; });
                })
                .then(function(r) {
                    var url = URL.createObjectURL(r.blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = r.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                })
                .catch(function(err) {
                    alert('Erreur lors de la sauvegarde : ' + err.message);
                })
                .finally(function() {
                    $btn.prop('disabled', false);
                    $('#btn-backup-text').text('Lancer la sauvegarde');
                });
            }
            </script>
            <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'tableexport' && isset($_GET['id']) && $_GET['id'] != '') : ?>
        <!-- Inclure SheetJS -->

        <!-- <button onclick="exportTableToExcel('tableId')">Exporter en Excel</button>
            <table id="tableId">
                
            </table> -->
        <script>
            exportTableToExcel(<?php echo j($_GET['id']); ?>)
            setTimeout(() => {
                location = "?page=configuration&subpage=" + <?php echo j($_GET['subpage']); ?>
            }, 2000)
        </script>
    <?php endif; ?>
<?php endif; ?>
<style>
    tr.doc-saved td{
        color:#fff;
        background-color:rgb(0, 95, 119) !important
    }
</style>