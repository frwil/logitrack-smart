<?php

namespace App\Controller;

use App\Entity\AffectationVehicule;
use App\Entity\ReleveKmsVehicule;
use App\Entity\Vehicule;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReleveKilometrageController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/releve/kilometrage', name: 'app_releve_kilometrage_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('releve_kilometrage.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux relevés de kilométrage.');
            return $this->redirectToRoute('app_homepage_index');
        }

        // Récupérer seulement les affectations non fermées
        $affectations = $entityManager->getRepository(AffectationVehicule::class)
            ->findBy(['is_ferme' => false]);

        // Récupérer tous les relevés de kilométrage, triés par affectation puis date croissante
        $releves = $entityManager->getRepository(ReleveKmsVehicule::class)
            ->findBy([], ['affectation' => 'ASC', 'dateReleve' => 'ASC']);

        // Tableau pour stocker les relevés groupés par affectation
        $relevesParAffectation = [];

        // Variables pour les statistiques
        $totalIncoherences = 0;
        $vehiculesIncoherents = [];

        // Grouper les relevés par affectation
        foreach ($releves as $releve) {
            // Ne traiter que les relevés des affectations non fermées
            if (!$releve->getAffectation()->isIsFerme()) {
                $affectationId = $releve->getAffectation()->getId();
                $relevesParAffectation[$affectationId][] = $releve;
            }
        }

        // Calculer les variations pour chaque affectation
        foreach ($relevesParAffectation as $affectationId => $relevesAffectation) {
            $previousKilometrage = null;

            foreach ($relevesAffectation as $releve) {
                $currentKilometrage = $releve->getKilometrage();

                if ($previousKilometrage !== null) {
                    // Calculer la variation par rapport au relevé précédent
                    $variation = $currentKilometrage - $previousKilometrage;
                    $releve->variation = $variation;
                    $releve->incoherent = $variation < 0;

                    // Compter les incohérences
                    if ($variation < 0) {
                        $totalIncoherences++;
                        // Marquer le véhicule comme ayant des incohérences
                        $vehiculeId = $releve->getAffectation()->getIdVehicule()->getId();
                        $vehiculesIncoherents[$vehiculeId] = true;
                    }
                } else {
                    // Premier relevé pour cette affectation
                    $releve->variation = 0;
                    $releve->incoherent = false;
                }

                $previousKilometrage = $currentKilometrage;
            }
        }

        // Compter le nombre de véhicules avec incohérences
        $nombreVehiculesIncoherents = count($vehiculesIncoherents);

        // Retrier les relevés par date décroissante pour l'affichage
        usort($releves, function ($a, $b) {
            return $b->getDateReleve() <=> $a->getDateReleve();
        });

        return $this->render('releve_kilometrage/index.html.twig', [
            'affectations' => $affectations,
            'releves' => $releves,
            'totalIncoherences' => $totalIncoherences,
            'vehiculesIncoherents' => $nombreVehiculesIncoherents,
        ]);
    }

    #[Route('/releve/kilometrage/vehicule/{id}/historique', name: 'app_releve_kilometrage_historique')]
    public function historiqueVehicule(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('releve_kilometrage.view')) {
            return new JsonResponse(['error' => 'Accès non autorisé.'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer le paramètre days (période en jours)
        $days = (int) $request->query->get('days', 30);
        
        // Validation de la période
        if ($days <= 0 || $days > 365) {
            return new JsonResponse(['error' => 'Période invalide. Doit être entre 1 et 365 jours.'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le véhicule
        $vehicule = $entityManager->getRepository(Vehicule::class)->find($id);

        if (!$vehicule) {
            return new JsonResponse(['error' => 'Véhicule non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Calculer la date de début en fonction de la période demandée
        $dateDebut = new \DateTime();
        $dateDebut->modify('-' . $days . ' days');

        // Récupérer les affectations non fermées pour ce véhicule
        $affectations = $entityManager->getRepository(AffectationVehicule::class)
            ->findBy([
                'id_vehicule' => $vehicule,
                'is_ferme' => false
            ]);

        // Récupérer tous les relevés pour ces affectations, dans la période demandée
        $releves = [];

        foreach ($affectations as $affectation) {
            $qb = $entityManager->getRepository(ReleveKmsVehicule::class)->createQueryBuilder('r');
            $qb->where('r.affectation = :affectation')
                ->andWhere('r.dateReleve >= :dateDebut')
                ->setParameter('affectation', $affectation)
                ->setParameter('dateDebut', $dateDebut)
                ->orderBy('r.dateReleve', 'ASC');

            $relevesAffectation = $qb->getQuery()->getResult();
            $releves = array_merge($releves, $relevesAffectation);
        }

        // Trier tous les relevés par date
        usort($releves, function ($a, $b) {
            return $a->getDateReleve() <=> $b->getDateReleve();
        });

        // Formater les données pour le graphique
        $labels = [];
        $data = [];

        foreach ($releves as $releve) {
            $labels[] = $releve->getDateReleve()->format('d/m/Y');
            $data[] = $releve->getKilometrage();
        }

        return new JsonResponse([
            'labels' => $labels,
            'data' => $data,
            'vehicule' => [
                'immatriculation' => $vehicule->getImmatriculationVehicule(),
                'modele' => $vehicule->getModeleVehicule() ? $vehicule->getModeleVehicule()->getNomModele() : 'N/A'
            ]
        ]);
    }

    #[Route('/releve/kilometrage/update/{id}', name: 'app_releve_kilometrage_update', methods: ['POST'])]
    public function updateReleve(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('releve_kilometrage.update')) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Vous n\'avez pas les permissions nécessaires pour modifier un relevé.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Récupérer le relevé à modifier
        $releve = $entityManager->getRepository(ReleveKmsVehicule::class)->find($id);

        if (!$releve) {
            return new JsonResponse(['success' => false, 'message' => 'Relevé non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = [
            'dateReleve' => $releve->getDateReleve()->format('Y-m-d'),
            'kilometrage' => $releve->getKilometrage(),
            'affectation_id' => $releve->getAffectation()->getId()
        ];

        // Récupérer les données du formulaire
        $dateReleveStr = $request->request->get('dateReleve');
        $kilometrage = (int) $request->request->get('kilometrage');

        // Validation des données
        if (empty($dateReleveStr)) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'UPDATE',
                'ReleveKmsVehicule',
                $id,
                [
                    'old_data' => $oldData,
                    'attempted_data' => [
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['La date de relevé est obligatoire.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'La date de relevé est obligatoire.']);
        }

        try {
            $dateReleve = new \DateTime($dateReleveStr);
        } catch (\Exception $e) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'UPDATE',
                'ReleveKmsVehicule',
                $id,
                [
                    'old_data' => $oldData,
                    'attempted_data' => [
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Format de date invalide.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Format de date invalide.']);
        }

        if ($kilometrage < 0) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'UPDATE',
                'ReleveKmsVehicule',
                $id,
                [
                    'old_data' => $oldData,
                    'attempted_data' => [
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Le kilométrage ne peut pas être négatif.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Le kilométrage ne peut pas être négatif.']);
        }

        // Vérifier la cohérence avec les autres relevés de la même affectation
        $affectation = $releve->getAffectation();
        $autresReleves = $entityManager->getRepository(ReleveKmsVehicule::class)
            ->findBy(['affectation' => $affectation], ['dateReleve' => 'ASC']);

        foreach ($autresReleves as $autreReleve) {
            // Ne pas comparer avec soi-même
            if ($autreReleve->getId() === $id) continue;

            // Vérifier si le nouveau kilométrage est inférieur à un relevé antérieur
            if ($autreReleve->getDateReleve() < $dateReleve && $autreReleve->getKilometrage() > $kilometrage) {
                $errorMessage = 'Le kilométrage ne peut pas être inférieur à un relevé antérieur (' .
                    $autreReleve->getKilometrage() . ' km le ' .
                    $autreReleve->getDateReleve()->format('d/m/Y') . ')';
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'UPDATE',
                    'ReleveKmsVehicule',
                    $id,
                    [
                        'old_data' => $oldData,
                        'attempted_data' => [
                            'dateReleve' => $dateReleveStr,
                            'kilometrage' => $kilometrage
                        ],
                        'validation_errors' => [$errorMessage]
                    ],
                    'error'
                );
                
                return new JsonResponse([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }

            // Vérifier si le nouveau kilométrage est supérieur à un relevé postérieur
            if ($autreReleve->getDateReleve() > $dateReleve && $autreReleve->getKilometrage() < $kilometrage) {
                $errorMessage = 'Le kilométrage ne peut pas être supérieur à un relevé postérieur (' .
                    $autreReleve->getKilometrage() . ' km le ' .
                    $autreReleve->getDateReleve()->format('d/m/Y') . ')';
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'UPDATE',
                    'ReleveKmsVehicule',
                    $id,
                    [
                        'old_data' => $oldData,
                        'attempted_data' => [
                            'dateReleve' => $dateReleveStr,
                            'kilometrage' => $kilometrage
                        ],
                        'validation_errors' => [$errorMessage]
                    ],
                    'error'
                );
                
                return new JsonResponse([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
        }

        // Mettre à jour le relevé
        $releve->setDateReleve($dateReleve);
        $releve->setKilometrage($kilometrage);

        try {
            $entityManager->flush();
            
            // Audit log pour la modification avec anciennes et nouvelles valeurs
            $this->auditLogger->log(
                'UPDATE',
                'ReleveKmsVehicule',
                $releve->getId(),
                [
                    'old_data' => $oldData,
                    'new_data' => [
                        'dateReleve' => $releve->getDateReleve()->format('Y-m-d'),
                        'kilometrage' => $releve->getKilometrage(),
                        'affectation_id' => $releve->getAffectation()->getId()
                    ]
                ]
            );
            
            return new JsonResponse(['success' => true, 'message' => 'Relevé mis à jour avec succès.']);
        } catch (\Exception $e) {
            // Audit log pour erreur lors de la mise à jour
            $this->auditLogger->log(
                'UPDATE',
                'ReleveKmsVehicule',
                $id,
                [
                    'old_data' => $oldData,
                    'attempted_data' => [
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'error' => $e->getMessage()
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    #[Route('/releve/kilometrage/create', name: 'app_releve_kilometrage_create', methods: ['POST'])]
    public function createReleve(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('releve_kilometrage.create')) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un relevé.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Récupérer les données du formulaire
        $affectationId = $request->request->get('affectation');
        $dateReleveStr = $request->request->get('dateReleve');
        $kilometrage = (int) $request->request->get('kilometrage');

        // Validation des données
        if (empty($affectationId) || empty($dateReleveStr) || empty($kilometrage)) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                0,
                [
                    'attempted_data' => [
                        'affectation_id' => $affectationId,
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Tous les champs sont obligatoires.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
        }

        try {
            $dateReleve = new \DateTime($dateReleveStr);
        } catch (\Exception $e) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                0,
                [
                    'attempted_data' => [
                        'affectation_id' => $affectationId,
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Format de date invalide.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Format de date invalide.']);
        }

        if ($kilometrage < 0) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                0,
                [
                    'attempted_data' => [
                        'affectation_id' => $affectationId,
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Le kilométrage ne peut pas être négatif.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Le kilométrage ne peut pas être négatif.']);
        }

        // Récupérer l'affectation
        $affectation = $entityManager->getRepository(AffectationVehicule::class)->find($affectationId);

        if (!$affectation) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                0,
                [
                    'attempted_data' => [
                        'affectation_id' => $affectationId,
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Affectation non trouvée.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Affectation non trouvée.']);
        }

        // Vérifier si l'affectation est fermée
        if ($affectation->isIsFerme()) {
            // Audit log pour échec de validation
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                0,
                [
                    'attempted_data' => [
                        'affectation_id' => $affectationId,
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'validation_errors' => ['Impossible d\'ajouter un relevé à une affectation fermée.']
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Impossible d\'ajouter un relevé à une affectation fermée.']);
        }

        // Vérifier la cohérence avec les autres relevés de la même affectation
        $autresReleves = $entityManager->getRepository(ReleveKmsVehicule::class)
            ->findBy(['affectation' => $affectation], ['dateReleve' => 'ASC']);

        foreach ($autresReleves as $releve) {
            // Vérifier si le nouveau kilométrage est inférieur à un relevé antérieur
            if ($releve->getDateReleve() < $dateReleve && $releve->getKilometrage() > $kilometrage) {
                $errorMessage = 'Le kilométrage ne peut pas être inférieur à un relevé antérieur (' .
                    $releve->getKilometrage() . ' km le ' .
                    $releve->getDateReleve()->format('d/m/Y') . ')';
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'CREATE',
                    'ReleveKmsVehicule',
                    0,
                    [
                        'attempted_data' => [
                            'affectation_id' => $affectationId,
                            'dateReleve' => $dateReleveStr,
                            'kilometrage' => $kilometrage
                        ],
                        'validation_errors' => [$errorMessage]
                    ],
                    'error'
                );
                
                return new JsonResponse([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }

            // Vérifier si le nouveau kilométrage est supérieur à un relevé postérieur
            if ($releve->getDateReleve() > $dateReleve && $releve->getKilometrage() < $kilometrage) {
                $errorMessage = 'Le kilométrage ne peut pas être supérieur à un relevé postérieur (' .
                    $releve->getKilometrage() . ' km le ' .
                    $releve->getDateReleve()->format('d/m/Y') . ')';
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'CREATE',
                    'ReleveKmsVehicule',
                    0,
                    [
                        'attempted_data' => [
                            'affectation_id' => $affectationId,
                            'dateReleve' => $dateReleveStr,
                            'kilometrage' => $kilometrage
                        ],
                        'validation_errors' => [$errorMessage]
                    ],
                    'error'
                );
                
                return new JsonResponse([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            }
        }

        // Créer le nouveau relevé
        $releve = new ReleveKmsVehicule();
        $releve->setAffectation($affectation);
        $releve->setDateReleve($dateReleve);
        $releve->setKilometrage($kilometrage);

        try {
            $entityManager->persist($releve);
            $entityManager->flush();
            
            // Audit log pour la création
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                $releve->getId(),
                [
                    'new_data' => [
                        'dateReleve' => $releve->getDateReleve()->format('Y-m-d'),
                        'kilometrage' => $releve->getKilometrage(),
                        'affectation_id' => $releve->getAffectation()->getId()
                    ]
                ]
            );
            
            return new JsonResponse(['success' => true, 'message' => 'Relevé créé avec succès.']);
        } catch (\Exception $e) {
            // Audit log pour erreur lors de la création
            $this->auditLogger->log(
                'CREATE',
                'ReleveKmsVehicule',
                0,
                [
                    'attempted_data' => [
                        'affectation_id' => $affectationId,
                        'dateReleve' => $dateReleveStr,
                        'kilometrage' => $kilometrage
                    ],
                    'error' => $e->getMessage()
                ],
                'error'
            );
            
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la création: ' . $e->getMessage()]);
        }
    }

    #[Route('/releve/kilometrage/check', name: 'app_releve_kilometrage_check', methods: ['POST'])]
    public function checkReleve(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('releve_kilometrage.create')) {
            return new JsonResponse([
                'valid' => false, 
                'message' => 'Vous n\'avez pas les permissions nécessaires pour vérifier un relevé.'
            ], Response::HTTP_FORBIDDEN);
        }

        $affectationId = $request->request->get('affectation');
        $kilometrage = (int) $request->request->get('kilometrage');

        // Validation des données
        if (empty($affectationId) || empty($kilometrage)) {
            return new JsonResponse(['valid' => false, 'message' => 'Données de vérification incomplètes.']);
        }

        // Récupérer l'affectation
        $affectation = $entityManager->getRepository(AffectationVehicule::class)->find($affectationId);

        if (!$affectation) {
            return new JsonResponse(['valid' => false, 'message' => 'Affectation non trouvée.']);
        }

        // Récupérer le dernier relevé pour cette affectation
        $dernierReleve = $entityManager->getRepository(ReleveKmsVehicule::class)
            ->findOneBy(['affectation' => $affectation], ['dateReleve' => 'DESC']);

        if ($dernierReleve && $kilometrage < $dernierReleve->getKilometrage()) {
            return new JsonResponse([
                'valid' => false,
                'message' => 'Attention : Ce kilométrage est inférieur au dernier relevé (' .
                    $dernierReleve->getKilometrage() . ' km le ' .
                    $dernierReleve->getDateReleve()->format('d/m/Y') . ')'
            ]);
        }

        return new JsonResponse(['valid' => true]);
    }

    #[Route('/releve/kilometrage/fix-incoherences', name: 'app_releve_kilometrage_fix_incoherences', methods: ['POST'])]
    public function fixIncoherences(EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission (ROLE_SUPER_ADMIN)
        if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
            return new JsonResponse([
                'success' => false, 
                'message' => 'Vous n\'avez pas les permissions nécessaires pour corriger les incohérences.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Récupérer tous les relevés de kilométrage, triés par affectation puis date croissante
        $releves = $entityManager->getRepository(ReleveKmsVehicule::class)
            ->findBy([], ['affectation' => 'ASC', 'dateReleve' => 'ASC']);

        $corrections = 0;
        $relevesParAffectation = [];

        // Grouper les relevés par affectation
        foreach ($releves as $releve) {
            $affectationId = $releve->getAffectation()->getId();
            $relevesParAffectation[$affectationId][] = $releve;
        }

        // Parcourir chaque affectation
        foreach ($relevesParAffectation as $affectationId => $relevesAffectation) {
            $previousKilometrage = null;

            foreach ($relevesAffectation as $releve) {
                $currentKilometrage = $releve->getKilometrage();

                if ($previousKilometrage !== null && $currentKilometrage < $previousKilometrage) {
                    // Sauvegarder les anciennes valeurs avant correction
                    $oldData = [
                        'dateReleve' => $releve->getDateReleve()->format('Y-m-d'),
                        'kilometrage' => $releve->getKilometrage(),
                        'affectation_id' => $releve->getAffectation()->getId()
                    ];
                    
                    // Corriger l'incohérence en utilisant la valeur précédente
                    $releve->setKilometrage($previousKilometrage);
                    $corrections++;
                    
                    // Audit log pour chaque correction
                    $this->auditLogger->log(
                        'CORRECT_INCOHERENCE',
                        'ReleveKmsVehicule',
                        $releve->getId(),
                        [
                            'old_data' => $oldData,
                            'new_data' => [
                                'dateReleve' => $releve->getDateReleve()->format('Y-m-d'),
                                'kilometrage' => $releve->getKilometrage(),
                                'affectation_id' => $releve->getAffectation()->getId()
                            ]
                        ]
                    );
                }

                $previousKilometrage = $releve->getKilometrage();
            }
        }

        if ($corrections > 0) {
            try {
                $entityManager->flush();
                return new JsonResponse([
                    'success' => true,
                    'message' => $corrections . ' incohérence(s) corrigée(s) avec succès.'
                ]);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Erreur lors de la correction: ' . $e->getMessage()
                ]);
            }
        } else {
            return new JsonResponse([
                'success' => true,
                'message' => 'Aucune incohérence à corriger.'
            ]);
        }
    }
}