<?php

namespace App\Controller;

use App\Entity\Region;
use App\Repository\RegionRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/region')]
class RegionController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_region_index', methods: ['GET'])]
    public function index(RegionRepository $regionRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('region.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des régions.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('region/index.html.twig', [
            'regions' => $regionRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_region_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('region.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer une région.');
            return $this->redirectToRoute('app_region_index');
        }

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            
            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom de la région est obligatoire.');
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'CREATE',
                    'Region',
                    0,
                    [
                        'attempted_data' => ['nom' => $nom],
                        'validation_errors' => ['Le nom de la région est obligatoire.']
                    ],
                    'error'
                );
                
                if ($request->request->get('ajax')) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Le nom de la région est obligatoire.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                return $this->render('region/new.html.twig');
            }
            
            // Vérifier si une région avec le même nom existe déjà
            $existingRegion = $entityManager->getRepository(Region::class)->findOneBy(['nom' => $nom]);
            if ($existingRegion) {
                $this->addFlash('error', 'Une région avec ce nom existe déjà.');
                
                // Audit log pour duplication
                $this->auditLogger->log(
                    'CREATE',
                    'Region',
                    0,
                    [
                        'attempted_data' => ['nom' => $nom],
                        'validation_errors' => ['Une région avec ce nom existe déjà.']
                    ],
                    'error'
                );
                
                if ($request->request->get('ajax')) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Une région avec ce nom existe déjà.'
                    ], Response::HTTP_BAD_REQUEST);
                }
                
                return $this->render('region/new.html.twig');
            }

            $region = new Region();
            $region->setNom($nom);

            try {
                $entityManager->persist($region);
                $entityManager->flush();
                
                // Audit log pour la création
                $this->auditLogger->log(
                    'CREATE',
                    'Region',
                    $region->getId(),
                    ['new_data' => ['nom' => $region->getNom()]]
                );
                
                $this->addFlash('success', 'La région a été créée avec succès.');

                if ($request->request->get('ajax')) {
                    return $this->json([
                        'success' => true,
                        'id' => $region->getId(),
                        'nom' => $region->getNom()
                    ]);
                }

                return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la création
                $this->auditLogger->log(
                    'CREATE',
                    'Region',
                    0,
                    [
                        'attempted_data' => ['nom' => $nom],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la région.');
                
                if ($request->request->get('ajax')) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Une erreur est survenue lors de la création de la région.'
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
                
                return $this->render('region/new.html.twig');
            }
        }

        return $this->render('region/new.html.twig');
    }

    #[Route('/{id}', name: 'app_region_show', methods: ['GET'])]
    public function show(Region $region): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('region.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de cette région.');
            return $this->redirectToRoute('app_region_index');
        }

        return $this->render('region/show.html.twig', [
            'region' => $region,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_region_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Region $region, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('region.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier cette région.');
            return $this->redirectToRoute('app_region_index');
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = [
            'nom' => $region->getNom()
        ];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            
            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom de la région est obligatoire.');
                
                // Audit log pour échec de validation
                $this->auditLogger->log(
                    'UPDATE',
                    'Region',
                    $region->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => ['nom' => $nom],
                        'validation_errors' => ['Le nom de la région est obligatoire.']
                    ],
                    'error'
                );
                
                return $this->render('region/edit.html.twig', [
                    'region' => $region,
                ]);
            }
            
            // Vérifier si une autre région avec le même nom existe déjà
            $existingRegion = $entityManager->getRepository(Region::class)->findOneBy(['nom' => $nom]);
            if ($existingRegion && $existingRegion->getId() !== $region->getId()) {
                $this->addFlash('error', 'Une autre région avec ce nom existe déjà.');
                
                // Audit log pour duplication
                $this->auditLogger->log(
                    'UPDATE',
                    'Region',
                    $region->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => ['nom' => $nom],
                        'validation_errors' => ['Une autre région avec ce nom existe déjà.']
                    ],
                    'error'
                );
                
                return $this->render('region/edit.html.twig', [
                    'region' => $region,
                ]);
            }

            $region->setNom($nom);

            try {
                $entityManager->flush();
                
                // Audit log pour la modification avec anciennes et nouvelles valeurs
                $this->auditLogger->log(
                    'UPDATE',
                    'Region',
                    $region->getId(),
                    [
                        'old_data' => $oldData,
                        'new_data' => ['nom' => $region->getNom()]
                    ]
                );
                
                $this->addFlash('success', 'La région a été modifiée avec succès.');
                return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la modification
                $this->auditLogger->log(
                    'UPDATE',
                    'Region',
                    $region->getId(),
                    [
                        'old_data' => $oldData,
                        'attempted_data' => ['nom' => $nom],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la région.');
                return $this->render('region/edit.html.twig', [
                    'region' => $region,
                ]);
            }
        }

        return $this->render('region/edit.html.twig', [
            'region' => $region,
        ]);
    }

    #[Route('/{id}', name: 'app_region_delete', methods: ['POST'])]
    public function delete(Request $request, Region $region, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('region.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer cette région.');
            return $this->redirectToRoute('app_region_index');
        }

        if ($this->isCsrfTokenValid('delete' . $region->getId(), $request->request->get('_token'))) {
            try {
                // Vérifier si la région est utilisée ailleurs avant de supprimer
                if ($region->getAffectationVehicules()->count() > 0 || $region->getDestinationVoyages()->count() > 0) {
                    $this->addFlash('error', 'Cette région ne peut pas être supprimée car elle est utilisée par des affectations ou des trajets.');
                    
                    // Audit log pour tentative de suppression d'une région utilisée
                    $this->auditLogger->log(
                        'DELETE',
                        'Region',
                        $region->getId(),
                        [
                            'deleted_data' => ['nom' => $region->getNom()],
                            'error' => 'Région utilisée par des affectations ou des trajets'
                        ],
                        'error'
                    );
                    
                    return $this->redirectToRoute('app_region_show', ['id' => $region->getId()]);
                }
               
                // Sauvegarder les données avant suppression pour l'audit
                $deletedData = [
                    'nom' => $region->getNom()
                ];
                
                $entityManager->remove($region);
                $entityManager->flush();
                
                // Audit log pour la suppression
                $this->auditLogger->log(
                    'DELETE',
                    'Region',
                    $region->getId(),
                    ['deleted_data' => $deletedData]
                );
                
                $this->addFlash('success', 'La région a été supprimée avec succès.');
            } catch (\Exception $e) {
                // Audit log pour erreur lors de la suppression
                $this->auditLogger->log(
                    'DELETE',
                    'Region',
                    $region->getId(),
                    [
                        'deleted_data' => ['nom' => $region->getNom()],
                        'error' => $e->getMessage()
                    ],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la région.');
            }
        } else {
            // Audit log pour token CSRF invalide
            $this->auditLogger->log(
                'DELETE',
                'Region',
                $region->getId(),
                [
                    'error' => 'Jeton CSRF invalide'
                ],
                'error'
            );
            
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_region_index', [], Response::HTTP_SEE_OTHER);
    }
}