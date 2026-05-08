<?php
class ConfigController extends BaseController
{
    private ConfigRepository $repo;

    public function __construct(ConfigRepository $repo) { $this->repo = $repo; }

    // -- Type permis --
    public function fetchTypePermis(): never {
        $row = $this->repo->findTypePermisById((int)$this->post('c-dl-s'));
        if (!$row) $this->jsonError('Introuvable', 404);
        unset($row[0], $row['id_type_permis']);
        $this->json(['data' => $row]);
    }
    public function updateTypePermis(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateTypePermisById((int)$this->post('id-type-permis'), $this->post('lib-type-upd'), $this->post('desc-type-upd') ?: null)
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
    public function deleteTypePermis(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->deleteTypePermisById((int)$this->post('dl-id'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    // -- Document --
    public function updateDocument(): never {
        try { $this->repo->transactional(fn() =>
            $this->repo->updateDocumentById((int)$this->post('id-doc'), $this->post('nom-doc-upd'), (int)$this->post('valid-doc-upd'))
        ); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }

    // -- Folder --
    public function updateFolder(): never {
        try { $this->repo->transactional(function() {
            $this->repo->deleteFolderDocumentsByRef($this->post('ref-folder'));
            $ids = $this->post('doc-list-id', []);
            $names = $this->post('doc-list-name', []);
            $dts = $this->post('dt-list-name', []);
            $refDocs = $this->post('refd-list-name', []);
            $vh = $this->post('vh-folder-upd');
            $ref = $this->post('ref-folder');
            for ($i = 0; $i < count($names); $i++) {
                $this->repo->exec(
                    "INSERT INTO dossier_vehicule_document (id_document, date_expiration_document, id_vehicule, id_dossier_vehicule, ref_document)
                     VALUES (?, ?, (SELECT id_vehicule FROM affectation_vehicule WHERE id_affectation = ?), (SELECT id_dossier_vehicule FROM dossier_vehicule WHERE ref_dossier = ?), ?)",
                    [(int)$ids[$i], $dts[$i], (int)$vh, $ref, $refDocs[$i]]
                );
            }
        }); $this->json(); } catch (\mysqli_sql_exception $e) { $this->jsonError('Erreur'); }
    }
}
