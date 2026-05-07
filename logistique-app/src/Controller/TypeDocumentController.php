<?php

namespace App\Controller;

use App\Entity\TypeDocument;
use App\Repository\TypeDocumentRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/type-document')]
class TypeDocumentController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_type_document_index', methods: ['GET'])]
    public function index(TypeDocumentRepository $typeDocumentRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_document.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des types de documents.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('type_document/index.html.twig', [
            'type_documents' => $typeDocumentRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_type_document_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_document.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un type de document.');
            return $this->redirectToRoute('app_type_document_index');
        }

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $validite = (int) $request->request->get('validite', 0);

            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du type de document est requis.');
                return $this->render('type_document/new.html.twig');
            }

            if ($validite < 0) {
                $this->addFlash('error', 'La validité ne peut pas être négative.');
                return $this->render('type_document/new.html.twig');
            }

            // Vérifier si le type existe déjà
            $existingType = $entityManager->getRepository(TypeDocument::class)->findOneBy(['nom' => $nom]);
            if ($existingType) {
                $this->addFlash('error', 'Ce type de document existe déjà.');
                return $this->render('type_document/new.html.twig');
            }

            $typeDocument = new TypeDocument();
            $typeDocument->setNom($nom);
            $typeDocument->setValidite($validite);

            try {
                $entityManager->persist($typeDocument);
                $entityManager->flush();

                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    TypeDocument::class,
                    $typeDocument->getId(),
                    ['new' => ['nom' => $nom, 'validite' => $validite]],
                    'success'
                );

                $this->addFlash('success', 'Type de document créé avec succès.');
                return $this->redirectToRoute('app_type_document_index');
            } catch (\Exception $e) {
                // Log de l'échec de création
                $this->auditLogger->log(
                    'create',
                    TypeDocument::class,
                    0,
                    ['attempted_data' => ['nom' => $nom, 'validite' => $validite], 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création du type de document.');
                return $this->render('type_document/new.html.twig');
            }
        }

        return $this->render('type_document/new.html.twig');
    }

    #[Route('/{id}', name: 'app_type_document_show', methods: ['GET'])]
    public function show(TypeDocument $typeDocument): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_document.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce type de document.');
            return $this->redirectToRoute('app_type_document_index');
        }

        return $this->render('type_document/show.html.twig', [
            'type_document' => $typeDocument,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_document_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeDocument $typeDocument, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_document.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce type de document.');
            return $this->redirectToRoute('app_type_document_index');
        }

        // Sauvegarde des anciennes valeurs pour le log
        $oldData = [
            'nom' => $typeDocument->getNom(),
            'validite' => $typeDocument->getValidite()
        ];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            $validite = (int) $request->request->get('validite', 0);

            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom du type de document est requis.');
                return $this->render('type_document/edit.html.twig', [
                    'type_document' => $typeDocument,
                ]);
            }

            if ($validite < 0) {
                $this->addFlash('error', 'La validité ne peut pas être négative.');
                return $this->render('type_document/edit.html.twig', [
                    'type_document' => $typeDocument,
                ]);
            }

            // Vérifier si un autre type existe déjà avec ce nom
            $existingType = $entityManager->getRepository(TypeDocument::class)->findOneBy(['nom' => $nom]);
            if ($existingType && $existingType->getId() !== $typeDocument->getId()) {
                $this->addFlash('error', 'Un autre type de document avec ce nom existe déjà.');
                return $this->render('type_document/edit.html.twig', [
                    'type_document' => $typeDocument,
                ]);
            }

            // Nouvelles valeurs
            $newData = [
                'nom' => $nom,
                'validite' => $validite
            ];

            $typeDocument->setNom($nom);
            $typeDocument->setValidite($validite);

            try {
                $entityManager->flush();

                // Log de l'action de modification avec anciennes et nouvelles valeurs
                $changes = [];
                foreach ($oldData as $key => $oldValue) {
                    if ($oldValue != $newData[$key]) {
                        $changes[$key] = [
                            'old' => $oldValue,
                            'new' => $newData[$key]
                        ];
                    }
                }

                if (!empty($changes)) {
                    $this->auditLogger->log(
                        'update',
                        TypeDocument::class,
                        $typeDocument->getId(),
                        $changes,
                        'success'
                    );
                }

                $this->addFlash('success', 'Type de document modifié avec succès.');
                return $this->redirectToRoute('app_type_document_index');
            } catch (\Exception $e) {
                // Log de l'échec de modification
                $this->auditLogger->log(
                    'update',
                    TypeDocument::class,
                    $typeDocument->getId(),
                    ['attempted_data' => $newData, 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du type de document.');
                return $this->render('type_document/edit.html.twig', [
                    'type_document' => $typeDocument,
                ]);
            }
        }

        return $this->render('type_document/edit.html.twig', [
            'type_document' => $typeDocument,
        ]);
    }

    #[Route('/{id}', name: 'app_type_document_delete', methods: ['POST'])]
    public function delete(Request $request, TypeDocument $typeDocument, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_document.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce type de document.');
            return $this->redirectToRoute('app_type_document_index');
        }

        if ($this->isCsrfTokenValid('delete' . $typeDocument->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des documents associés avant de supprimer
            if ($typeDocument->getDocuments()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce type de document car il est associé à des documents.');
                return $this->redirectToRoute('app_type_document_show', ['id' => $typeDocument->getId()]);
            }

            try {
                // Log de l'action de suppression avec les données de l'entité
                $this->auditLogger->log(
                    'delete',
                    TypeDocument::class,
                    $typeDocument->getId(),
                    ['old' => ['nom' => $typeDocument->getNom(), 'validite' => $typeDocument->getValidite()]],
                    'success'
                );

                $entityManager->remove($typeDocument);
                $entityManager->flush();

                $this->addFlash('success', 'Type de document supprimé avec succès.');
            } catch (\Exception $e) {
                // Log de l'échec de suppression
                $this->auditLogger->log(
                    'delete',
                    TypeDocument::class,
                    $typeDocument->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du type de document.');
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_type_document_index');
    }

    #[Route('/ajax/create', name: 'app_type_document_ajax_create', methods: ['POST'])]
    public function ajaxCreate(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_document.create')) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un type de document.'
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Requête invalide.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $typeName = trim($data['name'] ?? '');
        $typeValidite = (int) ($data['validite'] ?? 0);

        // Validation des données
        if (empty($typeName)) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Le nom du type de document est requis.'
            ]);
        }

        if ($typeValidite < 0) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'La validité ne peut pas être négative.'
            ]);
        }

        // Vérifier si le type existe déjà
        $existingType = $entityManager->getRepository(TypeDocument::class)->findOneBy(['nom' => $typeName]);
        if ($existingType) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Ce type de document existe déjà.'
            ]);
        }

        // Créer le nouveau type
        $typeDocument = new TypeDocument();
        $typeDocument->setNom($typeName);
        $typeDocument->setValidite($typeValidite);

        try {
            $entityManager->persist($typeDocument);
            $entityManager->flush();

            // Log de l'action de création via AJAX
            $this->auditLogger->log(
                'create',
                TypeDocument::class,
                $typeDocument->getId(),
                ['new' => ['nom' => $typeName, 'validite' => $typeValidite]],
                'success'
            );

            return new JsonResponse([
                'success' => true,
                'typeId' => $typeDocument->getId(),
                'typeName' => $typeDocument->getNom()
            ]);
        } catch (\Exception $e) {
            // Log de l'échec de création via AJAX
            $this->auditLogger->log(
                'create',
                TypeDocument::class,
                0,
                ['attempted_data' => ['nom' => $typeName, 'validite' => $typeValidite], 'error' => $e->getMessage()],
                'error'
            );
            
            return new JsonResponse([
                'success' => false, 
                'message' => 'Une erreur est survenue lors de la création du type de document.'
            ]);
        }
    }
}