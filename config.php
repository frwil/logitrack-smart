<script>
    $('div.container-fluid .col-2').remove()
    $('div.container-fluid .col-10').removeClass('col-10').addClass('col-12')
</script>
<?php function getTableauDriveLicence()
{
    global $con;
    global $rights_config;
    $q = mysqli_query($con, "select * from type_permis_vehicule");
    $tableau = "<table class='table table-striped responsive " . ((isset($_GET['action']) && $_GET['action'] == 'tableexport') ? "no-datatable" : "") . "' id='table-drivelicence'><thead><tr><th>#</th><th>Catégorie</th><th>Description</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r[1]}</td><td>{$r[2]}</td><td><div class='btn-group'>" . (in_array('upd', $rights_config) ? "<button class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#modal-driveLicence-upd' data-bs-idtype='" . sha1($r[0] . $r[1]) . "' title='Modifier'><i class='fa fa-pencil-alt'></i></button>" : "") . (in_array('del', $rights_config) ? "<button class='btn btn-danger' title='Supprimer' onclick='delDriveLicence(\"" . sha1($r[0] . $r[1]) . "\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}

function getTableauDocs()
{
    global $con;
    global $rights_config;
    $q = mysqli_query($con, "select * from document_vehicule");
    $tableau = "<table class='table table-striped responsive " . ((isset($_GET['action']) && $_GET['action'] == 'tableexport') ? "no-datatable" : "") . "' id='table-docs'><thead><tr><th>#</th><th>Désignation</th><th>Validité (en mois)</th><th></th></tr></thead><tbody>";
    $i = 1;
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr><td>$i</td><td>{$r[1]}</td><td>{$r[2]}</td><td><div class='btn-group'>" . (in_array('upd', $rights_config) ? "<a class='btn btn-primary' href='?page=configuration&subpage=documentslist&action=upd&id=" . sha1($r[0] . $r[1]) . "' title='Modifier'><i class='fa fa-pencil-alt'></i></a>" : "") . (in_array('del', $rights_config) ? "<button class='btn btn-danger' title='Supprimer' onclick='delDoc(\"" . sha1($r[0] . $r[1]) . "\")'><i class='fa fa-times'></i></button>" : "") . "</div></td></tr>";
        $i++;
    endwhile;
    $tableau .= "</tbody></table>";
    return $tableau;
}

function getTableauFolder()
{
    global $con;
    global $rights_config;
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    $q = mysqli_query($con, "select *,(select id_dossier_vehicule_document from dossier_vehicule_document where dossier_vehicule_document.id_vehicule=vehicule.id_vehicule limit 1) as id_v from vehicule left join affectation_vehicule on affectation_vehicule.id_vehicule=vehicule.id_vehicule left join chauffeur c on c.id_chauffeur=affectation_vehicule.id_chauffeur left join marque_vehicule on marque_vehicule.id_marque=vehicule.id_marque left join entite on entite.id_entite=vehicule.id_entite where is_ferme=0 and affectation_vehicule.id_region". ($_SESSION['usr-con']['region-sel'] != '' ? "=({$_SESSION['usr-con']['region-sel']})" : "=''")." order by immatriculation_vehicule");
    $tableau = "<table style='font-size:0.8rem' class='table table-striped responsive " . ((isset($_GET['action']) && $_GET['action'] == 'tableexport') ? "no-datatable" : "") . "' id='table-folder' ><thead><tr><th>Chassis</th><th>Véhicule</th><th>Marque</th><th>1ère mise en circulation</th><th>Entité</th><th>Places assises</th><th>Source d'énergie</th>";
    $q1 = mysqli_query($con, "select * from document_vehicule");
    $docs = array();
    while ($r1 = mysqli_fetch_array($q1)):
        $tableau .= "<th class='text-center'>{$r1['nom_document']} ({$r1['validite_document']} mois)<br>Date Expiration : </th>";
        array_push($docs, $r1);
    endwhile;
    $tableau .= "<th>Chauffeur</th><th>Qualification de permis</th><th></th></tr></thead><tbody>";
    $imm = "";
    while ($r = mysqli_fetch_array($q)):
        $tableau .= "<tr ".($r['id_v']!="" ? "style='font-weight:bold' class='doc-saved'" : "")."><td>{$r['chassis_vehicule']}</td><td>{$r['immatriculation_vehicule']}</td><td>{$r['nom_marque']}</td><td>{$r['premiere_utilisation']}</td><td>{$r['nom_entite']}</td><td>{$r['nb_place']}</td><td>{$r['type_carburant']}</td>";
        $ref_dossier = "";
        for ($i = 0; $i < count($docs); $i++):
            $q1 = mysqli_query($con, "select * from dossier_vehicule_document left join dossier_vehicule on dossier_vehicule.id_dossier_vehicule=dossier_vehicule_document.id_dossier_vehicule where id_vehicule={$r[0]} and id_document={$docs[$i]['id_document']} and is_active=1 limit 1");
            while ($r1 = mysqli_fetch_array($q1)):
                $tableau .= "<td title='Réf : {$r1['ref_document']}'>{$r1['date_expiration_document']}</td>";
                $ref_dossier = $r1['ref_dossier'];
            endwhile;
            if (mysqli_num_rows($q1) == 0) $tableau .= "<td></td>";
        endfor;
        $q1=mysqli_query($con,"select * from type_permis_vehicule inner join qualification_permis_vehicule on qualification_permis_vehicule.id_type_permis=type_permis_vehicule.id_type_permis and id_vehicule={$r[0]}");
        $permis="";
        $i=0;
        while($r1=mysqli_fetch_array($q1)):
            if($i>0) $permis.=",";
            $permis.=$r1['lib_type_permis'];
            $i++;
        endwhile;
        $tableau .= "<td>{$r['nom_chauffeur']}</td><td>$permis</td><td><div class='btn-group'>" . (in_array('upd', $rights_config) && $ref_dossier != "" ? "<a class='btn btn-primary' title='Modifier' href='?page=configuration&subpage=folderdetails&action=upd&id=$ref_dossier'><i class='fa fa-pencil-alt'></i></a>" : "") . "</div></td></tr>";
    endwhile;
    $tableau .= "</tbody></table><div id='output'></div>";
    return $tableau;
}

if (isset($_POST['dl-id'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "delete from `type_permis_vehicule` where sha1(concat(id_type_permis,lib_type_permis))='{$_POST['dl-id']}'");
        mysqli_commit($con);
        die("DELDL%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("DELDL%%%%%%0");
    }
endif;
if (isset($_POST['lib-type-upd'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "update type_permis_vehicule set lib_type_permis='{$_POST['lib-type-upd']}', desc_type_permis=" . ($_POST['desc-type-upd'] == "" ? "NULL" : "'{$_POST['desc-type-upd']}'") . " where sha1(concat(id_type_permis,lib_type_permis))='{$_POST['id-type-permis']}'");
        mysqli_commit($con);
        die("UPDDL%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UPDDL%%%%%%0");
    }
endif;
if (isset($_POST['c-dl-s'])):
    $q = mysqli_query($con, "select *,(sha1(concat(id_type_permis,lib_type_permis))) as id_dl from type_permis_vehicule where sha1(concat(id_type_permis,lib_type_permis))='{$_POST['c-dl-s']}'");
    $liste = array();
    while ($r = mysqli_fetch_array($q)):
        $liste = $r;
    endwhile;
    unset($liste[0]);
    unset($liste['id_type_permis']);
    die("LISTEDL%%%%%%" . json_encode($liste));
endif;
if (isset($_POST['nom-doc-upd'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $keys = array_keys($_POST);
        for ($i = 0; $i < count($keys); $i++) $_POST[$keys[$i]] = mysqli_real_escape_string($con, trim($_POST[$keys[$i]]));
        $q = mysqli_query($con, "update document_vehicule set nom_document='{$_POST['nom-doc-upd']}',validite_document='{$_POST['valid-doc-upd']}' where sha1(concat(id_document,nom_document))='{$_POST['id-doc']}'");
        mysqli_commit($con);
        die("UPDDOC%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UPDDOC%%%%%%0");
    }
endif;
if (isset($_POST['vh-folder-upd'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = mysqli_query($con, "delete from `dossier_vehicule_document` where id_dossier_vehicule=(select id_dossier_vehicule from dossier_vehicule where ref_dossier= '{$_POST['ref-folder']}')");
        for ($i = 0; $i < count($_POST['doc-list-name']); $i++):
            $q = mysqli_query($con, "INSERT INTO `dossier_vehicule_document` (`id_dossier_vehicule_document`, `id_document`, `date_expiration_document`, `id_vehicule`, `id_dossier_vehicule`,ref_document) VALUES (NULL, (select id_document from document_vehicule where sha1(concat(id_document,nom_document))='{$_POST['doc-list-id'][$i]}'), '{$_POST['dt-list-name'][$i]}', (select id_vehicule from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))='{$_POST['vh-folder-upd']}'), (select id_dossier_vehicule from dossier_vehicule where ref_dossier='{$_POST['ref-folder']}'),'{$_POST['refd-list-name'][$i]}')");
        endfor;
        mysqli_commit($con);
        die("UPDFLD%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        die("UPDFLD%%%%%%0");
    }
endif;
?>
<?php if (!isset($_GET['subpage'])): ?>
    <div class="row">
        <div class="card col-3 m-3" style="width: 18rem;">
            <div class="text-center border-bottom p-5"><a href="?page=configuration&subpage=drivelicence"><i class="fa fa-cogs fa-2xl" style="font-size:5rem"></i></a></div>
            <div class="card-body">
                <h5 class="card-title">Permis de conduire</h5>
                <p class="card-text">Enregistrez, mettez à jour les différentes catégories de permis de conduire applicables pour les véhicules</p>
            </div>
        </div>
        <div class="card col-3 m-3" style="width: 18rem;">
            <div class="text-center border-bottom p-5"><a href="?page=configuration&subpage=documentslist"><i class="fa fa-file fa-2xl" style="font-size:5rem"></i></a></div>
            <div class="card-body">
                <h5 class="card-title">Documents de véhicles</h5>
                <p class="card-text">Enregistrez, mettez à jour les différents documents utilisables dans un véhicule</p>
            </div>
        </div>
        <div class="card col-3 m-3" style="width: 18rem;">
            <div class="text-center border-bottom p-5"><a href="?page=configuration&subpage=folderdetails"><i class="fa fa-folder fa-2xl" style="font-size:5rem"></i></a></div>
            <div class="card-body">
                <h5 class="card-title">Dossier de véhicles</h5>
                <p class="card-text">Enregistrez, mettez à jour les différents documents du dossier d'un véhicule</p>
            </div>
        </div>
    </div>
<?php else : ?>
    <?php if ($_GET['subpage'] == 'drivelicence') : ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') include("modalNewDriveLicence.php"); ?>
        <div class="alert alert-primary">Catégories de Permis de conduire</div>
        <a href="?page=configuration&subpage=drivelicence&action=new" class="btn btn-primary">Nouvelle catégorie de permis</a>&nbsp;<a href="?page=configuration&subpage=<?php echo $_GET['subpage']; ?>&action=tableexport&id=table-drivelicence" class="btn btn-primary">Exporter</a>
        <hr>
        <?php echo getTableauDriveLicence(); ?>
        <script>
            function delDriveLicence(id) {
                if (confirm("Etes-vous sûr de vouloir supprimer ?")) {
                    $.ajax({
                        type: 'post',
                        data: 'dl-id=' + id
                    }).done((e) => {
                        let v = e.split('DELDL%%%%%%')[1]
                        if (v == '1') {
                            alert('Opération effectuée!')
                            location.reload()
                        }
                        $.notify("Echec de l'opération!")
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
                        data: 'c-dl-s=' + id
                    }).done((e) => {
                        let v = JSON.parse(e.split('LISTEDL%%%%%%')[1])
                        $('#lib-type-upd').val(v.lib_type_permis)
                        $('#desc-type-upd').val(v.desc_type_permis)
                        $('#lib-type-id').html(v.lib_type_permis)
                        $('#id-type-permis').val(v.id_dl)
                    })
                })
            }

            function updDriveLicence() {
                var valid = true
                $('#form-upd-drivelicence *[required]').each((e, el) => {
                    $(el).removeClass('is-invalid')
                    if ($(el).val() == '') {
                        valid = false
                        $(el).addClass('is-invalid')
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
                    data: $('#form-upd-drivelicence').serialize()
                }).done((e) => {
                    let v = e.split('UPDDL%%%%%%')[1]
                    if (v == '1') {
                        alert('Enregistrement effectué!')
                        location.reload()
                    }
                    $('#modal-driveLicence-upd').notify("Erreur lors de l'enregistrement !", {
                        position: 'top'
                    })
                })
            }
        </script>
    <?php elseif ($_GET['subpage'] == 'documentslist') : ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') include("modalNewDocs.php"); ?>
        <div class="alert alert-primary">Documents de véhicule</div>
        <a href="?page=configuration&subpage=documentslist&action=new" class="btn btn-primary">Nouveau document de véhicules</a>&nbsp;<a href="?page=configuration&subpage=<?php echo $_GET['subpage']; ?>&action=tableexport&id=table-docs" class="btn btn-primary">Exporter</a>
        <hr>
        <?php echo getTableauDocs(); ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'upd' && isset($_GET['id']) && $_GET['id'] != ""): ?>
            <?php $q = mysqli_query($con, "select * from document_vehicule where sha1(concat(id_document,nom_document))='{$_GET['id']}'");
            $doc = array();
            while ($r = mysqli_fetch_array($q)):
                $doc = $r;
            endwhile;
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
                                    <input type="hidden" id="id-doc" name="id-doc" value="<?php echo $_GET['id']; ?>">
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
                        if ($(el).val() == '') {
                            valid = false
                            $(el).addClass('is-invalid')
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
                        data: $('#form-upd-doc').serialize()
                    }).done((e) => {
                        let v = e.split('UPDDOC%%%%%%')[1]
                        if (v == '1') {
                            alert('Enregistrement effectué!')
                            location = '?page=configuration&subpage=documentslist'
                        }
                        $('#modal-doc-upd').notify("Erreur lors de l'enregistrement !", {
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
    <?php elseif ($_GET['subpage'] == 'folderdetails') : ?>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') include("modalNewFolder.php"); ?>
        <div class="alert alert-primary">Dossier de véhicule</div>
        <a href="?page=configuration&subpage=folderdetails&action=new" class="btn btn-primary">Nouveau dossier de véhicule</a>&nbsp;<a href="?page=configuration&subpage=<?php echo $_GET['subpage']; ?>&action=tableexport&id=table-folder" class="btn btn-primary">Exporter</a>
        <hr>
        <?php echo getTableauFolder(); ?>
        <script>

        </script>
        <?php if (isset($_GET['action']) && $_GET['action'] == 'new') : ?>
            <script>
                setTimeout(() => {
                    openModalFolders()
                }, 5000)
            </script>
        <?php elseif (isset($_GET['action']) && $_GET['action'] == 'upd' && (isset($_GET['id']) && $_GET['id'] != "")): ?>
            <?php
            $q = mysqli_query($con, "select *,(select sha1(concat(id_document,nom_document)) from document_vehicule dv where dv.id_document=dossier_vehicule_document.id_document) as iddoc from dossier_vehicule_document left join dossier_vehicule on dossier_vehicule.id_dossier_vehicule=dossier_vehicule_document.id_dossier_vehicule left join document_vehicule on document_vehicule.id_document=dossier_vehicule_document.id_document where ref_dossier='{$_GET['id']}'");
            $folder = array();
            while ($r = mysqli_fetch_array($q)) array_push($folder, $r); ?>
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
                                    <input type="text" class="form-control" required id="ref-folder" name="ref-folder" readonly value="<?php echo $_GET['id']; ?>">
                                    <label for="ref-folder">Référence dossier</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <select id="vh-folder" name="vh-folder-upd" class="form-select">
                                        <?php 
                                        $q = mysqli_query($con, "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0 and vehicule.id_vehicule={$folder[0]['id_vehicule']} and affectation_vehicule.id_region " . ($_SESSION['usr-con']['region-sel'] != '' ? "=({$_SESSION['usr-con']['region-sel']})" : "=''"));
                                        while ($r = mysqli_fetch_array($q)):
                                            echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . ($folder[0]['id_vehicule'] == $r['id_vehicule'] ? 'selected' : '') . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                                        endwhile;
                                        ?>
                                    </select>
                                    <label for="vh-folder">Véhicule</label>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="form-floating">
                                        <select class="form-select" id="folder-doc">
                                            <?php $q = mysqli_query($con, "select * from document_vehicule");
                                            while ($r = mysqli_fetch_array($q)):
                                                echo "<option value='" . sha1($r[0] . $r[1]) . "'>{$r[1]}</option>";
                                            endwhile;
                                            ?>
                                        </select>
                                        <label for="folder-doc">Document</label>
                                    </div>
                                    <div class="form-floating">
                                        <input type="date" id="date-expiry" class="form-control" value="<?php date('Y-m-d'); ?>">
                                        <label for="date-expiry">Date expiration</label>
                                    </div>
                                    <div class="form-floating">
                                        <input type="text" id="ref-doc" class="form-control">
                                        <label for="ref-doc">Réf. document</label>
                                    </div>
                                    <button class="btn btn-primary" type="button" title="Ajouter au dossier" onclick="addToFolder($('#folder-doc').val(),$('#date-expiry').val(),$('#ref-doc').val())"><i class="fa fa-plus-circle"></i></button>
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

                function populateFolder(doc, dt, refd, dname) {
                    docname = dname
                    doc = {
                        id: doc,
                        name: docname,
                        dtexpiry: dt,
                        refdoc: refd
                    }

                    docs.push(doc)
                    liste = "";
                    for (i = 0; i < docs.length; i++) {
                        liste += "<div class='input-group' id='" + docs[i].id + "'><input type='hidden' name='doc-list-id[]' value='" + docs[i].id + "'><input class='form-control' type='text' readonly required id='" + docs[i].id + "' name='doc-list-name[]' value='" + docs[i].name + "'><input type='date' class='form-control' readonly required id='dt-" + docs[i].id + "' name='dt-list-name[]' value='" + docs[i].dtexpiry + "'><input type='text' class='form-control' required id='refd-" + docs[i].id + "' name='refd-list-name[]' value='" + docs[i].refdoc + "'><button class='btn btn-danger' type='button' title='Retirer du dossier' onclick='remToFolder(\"" + docs[i].id + "\")'><i class='fa fa-times'></i></button></div>"
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
                        liste += "<div class='input-group' id='" + docs[i].id + "'><input type='hidden' name='doc-list-id[]' value='" + docs[i].id + "'><input class='form-control' type='text' readonly required id='" + docs[i].id + "' name='doc-list-name[]' value='" + docs[i].name + "'><input type='date' class='form-control' readonly required id='dt-" + docs[i].id + "' name='dt-list-name[]' value='" + docs[i].dtexpiry + "'><input type='text' class='form-control' readonly required id='refd-" + docs[i].id + "' name='refd-list-name[]' value='" + docs[i].refdoc + "'><button class='btn btn-danger' type='button' title='Retirer du dossier' onclick='remToFolder(\"" + docs[i].id + "\")'><i class='fa fa-times'></i></button></div>"
                    }
                    $('#doc-list').html(liste)
                    $('#ref-doc').val('')
                }

                function updFolder() {
                    var valid = true
                    $('#form-new-folder *[required]').each((e, el) => {
                        $(el).removeClass('is-invalid')
                        if ($(el).val() == '') {
                            valid = false
                            $(el).addClass('is-invalid')
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
                        data: $('#form-new-folder').serialize()
                    }).done((e) => {
                        let v = e.split('UPDFLD%%%%%%')[1]
                        if (v == '1') {
                            alert('Enregistrement effectué!')
                            location = "?page=configuration&subpage=folderdetails"
                        }
                        $('#modal-folder').notify("Erreur lors de l'enregistrement !", {
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
                    populateFolder('<?php echo $folder[$i]['iddoc']; ?>', '<?php echo $folder[$i]['date_expiration_document']; ?>', '<?php echo $folder[$i]['ref_document']; ?>', '<?php echo $folder[$i]['nom_document']; ?>')
                <?php endfor; ?>
            </script>

        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['action']) && $_GET['action'] == 'tableexport' && isset($_GET['id']) && $_GET['id'] != '') : ?>
        <!-- Inclure SheetJS -->

        <!-- <button onclick="exportTableToExcel('tableId')">Exporter en Excel</button>
            <table id="tableId">
                
            </table> -->
        <script>
            exportTableToExcel('<?php echo $_GET['id']; ?>')
            setTimeout(() => {
                location = "?page=configuration&subpage=<?php echo $_GET['subpage']; ?>"
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