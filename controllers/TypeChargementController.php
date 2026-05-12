<?php
class TypeChargementController extends BaseController
{
    public function fetchByHash(): void
    {
        $id = (int)$this->post('id-typechargement-forModal');
        if (!$id) { $this->jsonError('ID manquant'); return; }
        $repo = new TypeChargementRepository($GLOBALS['con']);
        $row = $repo->findById($id);
        if (!$row) { $this->jsonError('Type introuvable'); return; }
        $this->json(['success' => true, 'data' => $row]);
    }

    public function create(): void
    {
        $libelle = trim($this->post('lib-typechargement') ?? '');
        $unite   = trim($this->post('unite-typechargement') ?? '');
        $valMin  = (float)($this->post('valmin-typechargement') ?? 0);
        $valMax  = (float)($this->post('valmax-typechargement') ?? 0);
        if ($libelle === '') { $this->jsonError('Libellé requis'); return; }
        $repo = new TypeChargementRepository($GLOBALS['con']);
        $id = $repo->create($libelle, $unite, $valMin, $valMax);
        if (!$id) { $this->jsonError('Erreur lors de la création'); return; }
        $this->json(['success' => true, 'id' => $id]);
    }

    public function update(): void
    {
        $id      = (int)$this->post('id-typechargement');
        $libelle = trim($this->post('lib-typechargement-upd') ?? '');
        $unite   = trim($this->post('unite-typechargement-upd') ?? '');
        $valMin  = (float)($this->post('valmin-typechargement-upd') ?? 0);
        $valMax  = (float)($this->post('valmax-typechargement-upd') ?? 0);
        if (!$id || $libelle === '') { $this->jsonError('Données invalides'); return; }
        $repo = new TypeChargementRepository($GLOBALS['con']);
        $repo->update($id, $libelle, $unite, $valMin, $valMax);
        $this->json(['success' => true]);
    }

    public function delete(): void
    {
        $id = (int)$this->post('id-typechargement-forDel');
        if (!$id) { $this->jsonError('ID manquant'); return; }
        $repo = new TypeChargementRepository($GLOBALS['con']);
        $repo->delete($id);
        $this->json(['success' => true]);
    }
}
