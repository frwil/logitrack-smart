<?php /* POST handled by DossierController — see controllers/router.php */ ?>
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
                            <?php $configRepo = new ConfigRepository($con);
                            foreach ($configRepo->findAllFoldersByRegion((int)$_SESSION['usr-con']['region-sel']) as $r):
                                echo "<option value='" . sha1($r['id_affectation'] . $r['id_vehicule']) . "' " . (isset($_GET['idvgch']) && $_GET['idvgch'] == sha1($r['id_affectation'] . $r['id_vehicule']) ? "selected" : (isset($_GET['idvgch']) ? "disabled" : "")) . " >" . h($r['immatriculation_vehicule']) . " (" . h($r['nom_chauffeur']) . ")</option>";
                            endforeach;
                            ?>
                        </select>
                        <label for="vh-folder">Véhicule</label>
                    </div>
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <select class="form-select" id="folder-doc">
                                <?php foreach ($configRepo->findAllDocuments() as $r):
                                    echo "<option value='" . sha1($r['id_document'] . $r['nom_document']) . "'>" . h($r['nom_document']) . "</option>";
                                endforeach;
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
            data: $('#form-new-folder').serialize(),
            dataType: 'json'
        }).done((e) => {
            if (e.success) {
                showSuccess('Enregistrement effectué!')
                location.reload()
            } else if (e.error == '1062') {
                $('#modal-folder').notify("Ce dossier existe déjà!", {
                    position: 'top'
                })
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