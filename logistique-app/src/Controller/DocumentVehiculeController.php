<?php

namespace App\Controller;

use App\Entity\DocumentVehicule;
use App\Entity\DossierVehicule;
use App\Entity\TypeDocument;
use App\Entity\Vehicule;
use App\Repository\DocumentVehiculeRepository;
use App\Repository\DossierVehiculeRepository;
use App\Repository\TypeDocumentRepository;
use App\Repository\VehiculeRepository;
use App\Entity\AffectationVehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\AuditLogger;

#[Route('/vehicule/document')]
class DocumentVehiculeController extends AbstractController
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    private function getDocumentVehiculeData(DocumentVehicule $document): array
    {
        return [
            'typeDocument' => $document->getTypeDocument() ? $document->getTypeDocument()->getId() : null,
            'reference' => $document->getReference(),
            'dateExpiration' => $document->getDateExpiration() ? $document->getDateExpiration()->format('Y-m-d') : null,
            'isActive' => $document->isActive(),
            'vehicule' => $document->getVehicule() ? $document->getVehicule()->getId() : null,
            'dossier' => $document->getDossier() ? $document->getDossier()->getId() : null
        ];
    }

    #[Route('/{id}', name: 'app_document_vehicule_index', methods: ['GET'])]
    public function index(int $id, VehiculeRepository $vehiculeRepository, DocumentVehiculeRepository $documentVehiculeRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('document_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux documents de véhicule.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id, 'statut' => true]);
        $selectedRegionId = $request->getSession()->get('selected_region');

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé ou désactivé');
            return $this->redirectToRoute('app_vehicule_index');
        }

        // Vérifier si le véhicule appartient à la région sélectionnée
        if ($selectedRegionId) {
            $affectationRepository = $entityManager->getRepository(AffectationVehicule::class);
            $affectationActive = $affectationRepository->findActiveAffectationForVehicule($vehicule);

            $vehiculeRegion = $affectationActive ? $affectationActive->getIdRegion() : null;

            if (!$vehiculeRegion || $vehiculeRegion->getId() != $selectedRegionId) {
                $this->addFlash('error', 'Accès non autorisé à ce véhicule.');
                return $this->redirectToRoute('app_vehicule_index');
            }
        }

        $documents = $documentVehiculeRepository->findBy(['vehicule' => $vehicule]);

        return $this->render('document_vehicule/index.html.twig', [
            'vehicule' => $vehicule,
            'documents' => $documents,
        ]);
    }

    #[Route('/{id}/new', name: 'app_document_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $id, VehiculeRepository $vehiculeRepository, TypeDocumentRepository $typeDocumentRepository, EntityManagerInterface $entityManager, DossierVehiculeRepository $dossierVehiculeRepository, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('document_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un document de véhicule.');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $id]);
        }

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id, 'statut' => true]);

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé');
            return $this->redirectToRoute('app_vehicule_index');
        }

        // Vérifier l'accès régional
        $selectedRegionId = $request->getSession()->get('selected_region');
        if ($selectedRegionId) {
            $affectationRepository = $entityManager->getRepository(AffectationVehicule::class);
            $affectationActive = $affectationRepository->findActiveAffectationForVehicule($vehicule);
            $vehiculeRegion = $affectationActive ? $affectationActive->getIdRegion() : null;

            if (!$vehiculeRegion || $vehiculeRegion->getId() != $selectedRegionId) {
                $this->addFlash('error', 'Accès non autorisé à ce véhicule.');
                return $this->redirectToRoute('app_vehicule_index');
            }
        }

        $typesDocument = $typeDocumentRepository->findAll();

        if ($request->isMethod('POST')) {
            $typeDocumentId = $request->request->get('typeDocument');
            $reference = trim($request->request->get('reference', ''));
            $dateExpirationInput = $request->request->get('dateExpiration');
            $isActive = $request->request->get('isActive') ? true : false;

            // Validation des données
            $errors = [];
            if (empty($typeDocumentId)) {
                $errors[] = 'Le type de document est obligatoire.';
            }
            if (empty($reference)) {
                $errors[] = 'La référence est obligatoire.';
            }
            if (mb_strlen($reference) > 100) {
                $errors[] = 'La référence ne doit pas dépasser 100 caractères.';
            }
            if (empty($dateExpirationInput)) {
                $errors[] = 'La date d\'expiration est obligatoire.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('document_vehicule/new.html.twig', [
                    'vehicule' => $vehicule,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            // Validation de la date
            try {
                $dateExpiration = new \DateTime($dateExpirationInput);
                if ($dateExpiration < new \DateTime()) {
                    $this->addFlash('warning', 'La date d\'expiration est dans le passé.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide.');
                return $this->render('document_vehicule/new.html.twig', [
                    'vehicule' => $vehicule,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            $typeDocument = $typeDocumentRepository->find($typeDocumentId);

            if (!$typeDocument) {
                $this->addFlash('error', 'Type de document invalide');
                return $this->redirectToRoute('app_document_vehicule_new', ['id' => $vehicule->getId()]);
            }

            // Vérifier si un document avec la même référence existe déjà pour ce véhicule
            $existingDocument = $entityManager->getRepository(DocumentVehicule::class)
                ->findOneBy(['vehicule' => $vehicule, 'reference' => $reference]);
                
            if ($existingDocument) {
                $this->addFlash('error', 'Un document avec cette référence existe déjà pour ce véhicule.');
                return $this->render('document_vehicule/new.html.twig', [
                    'vehicule' => $vehicule,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            // Vérifier si un dossier existe déjà pour ce véhicule
            $dossier = $vehicule->getDossier();
            if (!$dossier) {
                // Créer un nouveau dossier
                $dossier = new DossierVehicule();
                $dossier->setVehicule($vehicule);
                $entityManager->persist($dossier);
            }

            $document = new DocumentVehicule();
            $document->setTypeDocument($typeDocument);
            $document->setReference($reference);
            $document->setDateExpiration($dateExpiration);
            $document->setVehicule($vehicule);
            $document->setDossier($dossier);
            $document->setIsActive($isActive);

            // Validation de l'entité
            $validationErrors = $validator->validate($document);
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('document_vehicule/new.html.twig', [
                    'vehicule' => $vehicule,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            // Si le document est actif, désactiver les autres documents du même type
            if ($isActive) {
                $entityManager->getRepository(DocumentVehicule::class)
                    ->deactivateAllDocumentsByType($vehicule, $typeDocument);
            }

            $entityManager->persist($document);
            try {
                $entityManager->flush();
                
                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    DocumentVehicule::class,
                    $document->getId(),
                    ['new' => $this->getDocumentVehiculeData($document)]
                );
                
                $this->addFlash('success', 'Document ajouté avec succès');
                return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                // En cas de conflit, régénérer une référence unique
                $dossierVehiculeRepository->ensureUniqueReference($dossier);
                $entityManager->flush();
                
                // Log de l'action de création après régénération de référence
                $this->auditLogger->log(
                    'create',
                    DocumentVehicule::class,
                    $document->getId(),
                    ['new' => $this->getDocumentVehiculeData($document)]
                );
                
                $this->addFlash('success', 'Document ajouté avec succès');
                return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'create',
                    DocumentVehicule::class,
                    0,
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du document: ' . $e->getMessage());
                return $this->render('document_vehicule/new.html.twig', [
                    'vehicule' => $vehicule,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }
        }

        return $this->render('document_vehicule/new.html.twig', [
            'vehicule' => $vehicule,
            'typesDocument' => $typesDocument,
        ]);
    }

    #[Route('/{id}/edit/{documentId}', name: 'app_document_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id, int $documentId, VehiculeRepository $vehiculeRepository, TypeDocumentRepository $typeDocumentRepository, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('document_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier un document de véhicule.');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $id]);
        }

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id, 'statut' => true]);

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé');
            return $this->redirectToRoute('app_vehicule_index');
        }
        
        $selectedRegionId = $request->getSession()->get('selected_region');
        if ($selectedRegionId) {
            $affectationRepository = $entityManager->getRepository(AffectationVehicule::class);
            $affectationActive = $affectationRepository->findActiveAffectationForVehicule($vehicule);
            $vehiculeRegion = $affectationActive ? $affectationActive->getIdRegion() : null;

            if (!$vehiculeRegion || $vehiculeRegion->getId() != $selectedRegionId) {
                $this->addFlash('error', 'Accès non autorisé à ce véhicule.');
                return $this->redirectToRoute('app_vehicule_index');
            }
        }

        $document = $entityManager->getRepository(DocumentVehicule::class)->find($documentId);
        $typesDocument = $typeDocumentRepository->findAll();

        if (!$document || $document->getVehicule()->getId() !== $vehicule->getId()) {
            $this->addFlash('error', 'Document non trouvé');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
        }

        // Sauvegarder les anciennes données pour le log
        $oldData = $this->getDocumentVehiculeData($document);

        if ($request->isMethod('POST')) {
            $typeDocumentId = $request->request->get('typeDocument');
            $reference = trim($request->request->get('reference', ''));
            $dateExpirationInput = $request->request->get('dateExpiration');
            $isActive = $request->request->get('isActive') ? true : false;

            // Validation des données
            $errors = [];
            if (empty($typeDocumentId)) {
                $errors[] = 'Le type de document est obligatoire.';
            }
            if (empty($reference)) {
                $errors[] = 'La référence est obligatoire.';
            }
            if (mb_strlen($reference) > 100) {
                $errors[] = 'La référence ne doit pas dépasser 100 caractères.';
            }
            if (empty($dateExpirationInput)) {
                $errors[] = 'La date d\'expiration est obligatoire.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('document_vehicule/edit.html.twig', [
                    'vehicule' => $vehicule,
                    'document' => $document,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            // Validation de la date
            try {
                $dateExpiration = new \DateTime($dateExpirationInput);
                if ($dateExpiration < new \DateTime()) {
                    $this->addFlash('warning', 'La date d\'expiration est dans le passé.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide.');
                return $this->render('document_vehicule/edit.html.twig', [
                    'vehicule' => $vehicule,
                    'document' => $document,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            $typeDocument = $typeDocumentRepository->find($typeDocumentId);

            if (!$typeDocument) {
                $this->addFlash('error', 'Type de document invalide');
                return $this->redirectToRoute('app_document_vehicule_edit', ['id' => $vehicule->getId(), 'documentId' => $documentId]);
            }

            // Vérifier si un autre document avec la même référence existe déjà pour ce véhicule
            $existingDocument = $entityManager->getRepository(DocumentVehicule::class)
                ->findOneBy(['vehicule' => $vehicule, 'reference' => $reference]);
                
            if ($existingDocument && $existingDocument->getId() !== $document->getId()) {
                $this->addFlash('error', 'Un document avec cette référence existe déjà pour ce véhicule.');
                return $this->render('document_vehicule/edit.html.twig', [
                    'vehicule' => $vehicule,
                    'document' => $document,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            $document->setTypeDocument($typeDocument);
            $document->setReference($reference);
            $document->setDateExpiration($dateExpiration);
            $document->setIsActive($isActive);

            // Validation de l'entité
            $validationErrors = $validator->validate($document);
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('document_vehicule/edit.html.twig', [
                    'vehicule' => $vehicule,
                    'document' => $document,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }

            // Si le document est activé, désactiver les autres documents du même type
            if ($isActive) {
                $entityManager->getRepository(DocumentVehicule::class)
                    ->deactivateAllDocumentsByType($vehicule, $typeDocument);
                $document->setIsActive(true);
            }

            try {
                $entityManager->flush();
                
                // Log de l'action de modification
                $this->auditLogger->log(
                    'update',
                    DocumentVehicule::class,
                    $document->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getDocumentVehiculeData($document)
                    ]
                );
                
                $this->addFlash('success', 'Document modifié avec succès');
                return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    DocumentVehicule::class,
                    $document->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du document: ' . $e->getMessage());
                return $this->render('document_vehicule/edit.html.twig', [
                    'vehicule' => $vehicule,
                    'document' => $document,
                    'typesDocument' => $typesDocument,
                    'reference' => $reference,
                    'dateExpiration' => $dateExpirationInput,
                    'isActive' => $isActive,
                ]);
            }
        }

        return $this->render('document_vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'document' => $document,
            'typesDocument' => $typesDocument,
        ]);
    }

    #[Route('/{id}/delete/{documentId}', name: 'app_document_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, int $documentId, VehiculeRepository $vehiculeRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('document_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer un document de véhicule.');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $id]);
        }

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id, 'statut' => true]);

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé');
            return $this->redirectToRoute('app_vehicule_index');
        }
        
        $selectedRegionId = $request->getSession()->get('selected_region');
        if ($selectedRegionId) {
            $affectationRepository = $entityManager->getRepository(AffectationVehicule::class);
            $affectationActive = $affectationRepository->findActiveAffectationForVehicule($vehicule);
            $vehiculeRegion = $affectationActive ? $affectationActive->getIdRegion() : null;

            if (!$vehiculeRegion || $vehiculeRegion->getId() != $selectedRegionId) {
                $this->addFlash('error', 'Accès non autorisé à ce véhicule.');
                return $this->redirectToRoute('app_vehicule_index');
            }
        }

        $document = $entityManager->getRepository(DocumentVehicule::class)->find($documentId);

        if (!$document || $document->getVehicule()->getId() !== $vehicule->getId()) {
            $this->addFlash('error', 'Document non trouvé');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $document->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour le log
                $oldData = $this->getDocumentVehiculeData($document);
                
                $entityManager->remove($document);
                $entityManager->flush();
                
                // Log de l'action de suppression
                $this->auditLogger->log(
                    'delete',
                    DocumentVehicule::class,
                    $document->getId(),
                    ['old' => $oldData]
                );
                
                $this->addFlash('success', 'Document supprimé avec succès');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'delete',
                    DocumentVehicule::class,
                    $document->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du document: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
    }

    #[Route('/{id}/toggle-active/{documentId}', name: 'app_document_vehicule_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, int $id, int $documentId, VehiculeRepository $vehiculeRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('document_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier le statut d\'un document de véhicule.');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $id]);
        }

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id, 'statut' => true]);

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé');
            return $this->redirectToRoute('app_vehicule_index');
        }
        
        $selectedRegionId = $request->getSession()->get('selected_region');
        if ($selectedRegionId) {
            $affectationRepository = $entityManager->getRepository(AffectationVehicule::class);
            $affectationActive = $affectationRepository->findActiveAffectationForVehicule($vehicule);
            $vehiculeRegion = $affectationActive ? $affectationActive->getIdRegion() : null;

            if (!$vehiculeRegion || $vehiculeRegion->getId() != $selectedRegionId) {
                $this->addFlash('error', 'Accès non autorisé à ce véhicule.');
                return $this->redirectToRoute('app_vehicule_index');
            }
        }

        $document = $entityManager->getRepository(DocumentVehicule::class)->find($documentId);

        if (!$document || $document->getVehicule()->getId() !== $vehicule->getId()) {
            $this->addFlash('error', 'Document non trouvé');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
        }

        if ($this->isCsrfTokenValid('toggle-active' . $document->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les anciennes données pour le log
                $oldData = $this->getDocumentVehiculeData($document);
                
                $isActive = !$document->isActive();
                $document->setIsActive($isActive);

                // Si on active le document, désactiver les autres documents du même type
                if ($isActive) {
                    $entityManager->getRepository(DocumentVehicule::class)
                        ->deactivateAllDocumentsByType($vehicule, $document->getTypeDocument());
                    $document->setIsActive(true);
                }

                $entityManager->flush();
                
                // Log de l'action de modification du statut
                $this->auditLogger->log(
                    'update',
                    DocumentVehicule::class,
                    $document->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getDocumentVehiculeData($document)
                    ]
                );
                
                $this->addFlash('success', 'Statut du document modifié avec succès');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    DocumentVehicule::class,
                    $document->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du statut du document: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
    }

    #[Route('/{id}/show/{documentId}', name: 'app_document_vehicule_show', methods: ['GET'])]
    public function show(Request $request, int $id, int $documentId, VehiculeRepository $vehiculeRepository, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('document_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails d\'un document de véhicule.');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $id]);
        }

        $vehicule = $vehiculeRepository->findOneBy(['id' => $id, 'statut' => true]);

        if (!$vehicule) {
            $this->addFlash('error', 'Véhicule non trouvé ou désactivé');
            return $this->redirectToRoute('app_vehicule_index');
        }

        $selectedRegionId = $request->getSession()->get('selected_region');
        if ($selectedRegionId) {
            $affectationRepository = $entityManager->getRepository(AffectationVehicule::class);
            $affectationActive = $affectationRepository->findActiveAffectationForVehicule($vehicule);
            $vehiculeRegion = $affectationActive ? $affectationActive->getIdRegion() : null;

            if (!$vehiculeRegion || $vehiculeRegion->getId() != $selectedRegionId) {
                $this->addFlash('error', 'Accès non autorisé à ce véhicule.');
                return $this->redirectToRoute('app_vehicule_index');
            }
        }

        $document = $entityManager->getRepository(DocumentVehicule::class)->find($documentId);

        if (!$document || $document->getVehicule()->getId() !== $vehicule->getId()) {
            $this->addFlash('error', 'Document non trouvé');
            return $this->redirectToRoute('app_document_vehicule_index', ['id' => $vehicule->getId()]);
        }

        return $this->render('document_vehicule/show.html.twig', [
            'vehicule' => $vehicule,
            'document' => $document,
        ]);
    }
}