<?php

namespace App\Controller;

use App\Entity\TypeChargementVoyage;
use App\Repository\TypeChargementVoyageRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/type/chargement/voyage')]
class TypeChargementVoyageController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_type_chargement_voyage_index', methods: ['GET'])]
    public function index(TypeChargementVoyageRepository $typeChargementVoyageRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_chargement_voyage.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des types de chargement.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('type_chargement_voyage/index.html.twig', [
            'type_chargement_voyages' => $typeChargementVoyageRepository->findBy([], ['libelle' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_type_chargement_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_chargement_voyage.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un type de chargement.');
            return $this->redirectToRoute('app_type_chargement_voyage_index');
        }

        $typeChargementVoyage = new TypeChargementVoyage();
        
        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            
            // Validation des données
            if (empty($libelle)) {
                $this->addFlash('error', 'Le libellé est obligatoire.');
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'CREATE',
                    'TypeChargementVoyage',
                    0,
                    [
                        'attempted_data' => ['libelle' => $libelle],
                        'validation_errors' => ['Le libellé est obligatoire.']
                    ],
                    'error'
                );
                
                return $this->render('type_chargement_voyage/new.html.twig');
            }
            
            // Vérifier l'unicité du libellé
            $existingType = $entityManager->getRepository(TypeChargementVoyage::class)
                ->findOneBy(['libelle' => $libelle]);
            
            if ($existingType) {
                $this->addFlash('error', 'Un type de chargement avec ce libellé existe déjà.');
                
                // Audit log pour duplication
                $this->auditLogger->log(
                    'CREATE',
                    'TypeChargementVoyage',
                    0,
                    [
                        'attempted_data' => ['libelle' => $libelle],
                        'validation_errors' => ['Un type de chargement avec ce libellé existe déjà.']
                    ],
                    'error'
                );
                
                return $this->render('type_chargement_voyage/new.html.twig');
            }
            
            $typeChargementVoyage->setLibelle($libelle);
            
            try {
                $entityManager->persist($typeChargementVoyage);
                $entityManager->flush();
                
                // Audit log pour la création
                $this->auditLogger->log(
                    'CREATE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    ['new_data' => ['libelle' => $typeChargementVoyage->getLibelle()]]
                );
                
                $this->addFlash('success', 'Type de chargement créé avec succès.');
                return $this->redirectToRoute('app_type_chargement_voyage_index');
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la création
                $this->auditLogger->log(
                    'CREATE',
                    'TypeChargementVoyage',
                    0,
                    [
                        'attempted_data' => ['libelle' => $libelle],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création du type de chargement.');
                return $this->render('type_chargement_voyage/new.html.twig');
            }
        }
        
        return $this->render('type_chargement_voyage/new.html.twig');
    }

    #[Route('/{id}', name: 'app_type_chargement_voyage_show', methods: ['GET'])]
    public function show(TypeChargementVoyage $typeChargementVoyage): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_chargement_voyage.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce type de chargement.');
            return $this->redirectToRoute('app_type_chargement_voyage_index');
        }

        return $this->render('type_chargement_voyage/show.html.twig', [
            'type_chargement_voyage' => $typeChargementVoyage,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_chargement_voyage_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeChargementVoyage $typeChargementVoyage, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_chargement_voyage.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce type de chargement.');
            return $this->redirectToRoute('app_type_chargement_voyage_index');
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = [
            'libelle' => $typeChargementVoyage->getLibelle()
        ];

        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            
            // Validation des données
            if (empty($libelle)) {
                $this->addFlash('error', 'Le libellé est obligatoire.');
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'UPDATE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => ['libelle' => $libelle],
                        'validation_errors' => ['Le libellé est obligatoire.']
                    ],
                    'error'
                );
                
                return $this->render('type_chargement_voyage/edit.html.twig', [
                    'type_chargement_voyage' => $typeChargementVoyage,
                ]);
            }
            
            // Vérifier l'unicité du libellé (en excluant l'enregistrement actuel)
            $existingType = $entityManager->getRepository(TypeChargementVoyage::class)
                ->findOneBy(['libelle' => $libelle]);
            
            if ($existingType && $existingType->getId() !== $typeChargementVoyage->getId()) {
                $this->addFlash('error', 'Un autre type de chargement avec ce libellé existe déjà.');
                
                // Audit log pour duplication
                $this->auditLogger->log(
                    'UPDATE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => ['libelle' => $libelle],
                        'validation_errors' => ['Un autre type de chargement avec ce libellé existe déjà.']
                    ],
                    'error'
                );
                
                return $this->render('type_chargement_voyage/edit.html.twig', [
                    'type_chargement_voyage' => $typeChargementVoyage,
                ]);
            }
            
            $typeChargementVoyage->setLibelle($libelle);
            
            try {
                $entityManager->flush();
                
                // Audit log pour la modification avec anciennes et nouvelles valeurs
                $this->auditLogger->log(
                    'UPDATE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    [
                        'old_data' => $oldData,
                        'new_data' => ['libelle' => $typeChargementVoyage->getLibelle()]
                    ]
                );
                
                $this->addFlash('success', 'Type de chargement modifié avec succès.');
                return $this->redirectToRoute('app_type_chargement_voyage_index');
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la modification
                $this->auditLogger->log(
                    'UPDATE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => ['libelle' => $libelle],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du type de chargement.');
                return $this->render('type_chargement_voyage/edit.html.twig', [
                    'type_chargement_voyage' => $typeChargementVoyage,
                ]);
            }
        }
        
        return $this->render('type_chargement_voyage/edit.html.twig', [
            'type_chargement_voyage' => $typeChargementVoyage,
        ]);
    }

    #[Route('/{id}', name: 'app_type_chargement_voyage_delete', methods: ['POST'])]
    public function delete(Request $request, TypeChargementVoyage $typeChargementVoyage, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_chargement_voyage.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce type de chargement.');
            return $this->redirectToRoute('app_type_chargement_voyage_index');
        }

        if ($this->isCsrfTokenValid('delete'.$typeChargementVoyage->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des voyages associés
            if ($typeChargementVoyage->getVoyages()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce type de chargement car il est utilisé dans des voyages.');
                
                // Audit log pour tentative de suppression d'un type utilisé
                $this->auditLogger->log(
                    'DELETE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    [
                        'deleted_data' => ['libelle' => $typeChargementVoyage->getLibelle()],
                        'error' => 'Type de chargement utilisé dans des voyages'
                    ],
                    'error'
                );
                
                return $this->redirectToRoute('app_type_chargement_voyage_show', ['id' => $typeChargementVoyage->getId()]);
            }
            
            try {
                // Sauvegarder les données avant suppression pour l'audit
                $deletedData = [
                    'libelle' => $typeChargementVoyage->getLibelle()
                ];
                
                $entityManager->remove($typeChargementVoyage);
                $entityManager->flush();
                
                // Audit log pour la suppression
                $this->auditLogger->log(
                    'DELETE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    ['deleted_data' => $deletedData]
                );
                
                $this->addFlash('success', 'Type de chargement supprimé avec succès.');
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la suppression
                $this->auditLogger->log(
                    'DELETE',
                    'TypeChargementVoyage',
                    $typeChargementVoyage->getId(),
                    [
                        'deleted_data' => ['libelle' => $typeChargementVoyage->getLibelle()],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du type de chargement.');
            }
        } else {
            // Audit log pour token CSRF invalide
            $this->auditLogger->log(
                'DELETE',
                'TypeChargementVoyage',
                $typeChargementVoyage->getId(),
                [
                    'error' => 'Jeton CSRF invalide'
                ],
                'error'
            );
            
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_type_chargement_voyage_index');
    }
}