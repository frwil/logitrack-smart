<?php if (isset($_POST['vh-folder'])):
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    mysqli_begin_transaction($con);
    try {
        $q = db_exec($con, "INSERT INTO `dossier_vehicule` (`id_dossier_vehicule`, `ref_dossier`) VALUES (NULL, ?)", [$_POST['ref-folder']]);
        for($i=0;$i<count($_POST['doc-list-name']);$i++):
            $q=db_exec($con,"update dossier_vehicule_document set is_active=0 where id_document=(select id_document from document_vehicule where sha1(concat(id_document,nom_document))=?) and id_vehicule=(select id_vehicule from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?)", [$_POST['doc-list-id'][$i], $_POST['vh-folder']]);
            $q=db_exec($con,"INSERT INTO `dossier_vehicule_document` (`id_dossier_vehicule_document`, `id_document`, `date_expiration_document`, `id_vehicule`, `id_dossier_vehicule`,ref_document,is_active) VALUES (NULL, (select id_document from document_vehicule where sha1(concat(id_document,nom_document))=?), ?, (select id_vehicule from affectation_vehicule where sha1(concat(id_affectation,id_vehicule))=?), (select id_dossier_vehicule from dossier_vehicule where ref_dossier=?),?,1)", [$_POST['doc-list-id'][$i], $_POST['dt-list-name'][$i], $_POST['vh-folder'], $_POST['ref-folder'], $_POST['refd-list-name'][$i]]);
        endfor;
        mysqli_commit($con);
        die("NEWFLD%%%%%%1");
    } catch (mysqli_sql_exception $e) {
        mysqli_rollback($con);
        if ($e->getCode() == 1062) die("NEWFLD%%%%%%1062");
        die("NEWFLD%%%%%%0");
    }
endif;
?>
<div class="modal fade" id="modal-folder" tabindex="-1" aria-labelledby="modal-folderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="modal-folderLabel">Dossier de véhicule (Nouveau)</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" action="#" id="form-new-folder">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" required id="ref-folder" name="ref-folder" readonly value="DV-<?php echo sha1('DV-' . date('Y-m-d h:i:s')); ?>">
                        <label for="ref-folder">Référence dossier</label>
                    </div>
                    <div class="form-floating mb-3">
                        <select id="vh-folder" name="vh-folder" class="form-select">
                            <?php $sqlFld = "select * from affectation_vehicule left join vehicule on vehicule.id_vehicule=affectation_vehicule.id_vehicule left join chauffeur on chauffeur.id_chauffeur=affectation_vehicule.id_chauffeur left join region on affectation_vehicule.id_region=region.id_region where is_ferme=0";
                            $paramsFld = [];
                            if ($_SESSION['usr-con']['region-sel'] != '') { $sqlFld .= " and affectation_vehicule.id_region=?"; $paramsFld[] = (int)$_SESSION['usr-con']['region-sel']; }
                            $q = db_select($con, $sqlFld, $paramsFld);
                            while ($r = mysqli_fetch_array($q)):
                                echo "<option value='" . sha1($r[0] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r[0] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >{$r['immatriculation_vehicule']} ({$r['nom_chauffeur']})</option>";
                            endwhile;
                            ?>
                        </select>
                        <label for="vh-folder">Véhicule</label>
                    </div>
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <select class="form-select" id="folder-doc">
                                <?php $q = db_select($con, "select * from document_vehicule");
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
                <button type="button" class="btn btn-primary" onclick="saveFolder()">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<script>
    function saveFolder() {
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
        if(docs.length==0){
            $('#form-new-folder').notify("Vous devez ajouter des documents pour pouvoir créer un dossier",{position:'top'})
            return false
        }
        $.ajax({
            type: 'post',
            data: $('#form-new-folder').serialize()
        }).done((e) => {
            let v = e.split('NEWFLD%%%%%%')[1]
            if (v == '1') {
                alert('Enregistrement effectué!')
                location.reload()
            } 
            $('#modal-folder').notify("Erreur lors de l'enregistrement !", {
                position: 'top'
            })
        })
    }

    function openModalFolders() {
        $('#modal-folder').modal('show')
    }
    var docs = []

    function addToFolder(doc, dt,refd) {
        if(dt==""){
            $('#date-expiry').notify("Ce champs est obligatoire!",{position:'top'})
            return false
        }
        if(refd==""){
            $('#ref-doc').notify("Ce champs est obligatoire!",{position:'top'})
            return false
        }
        docname=$('#folder-doc option[value="'+doc+'"]').html()
        doc = {
            id: doc,
            name: docname,
            dtexpiry:dt,
            refdoc:refd
        }
        if (typeof(docs.find(({
                id
            }) => id === doc.id)) != "undefined") {
            /* docs.splice(docs.findIndex(({
                id
            }) => id === doc.id), 1) */
             $('#folder-doc').notify("Ce document a déjà été ajouté!",{position:'top'})
        }else{
        docs.push(doc)
        }
        liste="";
        for(i=0;i<docs.length;i++){
            liste+="<div class='input-group' id='"+docs[i].id+"'><input type='hidden' name='doc-list-id[]' value='"+docs[i].id+"'><input class='form-control' type='text' readonly required id='"+docs[i].id+"' name='doc-list-name[]' value='"+docs[i].name+"'><input type='date' class='form-control' readonly required id='dt-"+docs[i].id+"' name='dt-list-name[]' value='"+docs[i].dtexpiry+"'><input type='text' class='form-control' readonly required id='refd-"+docs[i].id+"' name='refd-list-name[]' value='"+docs[i].refdoc+"'><button class='btn btn-danger' type='button' title='Retirer du dossier' onclick='remToFolder(\""+docs[i].id+"\")'><i class='fa fa-times'></i></button></div>"
        }
        $('#doc-list').html(liste)
        $('#ref-doc').val('')
    }
    function remToFolder(id){
        if(confirm("Etes-vous sûr de vouloir continuer ?")){
            docs.splice(docs.findIndex(({
                id
            }) => id === docs.id), 1)
            $('#'+id).remove()
        }
    }
</script>