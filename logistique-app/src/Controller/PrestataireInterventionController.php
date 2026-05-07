<?php
// src/Controller/PrestataireInterventionController.php

namespace App\Controller;

use App\Entity\PrestataireIntervention;
use App\Repository\PrestataireInterventionRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/prestataire/intervention')]
class PrestataireInterventionController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_prestataire_intervention_index', methods: ['GET'])]
    public function index(PrestataireInterventionRepository $prestataireInterventionRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des prestataires.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('prestataire_intervention/index.html.twig', [
            'prestataire_interventions' => $prestataireInterventionRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_prestataire_intervention_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un prestataire.');
            return $this->redirectToRoute('app_prestataire_intervention_index');
        }

        $prestataireIntervention = new PrestataireIntervention();

        if ($request->isMethod('POST')) {
            // Récupérer et nettoyer les données du formulaire
            $nom = trim($request->request->get('nom', ''));
            $contact = trim($request->request->get('contact', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $email = trim($request->request->get('email', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $specialites = trim($request->request->get('specialites', ''));
            $actif = (bool)$request->request->get('actif', true);

            // Validation des données obligatoires
            $errors = [];
            if (empty($nom)) {
                $errors[] = 'Le nom est obligatoire.';
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'adresse email n\'est pas valide.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'CREATE',
                    'PrestataireIntervention',
                    0,
                    [
                        'attempted_data' => [
                            'nom' => $nom,
                            'contact' => $contact,
                            'telephone' => $telephone,
                            'email' => $email,
                            'adresse' => $adresse,
                            'specialites' => $specialites,
                            'actif' => $actif
                        ],
                        'validation_errors' => $errors
                    ],
                    'error'
                );
                
                return $this->render('prestataire_intervention/new.html.twig', [
                    'nom' => $nom,
                    'contact' => $contact,
                    'telephone' => $telephone,
                    'email' => $email,
                    'adresse' => $adresse,
                    'specialites' => $specialites,
                    'actif' => $actif,
                ]);
            }

            // Définir les valeurs
            $prestataireIntervention->setNom($nom);
            $prestataireIntervention->setContact($contact);
            $prestataireIntervention->setTelephone($telephone);
            $prestataireIntervention->setEmail($email);
            $prestataireIntervention->setAdresse($adresse);
            $prestataireIntervention->setSpecialites($specialites);
            $prestataireIntervention->setActif($actif);

            // Valider l'entité
            $validationErrors = $validator->validate($prestataireIntervention);

            if (count($validationErrors) === 0) {
                try {
                    $entityManager->persist($prestataireIntervention);
                    $entityManager->flush();

                    // Audit log pour la création
                    $this->auditLogger->log(
                        'CREATE',
                        'PrestataireIntervention',
                        $prestataireIntervention->getId(),
                        [
                            'new_data' => [
                                'nom' => $prestataireIntervention->getNom(),
                                'contact' => $prestataireIntervention->getContact(),
                                'telephone' => $prestataireIntervention->getTelephone(),
                                'email' => $prestataireIntervention->getEmail(),
                                'adresse' => $prestataireIntervention->getAdresse(),
                                'specialites' => $prestataireIntervention->getSpecialites(),
                                'actif' => $prestataireIntervention->isActif()
                            ]
                        ]
                    );

                    $this->addFlash('success', 'Le prestataire a été créé avec succès.');
                    return $this->redirectToRoute('app_prestataire_intervention_index');
                } catch (\Exception $e) {
                    // Audit log pour erreur lors de la création
                    $this->auditLogger->log(
                        'CREATE',
                        'PrestataireIntervention',
                        0,
                        [
                            'attempted_data' => [
                                'nom' => $nom,
                                'contact' => $contact,
                                'telephone' => $telephone,
                                'email' => $email,
                                'adresse' => $adresse,
                                'specialites' => $specialites,
                                'actif' => $actif
                            ],
                            'error' => $e->getMessage()
                        ],
                        'error'
                    );
                    
                    $this->addFlash('error', 'Une erreur est survenue lors de la création du prestataire.');
                }
            } else {
                // Afficher les erreurs de validation
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                
                // Audit log pour erreur de validation
                $errorMessages = [];
                foreach ($validationErrors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $this->auditLogger->log(
                    'CREATE',
                    'PrestataireIntervention',
                    0,
                    [
                        'attempted_data' => [
                            'nom' => $nom,
                            'contact' => $contact,
                            'telephone' => $telephone,
                            'email' => $email,
                            'adresse' => $adresse,
                            'specialites' => $specialites,
                            'actif' => $actif
                        ],
                        'validation_errors' => $errorMessages
                    ],
                    'error'
                );
            }
        }

        return $this->render('prestataire_intervention/new.html.twig', [
            'nom' => '',
            'contact' => '',
            'telephone' => '',
            'email' => '',
            'adresse' => '',
            'specialites' => '',
            'actif' => true,
        ]);
    }

    #[Route('/ajax-list', name: 'app_prestataire_intervention_list', methods: ['GET'])]
    public function ajaxList(PrestataireInterventionRepository $repository): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.view')) {
            return new JsonResponse(['error' => 'Accès non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        $prestataires = $repository->findBy(['actif' => true], ['nom' => 'ASC']);

        $data = [];
        foreach ($prestataires as $prestataire) {
            $data[] = [
                'id' => $prestataire->getId(),
                'nom' => $prestataire->getNom()
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/ajax-new', name: 'app_prestataire_intervention_ajax_new', methods: ['POST'])]
    public function ajaxNew(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.create')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un prestataire.'
            ], Response::HTTP_FORBIDDEN);
        }

        $prestataireIntervention = new PrestataireIntervention();

        // Récupérer les données du formulaire
        $nom = trim($request->request->get('nom', ''));
        $contact = trim($request->request->get('contact', ''));
        $telephone = trim($request->request->get('telephone', ''));
        $email = trim($request->request->get('email', ''));
        $adresse = trim($request->request->get('adresse', ''));
        $specialites = trim($request->request->get('specialites', ''));

        // Validation des données
        $errors = [];
        if (empty($nom)) {
            $errors[] = 'Le nom est obligatoire.';
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L\'adresse email n\'est pas valide.';
        }

        if (!empty($errors)) {
            // Audit log pour échec de validation AJAX
            $this->auditLogger->log(
                'CREATE',
                'PrestataireIntervention',
                0,
                [
                    'attempted_data' => [
                        'nom' => $nom,
                        'contact' => $contact,
                        'telephone' => $telephone,
                        'email' => $email,
                        'adresse' => $adresse,
                        'specialites' => $specialites
                    ],
                    'validation_errors' => $errors
                ],
                'error'
            );
            
            return new JsonResponse([
                'success' => false,
                'message' => implode(' ', $errors)
            ]);
        }

        // Définir les valeurs
        $prestataireIntervention->setNom($nom);
        $prestataireIntervention->setContact($contact);
        $prestataireIntervention->setTelephone($telephone);
        $prestataireIntervention->setEmail($email);
        $prestataireIntervention->setAdresse($adresse);
        $prestataireIntervention->setSpecialites($specialites);
        $prestataireIntervention->setActif(true);

        // Valider l'entité
        $validationErrors = $validator->validate($prestataireIntervention);

        if (count($validationErrors) === 0) {
            try {
                $entityManager->persist($prestataireIntervention);
                $entityManager->flush();

                // Audit log pour la création via AJAX
                $this->auditLogger->log(
                    'CREATE',
                    'PrestataireIntervention',
                    $prestataireIntervention->getId(),
                    [
                        'new_data' => [
                            'nom' => $prestataireIntervention->getNom(),
                            'contact' => $prestataireIntervention->getContact(),
                            'telephone' => $prestataireIntervention->getTelephone(),
                            'email' => $prestataireIntervention->getEmail(),
                            'adresse' => $prestataireIntervention->getAdresse(),
                            'specialites' => $prestataireIntervention->getSpecialites(),
                            'actif' => $prestataireIntervention->isActif()
                        ]
                    ]
                );

                return new JsonResponse([
                    'success' => true,
                    'prestataire' => [
                        'id' => $prestataireIntervention->getId(),
                        'nom' => $prestataireIntervention->getNom()
                    ]
                ]);
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la création AJAX
                $this->auditLogger->log(
                    'CREATE',
                    'PrestataireIntervention',
                    0,
                    [
                        'attempted_data' => [
                            'nom' => $nom,
                            'contact' => $contact,
                            'telephone' => $telephone,
                            'email' => $email,
                            'adresse' => $adresse,
                            'specialites' => $specialites
                        ],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de la création du prestataire.'
                ]);
            }
        }

        // Si il y a des erreurs, les retourner
        $errorMessages = [];
        foreach ($validationErrors as $error) {
            $errorMessages[] = $error->getMessage();
        }

        // Audit log pour erreur de validation AJAX
        $this->auditLogger->log(
            'CREATE',
            'PrestataireIntervention',
            0,
            [
                'attempted_data' => [
                    'nom' => $nom,
                    'contact' => $contact,
                    'telephone' => $telephone,
                    'email' => $email,
                    'adresse' => $adresse,
                    'specialites' => $specialites
                ],
                'validation_errors' => $errorMessages
            ],
            'error'
        );

        return new JsonResponse([
            'success' => false,
            'message' => implode(', ', $errorMessages)
        ]);
    }

    #[Route('/{id}', name: 'app_prestataire_intervention_show', methods: ['GET'])]
    public function show(PrestataireIntervention $prestataireIntervention): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce prestataire.');
            return $this->redirectToRoute('app_prestataire_intervention_index');
        }

        return $this->render('prestataire_intervention/show.html.twig', [
            'prestataire_intervention' => $prestataireIntervention,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_prestataire_intervention_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PrestataireIntervention $prestataireIntervention, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce prestataire.');
            return $this->redirectToRoute('app_prestataire_intervention_index');
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = [
            'nom' => $prestataireIntervention->getNom(),
            'contact' => $prestataireIntervention->getContact(),
            'telephone' => $prestataireIntervention->getTelephone(),
            'email' => $prestataireIntervention->getEmail(),
            'adresse' => $prestataireIntervention->getAdresse(),
            'specialites' => $prestataireIntervention->getSpecialites(),
            'actif' => $prestataireIntervention->isActif()
        ];

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $nom = trim($request->request->get('nom', ''));
            $contact = trim($request->request->get('contact', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $email = trim($request->request->get('email', ''));
            $adresse = trim($request->request->get('adresse', ''));
            $specialites = trim($request->request->get('specialites', ''));
            $actif = (bool)$request->request->get('actif', false);

            // Validation des données obligatoires
            $errors = [];
            if (empty($nom)) {
                $errors[] = 'Le nom est obligatoire.';
            }

            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'adresse email n\'est pas valide.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'UPDATE',
                    'PrestataireIntervention',
                    $prestataireIntervention->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => [
                            'nom' => $nom,
                            'contact' => $contact,
                            'telephone' => $telephone,
                            'email' => $email,
                            'adresse' => $adresse,
                            'specialites' => $specialites,
                            'actif' => $actif
                        ],
                        'validation_errors' => $errors
                    ],
                    'error'
                );
                
                return $this->render('prestataire_intervention/edit.html.twig', [
                    'prestataire_intervention' => $prestataireIntervention,
                    'nom' => $nom,
                    'contact' => $contact,
                    'telephone' => $telephone,
                    'email' => $email,
                    'adresse' => $adresse,
                    'specialites' => $specialites,
                    'actif' => $actif,
                ]);
            }

            // Définir les valeurs
            $prestataireIntervention->setNom($nom);
            $prestataireIntervention->setContact($contact);
            $prestataireIntervention->setTelephone($telephone);
            $prestataireIntervention->setEmail($email);
            $prestataireIntervention->setAdresse($adresse);
            $prestataireIntervention->setSpecialites($specialites);
            $prestataireIntervention->setActif($actif);

            // Valider l'entité
            $validationErrors = $validator->validate($prestataireIntervention);

            if (count($validationErrors) === 0) {
                try {
                    $entityManager->flush();

                    // Audit log pour la modification avec anciennes et nouvelles valeurs
                    $this->auditLogger->log(
                        'UPDATE',
                        'PrestataireIntervention',
                        $prestataireIntervention->getId(),
                        [
                            'old_data' => $oldData,
                            'new_data' => [
                                'nom' => $prestataireIntervention->getNom(),
                                'contact' => $prestataireIntervention->getContact(),
                                'telephone' => $prestataireIntervention->getTelephone(),
                                'email' => $prestataireIntervention->getEmail(),
                                'adresse' => $prestataireIntervention->getAdresse(),
                                'specialites' => $prestataireIntervention->getSpecialites(),
                                'actif' => $prestataireIntervention->isActif()
                            ]
                        ]
                    );

                    $this->addFlash('success', 'Le prestataire a été modifié avec succès.');
                    return $this->redirectToRoute('app_prestataire_intervention_index');
                } catch (\Exception $e) {
                    // Audit log pour erreur lors de la modification
                    $this->auditLogger->log(
                        'UPDATE',
                        'PrestataireIntervention',
                        $prestataireIntervention->getId(),
                        [
                            'old_data' => $oldData,
                            'attempted_data' => [
                                'nom' => $nom,
                                'contact' => $contact,
                                'telephone' => $telephone,
                                'email' => $email,
                                'adresse' => $adresse,
                                'specialites' => $specialites,
                                'actif' => $actif
                            ],
                            'error' => $e->getMessage()
                        ],
                        'error'
                    );
                    
                    $this->addFlash('error', 'Une erreur est survenue lors de la modification du prestataire.');
                }
            } else {
                // Afficher les erreurs de validation
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                
                // Audit log pour erreur de validation
                $errorMessages = [];
                foreach ($validationErrors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                $this->auditLogger->log(
                    'UPDATE',
                    'PrestataireIntervention',
                    $prestataireIntervention->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => [
                            'nom' => $nom,
                            'contact' => $contact,
                            'telephone' => $telephone,
                            'email' => $email,
                            'adresse' => $adresse,
                            'specialites' => $specialites,
                            'actif' => $actif
                        ],
                        'validation_errors' => $errorMessages
                    ],
                    'error'
                );
            }
        }

        return $this->render('prestataire_intervention/edit.html.twig', [
            'prestataire_intervention' => $prestataireIntervention,
            'nom' => $prestataireIntervention->getNom(),
            'contact' => $prestataireIntervention->getContact(),
            'telephone' => $prestataireIntervention->getTelephone(),
            'email' => $prestataireIntervention->getEmail(),
            'adresse' => $prestataireIntervention->getAdresse(),
            'specialites' => $prestataireIntervention->getSpecialites(),
            'actif' => $prestataireIntervention->isActif(),
        ]);
    }

    #[Route('/{id}', name: 'app_prestataire_intervention_delete', methods: ['POST'])]
    public function delete(Request $request, PrestataireIntervention $prestataireIntervention, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('prestataire_intervention.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce prestataire.');
            return $this->redirectToRoute('app_prestataire_intervention_index');
        }

        // Vérifier si le prestataire peut être supprimé
        if ($prestataireIntervention->getBonReparations()->count() > 0 || $prestataireIntervention->getVidanges()->count() > 0) {
            $this->addFlash('error', 'Ce prestataire ne peut pas être supprimé car il est associé à des interventions.');
            return $this->redirectToRoute('app_prestataire_intervention_show', ['id' => $prestataireIntervention->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $prestataireIntervention->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour l'audit
                $deletedData = [
                    'nom' => $prestataireIntervention->getNom(),
                    'contact' => $prestataireIntervention->getContact(),
                    'telephone' => $prestataireIntervention->getTelephone(),
                    'email' => $prestataireIntervention->getEmail(),
                    'adresse' => $prestataireIntervention->getAdresse(),
                    'specialites' => $prestataireIntervention->getSpecialites(),
                    'actif' => $prestataireIntervention->isActif()
                ];
                
                $entityManager->remove($prestataireIntervention);
                $entityManager->flush();

                // Audit log pour la suppression
                $this->auditLogger->log(
                    'DELETE',
                    'PrestataireIntervention',
                    $prestataireIntervention->getId(),
                    ['deleted_data' => $deletedData]
                );

                $this->addFlash('success', 'Le prestataire a été supprimé avec succès.');
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la suppression
                $this->auditLogger->log(
                    'DELETE',
                    'PrestataireIntervention',
                    $prestataireIntervention->getId(),
                    [
                        'deleted_data' => [
                            'nom' => $prestataireIntervention->getNom(),
                            'contact' => $prestataireIntervention->getContact(),
                            'telephone' => $prestataireIntervention->getTelephone(),
                            'email' => $prestataireIntervention->getEmail(),
                            'adresse' => $prestataireIntervention->getAdresse(),
                            'specialites' => $prestataireIntervention->getSpecialites(),
                            'actif' => $prestataireIntervention->isActif()
                        ],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du prestataire.');
            }
        } else {
            // Audit log pour token CSRF invalide
            $this->auditLogger->log(
                'DELETE',
                'PrestataireIntervention',
                $prestataireIntervention->getId(),
                [
                    'error' => 'Jeton CSRF invalide'
                ],
                'error'
            );
            
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_prestataire_intervention_index', [], Response::HTTP_SEE_OTHER);
    }
}