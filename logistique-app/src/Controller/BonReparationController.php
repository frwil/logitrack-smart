<?php
// src/Controller/BonReparationController.php

namespace App\Controller;

use App\Entity\BonReparation;
use App\Entity\AffectationVehicule;
use App\Entity\PrestataireIntervention;
use App\Entity\PlusOuMoinsValue;
use App\Entity\CentreCout;
use App\Entity\StatutReparation;
use App\Repository\BonReparationRepository;
use App\Repository\AffectationVehiculeRepository;
use App\Repository\PrestataireInterventionRepository;
use App\Repository\PlusOuMoinsValueRepository;
use App\Repository\CentreCoutRepository;
use App\Repository\StatutReparationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\AuditLogger;

#[Route('/bon/reparation')]
class BonReparationController extends AbstractController
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    private function getBonReparationData(BonReparation $bonReparation): array
    {
        return [
            'numero' => $bonReparation->getNumero(),
            'dateCreation' => $bonReparation->getDateCreation() ? $bonReparation->getDateCreation()->format('Y-m-d H:i:s') : null,
            'affectation' => $bonReparation->getAffectation() ? $bonReparation->getAffectation()->getId() : null,
            'dateEntree' => $bonReparation->getDateEntree() ? $bonReparation->getDateEntree()->format('Y-m-d H:i:s') : null,
            'diagnostic' => $bonReparation->getDiagnostic(),
            'typeExecution' => $bonReparation->isTypeExecution(),
            'prestataire' => $bonReparation->getPrestataire() ? $bonReparation->getPrestataire()->getId() : null,
            'montantReparation' => $bonReparation->getMontantReparation(),
            'plusOuMoinsValue' => $bonReparation->getPlusOuMoinsValue() ? $bonReparation->getPlusOuMoinsValue()->getId() : null,
            'plusOuMoinsValueValeur' => $bonReparation->getPlusOuMoinsValueValeur(),
            'destination' => $bonReparation->getDestination(),
            'dureeReparation' => $bonReparation->getDureeReparation(),
            'dateJustification' => $bonReparation->getDateJustification() ? $bonReparation->getDateJustification()->format('Y-m-d H:i:s') : null,
            'centreCout' => $bonReparation->getCentreCout() ? $bonReparation->getCentreCout()->getId() : null,
            'datePrevueSortie' => $bonReparation->getDatePrevueSortie() ? $bonReparation->getDatePrevueSortie()->format('Y-m-d H:i:s') : null,
            'dateFinReparation' => $bonReparation->getDateFinReparation() ? $bonReparation->getDateFinReparation()->format('Y-m-d H:i:s') : null,
            'observations' => $bonReparation->getObservations(),
            'cloture' => $bonReparation->isCloture(),
            'statut' => $bonReparation->getStatut() ? $bonReparation->getStatut()->getId() : null,
        ];
    }

    #[Route('/', name: 'app_bon_reparation_index', methods: ['GET'])]
    public function index(BonReparationRepository $bonReparationRepository, Request $request): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.view')) {
            $this->auditLogger->log(
                'tentative_access', 
                'BonReparation', 
                0, 
                ['route' => 'app_bon_reparation_index', 'reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des bons de réparation.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $selectedRegionId = $request->getSession()->get('selected_region');

        if ($selectedRegionId) {
            // Filtrer les bons de réparation par région via l'affectation
            $bonReparations = $bonReparationRepository->createQueryBuilder('br')
                ->join('br.affectation', 'a')
                ->join('a.id_region', 'r')
                ->where('r.id = :regionId')
                ->setParameter('regionId', $selectedRegionId)
                ->orderBy('br.dateCreation', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $bonReparations = $bonReparationRepository->findBy([], ['dateCreation' => 'DESC']);
        }

        return $this->render('bon_reparation/index.html.twig', [
            'bon_reparations' => $bonReparations,
        ]);
    }

    #[Route('/new', name: 'app_bon_reparation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        AffectationVehiculeRepository $affectationRepository,
        PrestataireInterventionRepository $prestataireRepository,
        PlusOuMoinsValueRepository $plusOuMoinsValueRepository,
        CentreCoutRepository $centreCoutRepository,
        StatutReparationRepository $statutRepository
    ): Response {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'BonReparation', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un bon de réparation.');
            return $this->redirectToRoute('app_bon_reparation_index');
        }

        $bonReparation = new BonReparation();

        // Récupérer les listes pour les selects
        $affectations = $affectationRepository->findAll();
        $prestataires = $prestataireRepository->findBy(['actif' => true]);
        $plusOuMoinsValues = $plusOuMoinsValueRepository->findBy(['estActif' => true]);
        $centreCouts = $centreCoutRepository->findBy(['estActif' => true]);
        $statuts = $statutRepository->findBy(['estActif' => true]);

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $data = $request->request->all();

            // Traiter les relations
            $affectation = $affectationRepository->find($data['affectation'] ?? 0);
            $prestataire = $prestataireRepository->find($data['prestataire'] ?? 0);
            $plusOuMoinsValue = $plusOuMoinsValueRepository->find($data['plusOuMoinsValue'] ?? 0);
            $centreCout = $centreCoutRepository->find($data['centreCout'] ?? 0);
            $statut = $statutRepository->find($data['statut'] ?? 0);

            // Générer le numéro automatiquement
            $numero = 'BR-' . date('YmdHis') . '-' . rand(100, 999);
            $bonReparation->setNumero($numero);

            // Définir les valeurs
            $bonReparation->setAffectation($affectation);
            $bonReparation->setDateEntree(new \DateTime($data['dateEntree'] ?? 'now'));
            $bonReparation->setDiagnostic($data['diagnostic'] ?? '');
            $bonReparation->setTypeExecution((bool)($data['typeExecution'] ?? false));
            $bonReparation->setPrestataire($prestataire);
            $bonReparation->setMontantReparation((int)($data['montantReparation'] ?? 0));
            $bonReparation->setPlusOuMoinsValue($plusOuMoinsValue);
            $bonReparation->setPlusOuMoinsValueValeur((int)($data['plusOuMoinsValueValeur'] ?? 0));
            $bonReparation->setDestination($data['destination'] ?? '');
            $bonReparation->setDureeReparation((int)($data['dureeReparation'] ?? 0));
            $bonReparation->setDateJustification(new \DateTime($data['dateJustification'] ?? 'now'));
            $bonReparation->setCentreCout($centreCout);
            $bonReparation->setDatePrevueSortie(new \DateTime($data['datePrevueSortie'] ?? 'now'));
            $bonReparation->setObservations($data['observations'] ?? '');
            $bonReparation->setCloture((bool)($data['cloture'] ?? false));
            $bonReparation->setStatut($statut);

            // Si clôturé, définir la date de fin
            if ($bonReparation->isCloture() && !$bonReparation->getDateFinReparation()) {
                $bonReparation->setDateFinReparation(new \DateTime());
            }

            // Valider l'entité
            $errors = $validator->validate($bonReparation);

            if (count($errors) === 0) {
                try {
                    $entityManager->persist($bonReparation);
                    $entityManager->flush();

                    // Log de l'action de création
                    $this->auditLogger->log(
                        'create',
                        'BonReparation',
                        $bonReparation->getId(),
                        ['new_data' => $this->getBonReparationData($bonReparation)]
                    );

                    $this->addFlash('success', 'Le bon de réparation a été créé avec succès.');
                    return $this->redirectToRoute('app_bon_reparation_index');
                } catch (\Exception $e) {
                    $this->auditLogger->log(
                        'create', 
                        'BonReparation', 
                        0, 
                        ['error' => $e->getMessage()], 
                        'error'
                    );
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du bon de réparation: ' . $e->getMessage());
                }
            } else {
                // Journaliser les erreurs de validation
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $this->auditLogger->log(
                    'tentative_create', 
                    'BonReparation', 
                    0, 
                    ['errors' => $errorMessages, 'data' => $data], 
                    'error'
                );
                
                // Si il y a des erreurs, les afficher
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('bon_reparation/new.html.twig', [
            'affectations' => $affectations,
            'prestataires' => $prestataires,
            'plusOuMoinsValues' => $plusOuMoinsValues,
            'centreCouts' => $centreCouts,
            'statuts' => $statuts,
            'form_data' => $request->request->all(),
        ]);
    }

    #[Route('/ajax-new-statut', name: 'app_statut_ajax_new', methods: ['POST'])]
    public function ajaxNewStatut(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'StatutReparation', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un statut.'
            ]);
        }

        $statutReparation = new StatutReparation();

        // Récupérer les données du formulaires
        $libelle = trim($request->request->get('libelle', ''));
        $description = trim($request->request->get('description', ''));

        // Vérifier que le libellé n'est pas empty
        if (empty($libelle)) {
            $this->auditLogger->log(
                'tentative_create', 
                'StatutReparation', 
                0, 
                ['error' => 'Libellé manquant'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Le libellé est obligatoire.'
            ]);
        }

        // Définir les valeurs
        $statutReparation->setLibelle($libelle);
        $statutReparation->setDescription($description);
        $statutReparation->setEstActif(true);

        // Valider l'entité
        $errors = $validator->validate($statutReparation);

        if (count($errors) === 0) {
            try {
                $entityManager->persist($statutReparation);
                $entityManager->flush();

                // Log de création de statut
                $this->auditLogger->log(
                    'create',
                    'StatutReparation',
                    $statutReparation->getId(),
                    ['libelle' => $libelle, 'description' => $description]
                );

                return new JsonResponse([
                    'success' => true,
                    'statut' => [
                        'id' => $statutReparation->getId(),
                        'libelle' => $statutReparation->getLibelle()
                    ]
                ]);
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'create', 
                    'StatutReparation', 
                    0, 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création du statut.'
                ]);
            }
        }

        // Si il y a des erreurs, les retourner
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        $this->auditLogger->log(
            'tentative_create', 
            'StatutReparation', 
            0, 
            ['errors' => $errorMessages], 
            'error'
        );

        return new JsonResponse([
            'success' => false,
            'message' => implode(', ', $errorMessages)
        ]);
    }

    #[Route('/ajax-new-plus-ou-moins-value', name: 'app_plus_ou_moins_value_ajax_new', methods: ['POST'])]
    public function ajaxNewPlusOuMoinsValue(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'PlusOuMoinsValue', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer une plus ou moins value.'
            ]);
        }

        $plusOuMoinsValue = new PlusOuMoinsValue();

        // Récupérer les données du formulaire
        $libelle = trim($request->request->get('libelle', ''));
        $libelle = empty($libelle) ? null : $libelle;
        $description = trim($request->request->get('description', ''));
        $typeValeur = (bool)$request->request->get('typeValeur', true);

        // Vérifier que le libellé n'est pas vide
        if (empty($libelle)) {
            $this->auditLogger->log(
                'tentative_create', 
                'PlusOuMoinsValue', 
                0, 
                ['error' => 'Libellé manquant'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Le libellé est obligatoire.'
            ]);
        }

        // Définir les valeurs
        $plusOuMoinsValue->setLibelle($libelle);
        $plusOuMoinsValue->setTypeValeur($typeValeur);
        $plusOuMoinsValue->setDescription($description);
        $plusOuMoinsValue->setEstActif(true);

        // Valider l'entité
        $errors = $validator->validate($plusOuMoinsValue);

        if (count($errors) === 0) {
            try {
                $entityManager->persist($plusOuMoinsValue);
                $entityManager->flush();

                // Log de création de plus ou moins value
                $this->auditLogger->log(
                    'create',
                    'PlusOuMoinsValue',
                    $plusOuMoinsValue->getId(),
                    ['libelle' => $libelle, 'typeValeur' => $typeValeur, 'description' => $description]
                );

                return new JsonResponse([
                    'success' => true,
                    'plusOuMoinsValue' => [
                        'id' => $plusOuMoinsValue->getId(),
                        'libelle' => $plusOuMoinsValue->getLibelle(),
                        'typeValeur' => $plusOuMoinsValue->getTypeValeur()
                    ]
                ]);
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'create', 
                    'PlusOuMoinsValue', 
                    0, 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création de la plus ou moins value.'
                ]);
            }
        }

        // Si il y a des erreurs, les retourner
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        $this->auditLogger->log(
            'tentative_create', 
            'PlusOuMoinsValue', 
            0, 
            ['errors' => $errorMessages], 
            'error'
        );

        return new JsonResponse([
            'success' => false,
            'message' => implode(', ', $errorMessages)
        ]);
    }

    #[Route('/ajax-list/pomv-list', name: 'app_plus_ou_moins_value_list', methods: ['GET'])]
    public function ajaxPOMList(PlusOuMoinsValueRepository $repository): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.view')) {
            $this->auditLogger->log(
                'tentative_access', 
                'PlusOuMoinsValue', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.'
            ]);
        }

        $values = $repository->findBy(['estActif' => true], ['libelle' => 'ASC']);

        $data = [];
        foreach ($values as $value) {
            $data[] = [
                'id' => $value->getId(),
                'libelle' => $value->getLibelle()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}/edit', name: 'app_bon_reparation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        BonReparation $bonReparation,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        AffectationVehiculeRepository $affectationRepository,
        PrestataireInterventionRepository $prestataireRepository,
        PlusOuMoinsValueRepository $plusOuMoinsValueRepository,
        CentreCoutRepository $centreCoutRepository,
        StatutReparationRepository $statutRepository
    ): Response {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.update')) {
            $this->auditLogger->log(
                'tentative_update', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier un bon de réparation.');
            return $this->redirectToRoute('app_bon_reparation_index');
        }

        // Sauvegarder les anciennes données pour le log
        $oldData = $this->getBonReparationData($bonReparation);

        // Récupérer les listes pour les selects
        $affectations = $affectationRepository->findAll();
        $prestataires = $prestataireRepository->findBy(['actif' => true]);
        $plusOuMoinsValues = $plusOuMoinsValueRepository->findBy(['estActif' => true]);
        $centreCouts = $centreCoutRepository->findBy(['estActif' => true]);
        $statuts = $statutRepository->findBy(['estActif' => true]);

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $data = $request->request->all();

            // Traiter les relations
            $affectation = $affectationRepository->find($data['affectation'] ?? 0);
            $prestataire = $prestataireRepository->find($data['prestataire'] ?? 0);
            $plusOuMoinsValue = $plusOuMoinsValueRepository->find($data['plusOuMoinsValue'] ?? 0);
            $centreCout = $centreCoutRepository->find($data['centreCout'] ?? 0);
            $statut = $statutRepository->find($data['statut'] ?? 0);

            // Définir les valeurs (le numéro n'est pas modifiable)
            $bonReparation->setAffectation($affectation);
            $bonReparation->setDateEntree(new \DateTime($data['dateEntree'] ?? 'now'));
            $bonReparation->setDiagnostic($data['diagnostic'] ?? '');
            $bonReparation->setTypeExecution((bool)($data['typeExecution'] ?? false));
            $bonReparation->setPrestataire($prestataire);
            $bonReparation->setMontantReparation((int)($data['montantReparation'] ?? 0));
            $bonReparation->setPlusOuMoinsValue($plusOuMoinsValue);
            $bonReparation->setPlusOuMoinsValueValeur((int)($data['plusOuMoinsValueValeur'] ?? 0));
            $bonReparation->setDestination($data['destination'] ?? '');
            $bonReparation->setDureeReparation((int)($data['dureeReparation'] ?? 0));
            $bonReparation->setDateJustification(new \DateTime($data['dateJustification'] ?? 'now'));
            $bonReparation->setCentreCout($centreCout);
            $bonReparation->setDatePrevueSortie(new \DateTime($data['datePrevueSortie'] ?? 'now'));

            // Gestion de la date de fin de réparation
            if (!empty($data['dateFinReparation'])) {
                $bonReparation->setDateFinReparation(new \DateTime($data['dateFinReparation']));
            } else {
                $bonReparation->setDateFinReparation(null);
            }

            $bonReparation->setObservations($data['observations'] ?? '');
            $bonReparation->setCloture((bool)($data['cloture'] ?? false));
            $bonReparation->setStatut($statut);

            // Valider l'entité
            $errors = $validator->validate($bonReparation);

            if (count($errors) === 0) {
                try {
                    $entityManager->flush();

                    // Log de l'action de modification
                    $this->auditLogger->log(
                        'update',
                        'BonReparation',
                        $bonReparation->getId(),
                        [
                            'old_data' => $oldData,
                            'new_data' => $this->getBonReparationData($bonReparation)
                        ]
                    );

                    $this->addFlash('success', 'Le bon de réparation a été modifié avec succès.');
                    return $this->redirectToRoute('app_bon_reparation_index');
                } catch (\Exception $e) {
                    $this->auditLogger->log(
                        'update', 
                        'BonReparation', 
                        $bonReparation->getId(), 
                        ['error' => $e->getMessage()], 
                        'error'
                    );
                    $this->addFlash('error', 'Une erreur est survenue lors de la modification du bon de réparation: ' . $e->getMessage());
                }
            } else {
                // Journaliser les erreurs de validation
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $this->auditLogger->log(
                    'tentative_update', 
                    'BonReparation', 
                    $bonReparation->getId(), 
                    ['errors' => $errorMessages, 'data' => $data], 
                    'error'
                );
                
                // Si il y a des erreurs, les afficher
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            }
        }

        return $this->render('bon_reparation/edit.html.twig', [
            'bon_reparation' => $bonReparation,
            'affectations' => $affectations,
            'prestataires' => $prestataires,
            'plusOuMoinsValues' => $plusOuMoinsValues,
            'centreCouts' => $centreCouts,
            'statuts' => $statuts,
            'form_data' => $request->request->all(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_bon_reparation_delete', methods: ['POST'])]
    public function delete(Request $request, BonReparation $bonReparation, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.delete')) {
            $this->auditLogger->log(
                'tentative_delete', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer un bon de réparation.');
            return $this->redirectToRoute('app_bon_reparation_index');
        }

        if ($this->isCsrfTokenValid('delete' . $bonReparation->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour le log
                $oldData = $this->getBonReparationData($bonReparation);
                
                $entityManager->remove($bonReparation);
                $entityManager->flush();

                // Log de l'action de suppression
                $this->auditLogger->log(
                    'delete',
                    'BonReparation',
                    $bonReparation->getId(),
                    ['deleted_data' => $oldData]
                );

                $this->addFlash('success', 'Le bon de réparation a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'delete', 
                    'BonReparation', 
                    $bonReparation->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du bon de réparation: ' . $e->getMessage());
            }
        } else {
            $this->auditLogger->log(
                'tentative_delete', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_bon_reparation_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/cloturer', name: 'app_bon_reparation_cloturer', methods: ['POST'])]
    public function cloturer(Request $request, BonReparation $bonReparation, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.update')) {
            $this->auditLogger->log(
                'tentative_cloture', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour clôturer un bon de réparation.');
            return $this->redirectToRoute('app_bon_reparation_show', ['id' => $bonReparation->getId()]);
        }

        if ($this->isCsrfTokenValid('cloturer' . $bonReparation->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les anciennes données pour le log
                $oldData = $this->getBonReparationData($bonReparation);
                
                $bonReparation->setCloture(true);
                $bonReparation->setDateFinReparation(new \DateTime());
                $entityManager->flush();

                // Log de l'action de clôture
                $this->auditLogger->log(
                    'cloture',
                    'BonReparation',
                    $bonReparation->getId(),
                    [
                        'old_data' => $oldData,
                        'new_data' => $this->getBonReparationData($bonReparation)
                    ]
                );

                $this->addFlash('success', 'Le bon de réparation a été clôturé avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'cloture', 
                    'BonReparation', 
                    $bonReparation->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la clôture du bon de réparation: ' . $e->getMessage());
            }
        } else {
            $this->auditLogger->log(
                'tentative_cloture', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_bon_reparation_show', ['id' => $bonReparation->getId()]);
    }

    #[Route('/{id}/reouvrir', name: 'app_bon_reparation_reouvrir', methods: ['POST'])]
    public function reouvrir(Request $request, BonReparation $bonReparation, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.update')) {
            $this->auditLogger->log(
                'tentative_reouverture', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour réouvrir un bon de réparation.');
            return $this->redirectToRoute('app_bon_reparation_show', ['id' => $bonReparation->getId()]);
        }

        if ($this->isCsrfTokenValid('reouvrir' . $bonReparation->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les anciennes données pour le log
                $oldData = $this->getBonReparationData($bonReparation);
                
                $bonReparation->setCloture(false);
                $bonReparation->setDateFinReparation(null);
                $entityManager->flush();

                // Log de l'action de réouverture
                $this->auditLogger->log(
                    'reouverture',
                    'BonReparation',
                    $bonReparation->getId(),
                    [
                        'old_data' => $oldData,
                        'new_data' => $this->getBonReparationData($bonReparation)
                    ]
                );

                $this->addFlash('success', 'Le bon de réparation a été réouvert avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'reouverture', 
                    'BonReparation', 
                    $bonReparation->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la réouverture du bon de réparation: ' . $e->getMessage());
            }
        } else {
            $this->auditLogger->log(
                'tentative_reouverture', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_bon_reparation_show', ['id' => $bonReparation->getId()]);
    }

    #[Route('/{id}', name: 'app_bon_reparation_show', methods: ['GET'])]
    public function show(BonReparation $bonReparation): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('bon_reparation.view')) {
            $this->auditLogger->log(
                'tentative_view', 
                'BonReparation', 
                $bonReparation->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails d\'un bon de réparation.');
            return $this->redirectToRoute('app_bon_reparation_index');
        }

        return $this->render('bon_reparation/show.html.twig', [
            'bon_reparation' => $bonReparation,
        ]);
    }
}