<?php
// src/Controller/CentreCoutController.php

namespace App\Controller;

use App\Entity\CentreCout;
use App\Repository\CentreCoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\AuditLogger;

#[Route('/centre/cout')]
class CentreCoutController extends AbstractController
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    private function getCentreCoutData(CentreCout $centreCout): array
    {
        return [
            'libelle' => $centreCout->getLibelle(),
            'description' => $centreCout->getDescription(),
            'estActif' => $centreCout->isEstActif(),
        ];
    }

    #[Route('/', name: 'app_centre_cout_index', methods: ['GET'])]
    public function index(CentreCoutRepository $centreCoutRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.view')) {
            $this->auditLogger->log(
                'tentative_access', 
                'CentreCout', 
                0, 
                ['route' => 'app_centre_cout_index', 'reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des centres de coût.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('centre_cout/index.html.twig', [
            'centre_couts' => $centreCoutRepository->findBy([], ['libelle' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_centre_cout_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'CentreCout', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un centre de coût.');
            return $this->redirectToRoute('app_centre_cout_index');
        }

        $centreCout = new CentreCout();

        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            $description = trim($request->request->get('description', ''));
            $estActif = (bool)$request->request->get('estActif', false);

            // Validation manuelle renforcée
            if (empty($libelle)) {
                $this->auditLogger->log(
                    'tentative_create', 
                    'CentreCout', 
                    0, 
                    ['error' => 'Libellé manquant'], 
                    'error'
                );
                $this->addFlash('error', 'Le libellé est obligatoire.');
                return $this->render('centre_cout/new.html.twig', [
                    'libelle' => $libelle,
                    'description' => $description,
                    'estActif' => $estActif,
                ]);
            }

            if (mb_strlen($libelle) > 255) {
                $this->auditLogger->log(
                    'tentative_create', 
                    'CentreCout', 
                    0, 
                    ['error' => 'Libellé trop long'], 
                    'error'
                );
                $this->addFlash('error', 'Le libellé ne doit pas dépasser 255 caractères.');
                return $this->render('centre_cout/new.html.twig', [
                    'libelle' => $libelle,
                    'description' => $description,
                    'estActif' => $estActif,
                ]);
            }

            if (mb_strlen($description) > 500) {
                $this->auditLogger->log(
                    'tentative_create', 
                    'CentreCout', 
                    0, 
                    ['error' => 'Description trop longue'], 
                    'error'
                );
                $this->addFlash('error', 'La description ne doit pas dépasser 500 caractères.');
                return $this->render('centre_cout/new.html.twig', [
                    'libelle' => $libelle,
                    'description' => $description,
                    'estActif' => $estActif,
                ]);
            }

            $centreCout->setLibelle($libelle);
            $centreCout->setDescription($description);
            $centreCout->setEstActif($estActif);

            $errors = $validator->validate($centreCout);

            if (count($errors) === 0) {
                try {
                    $entityManager->persist($centreCout);
                    $entityManager->flush();

                    // Log de l'action de création
                    $this->auditLogger->log(
                        'create',
                        'CentreCout',
                        $centreCout->getId(),
                        ['new_data' => $this->getCentreCoutData($centreCout)]
                    );

                    $this->addFlash('success', 'Le centre de coût a été créé avec succès.');
                    return $this->redirectToRoute('app_centre_cout_index');
                } catch (\Exception $e) {
                    $this->auditLogger->log(
                        'create', 
                        'CentreCout', 
                        0, 
                        ['error' => $e->getMessage()], 
                        'error'
                    );
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du centre de coût: ' . $e->getMessage());
                }
            } else {
                // Journaliser les erreurs de validation
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $this->auditLogger->log(
                    'tentative_create', 
                    'CentreCout', 
                    0, 
                    ['errors' => $errorMessages], 
                    'error'
                );
                
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('centre_cout/new.html.twig', [
            'libelle' => $request->request->get('libelle', ''),
            'description' => $request->request->get('description', ''),
            'estActif' => $request->request->get('estActif', true),
        ]);
    }

    #[Route('/ajax-new', name: 'app_centre_cout_ajax_new', methods: ['POST'])]
    public function ajaxNew(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'CentreCout', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un centre de coût.'
            ], Response::HTTP_FORBIDDEN);
        }

        $centreCout = new CentreCout();

        $libelle = trim($request->request->get('libelle', ''));
        $description = trim($request->request->get('description', ''));

        if (empty($libelle)) {
            $this->auditLogger->log(
                'tentative_create', 
                'CentreCout', 
                0, 
                ['error' => 'Libellé manquant'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Le libellé est obligatoire.'
            ]);
        }

        if (mb_strlen($libelle) > 255) {
            $this->auditLogger->log(
                'tentative_create', 
                'CentreCout', 
                0, 
                ['error' => 'Libellé trop long'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Le libellé ne doit pas dépasser 255 caractères.'
            ]);
        }

        if (mb_strlen($description) > 500) {
            $this->auditLogger->log(
                'tentative_create', 
                'CentreCout', 
                0, 
                ['error' => 'Description trop longue'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'La description ne doit pas dépasser 500 caractères.'
            ]);
        }

        $centreCout->setLibelle($libelle);
        $centreCout->setDescription($description);
        $centreCout->setEstActif(true);

        $errors = $validator->validate($centreCout);

        if (count($errors) === 0) {
            try {
                $entityManager->persist($centreCout);
                $entityManager->flush();

                // Log de l'action de création via AJAX
                $this->auditLogger->log(
                    'create',
                    'CentreCout',
                    $centreCout->getId(),
                    ['new_data' => $this->getCentreCoutData($centreCout)]
                );

                return new JsonResponse([
                    'success' => true,
                    'centreCout' => [
                        'id' => $centreCout->getId(),
                        'libelle' => $centreCout->getLibelle()
                    ]
                ]);
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'create', 
                    'CentreCout', 
                    0, 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création du centre de coût: ' . $e->getMessage()
                ]);
            }
        }

        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        $this->auditLogger->log(
            'tentative_create', 
            'CentreCout', 
            0, 
            ['errors' => $errorMessages], 
            'error'
        );

        return new JsonResponse([
            'success' => false,
            'message' => implode(', ', $errorMessages)
        ]);
    }

    #[Route('/ajax-list', name: 'app_centre_cout_list', methods: ['GET'])]
    public function ajaxList(CentreCoutRepository $repository): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.view')) {
            $this->auditLogger->log(
                'tentative_access', 
                'CentreCout', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return new JsonResponse([
                'error' => 'Accès non autorisé'
            ], Response::HTTP_FORBIDDEN);
        }

        $centreCouts = $repository->findBy(['estActif' => true], ['libelle' => 'ASC']);

        $data = [];
        foreach ($centreCouts as $centreCout) {
            $data[] = [
                'id' => $centreCout->getId(),
                'libelle' => $centreCout->getLibelle()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}', name: 'app_centre_cout_show', methods: ['GET'])]
    public function show(CentreCout $centreCout): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.view')) {
            $this->auditLogger->log(
                'tentative_view', 
                'CentreCout', 
                $centreCout->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce centre de coût.');
            return $this->redirectToRoute('app_centre_cout_index');
        }

        return $this->render('centre_cout/show.html.twig', [
            'centre_cout' => $centreCout,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_centre_cout_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CentreCout $centreCout, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.update')) {
            $this->auditLogger->log(
                'tentative_update', 
                'CentreCout', 
                $centreCout->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce centre de coût.');
            return $this->redirectToRoute('app_centre_cout_index');
        }

        // Vérifier si le centre de coût peut être modifié
        if ($centreCout->getNombreBonsReparation() > 0) {
            $this->auditLogger->log(
                'tentative_update', 
                'CentreCout', 
                $centreCout->getId(), 
                ['error' => 'Impossible de modifier - associé à des bons de réparation'], 
                'error'
            );
            $this->addFlash('warning', 'Ce centre de coût ne peut pas être modifié car il est associé à des bons de réparation.');
            return $this->redirectToRoute('app_centre_cout_show', ['id' => $centreCout->getId()]);
        }

        // Sauvegarder les anciennes données pour le log
        $oldData = $this->getCentreCoutData($centreCout);

        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            $description = trim($request->request->get('description', ''));
            $estActif = (bool)$request->request->get('estActif', false);

            if (empty($libelle)) {
                $this->auditLogger->log(
                    'tentative_update', 
                    'CentreCout', 
                    $centreCout->getId(), 
                    ['error' => 'Libellé manquant'], 
                    'error'
                );
                $this->addFlash('error', 'Le libellé est obligatoire.');
                return $this->render('centre_cout/edit.html.twig', [
                    'centre_cout' => $centreCout,
                    'libelle' => $libelle,
                    'description' => $description,
                    'estActif' => $estActif,
                ]);
            }

            if (mb_strlen($libelle) > 255) {
                $this->auditLogger->log(
                    'tentative_update', 
                    'CentreCout', 
                    $centreCout->getId(), 
                    ['error' => 'Libellé trop long'], 
                    'error'
                );
                $this->addFlash('error', 'Le libellé ne doit pas dépasser 255 caractères.');
                return $this->render('centre_cout/edit.html.twig', [
                    'centre_cout' => $centreCout,
                    'libelle' => $libelle,
                    'description' => $description,
                    'estActif' => $estActif,
                ]);
            }

            if (mb_strlen($description) > 500) {
                $this->auditLogger->log(
                    'tentative_update', 
                    'CentreCout', 
                    $centreCout->getId(), 
                    ['error' => 'Description trop longue'], 
                    'error'
                );
                $this->addFlash('error', 'La description ne doit pas dépasser 500 caractères.');
                return $this->render('centre_cout/edit.html.twig', [
                    'centre_cout' => $centreCout,
                    'libelle' => $libelle,
                    'description' => $description,
                    'estActif' => $estActif,
                ]);
            }

            $centreCout->setLibelle($libelle);
            $centreCout->setDescription($description);
            $centreCout->setEstActif($estActif);

            $errors = $validator->validate($centreCout);

            if (count($errors) === 0) {
                try {
                    $entityManager->flush();

                    // Log de l'action de modification
                    $this->auditLogger->log(
                        'update',
                        'CentreCout',
                        $centreCout->getId(),
                        [
                            'old_data' => $oldData,
                            'new_data' => $this->getCentreCoutData($centreCout)
                        ]
                    );

                    $this->addFlash('success', 'Le centre de coût a été modifié avec succès.');
                    return $this->redirectToRoute('app_centre_cout_index');
                } catch (\Exception $e) {
                    $this->auditLogger->log(
                        'update', 
                        'CentreCout', 
                        $centreCout->getId(), 
                        ['error' => $e->getMessage()], 
                        'error'
                    );
                    $this->addFlash('error', 'Une erreur est survenue lors de la modification du centre de coût: ' . $e->getMessage());
                }
            } else {
                // Journaliser les erreurs de validation
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $this->auditLogger->log(
                    'tentative_update', 
                    'CentreCout', 
                    $centreCout->getId(), 
                    ['errors' => $errorMessages], 
                    'error'
                );
                
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('centre_cout/edit.html.twig', [
            'centre_cout' => $centreCout,
            'libelle' => $request->request->get('libelle', $centreCout->getLibelle()),
            'description' => $request->request->get('description', $centreCout->getDescription()),
            'estActif' => $request->request->get('estActif', $centreCout->isEstActif()),
        ]);
    }

    #[Route('/{id}', name: 'app_centre_cout_delete', methods: ['POST'])]
    public function delete(Request $request, CentreCout $centreCout, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('centre_cout.delete')) {
            $this->auditLogger->log(
                'tentative_delete', 
                'CentreCout', 
                $centreCout->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce centre de coût.');
            return $this->redirectToRoute('app_centre_cout_index');
        }

        if (!$centreCout->peutEtreSupprime()) {
            $this->auditLogger->log(
                'tentative_delete', 
                'CentreCout', 
                $centreCout->getId(), 
                ['error' => 'Impossible de supprimer - associé à des bons de réparation'], 
                'error'
            );
            $this->addFlash('error', 'Ce centre de coût ne peut pas être supprimé car il est associé à des bons de réparation.');
            return $this->redirectToRoute('app_centre_cout_show', ['id' => $centreCout->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $centreCout->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour le log
                $oldData = $this->getCentreCoutData($centreCout);
                
                $entityManager->remove($centreCout);
                $entityManager->flush();

                // Log de l'action de suppression
                $this->auditLogger->log(
                    'delete',
                    'CentreCout',
                    $centreCout->getId(),
                    ['deleted_data' => $oldData]
                );

                $this->addFlash('success', 'Le centre de coût a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'delete', 
                    'CentreCout', 
                    $centreCout->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du centre de coût: ' . $e->getMessage());
            }
        } else {
            $this->auditLogger->log(
                'tentative_delete', 
                'CentreCout', 
                $centreCout->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_centre_cout_index');
    }
}