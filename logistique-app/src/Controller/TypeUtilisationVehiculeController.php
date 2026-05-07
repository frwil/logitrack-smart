<?php

namespace App\Controller;

use App\Entity\TypeUtilisationVehicule;
use App\Repository\TypeUtilisationVehiculeRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/type/utilisation/vehicule')]
class TypeUtilisationVehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_type_utilisation_vehicule_index', methods: ['GET'])]
    public function index(TypeUtilisationVehiculeRepository $typeUtilisationVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_utilisation_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des types d\'utilisation de véhicule.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('type_utilisation_vehicule/index.html.twig', [
            'type_utilisation_vehicules' => $typeUtilisationVehiculeRepository->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_type_utilisation_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_utilisation_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un type d\'utilisation de véhicule.');
            return $this->redirectToRoute('app_type_utilisation_vehicule_index');
        }

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            
            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom est obligatoire.');
                return $this->render('type_utilisation_vehicule/new.html.twig');
            }
            
            // Vérifier l'unicité du nom
            $existingType = $entityManager->getRepository(TypeUtilisationVehicule::class)
                ->findOneBy(['nom' => $nom]);
            
            if ($existingType) {
                $this->addFlash('error', 'Un type d\'utilisation avec ce nom existe déjà.');
                return $this->render('type_utilisation_vehicule/new.html.twig');
            }
            
            $typeUtilisationVehicule = new TypeUtilisationVehicule();
            $typeUtilisationVehicule->setNom($nom);
            
            try {
                $entityManager->persist($typeUtilisationVehicule);
                $entityManager->flush();
                
                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    TypeUtilisationVehicule::class,
                    $typeUtilisationVehicule->getId(),
                    ['new' => ['nom' => $nom]],
                    'success'
                );
                
                $this->addFlash('success', 'Type d\'utilisation créé avec succès.');
                return $this->redirectToRoute('app_type_utilisation_vehicule_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Log de l'échec de création
                $this->auditLogger->log(
                    'create',
                    TypeUtilisationVehicule::class,
                    0,
                    ['attempted_data' => ['nom' => $nom], 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création du type d\'utilisation.');
                return $this->render('type_utilisation_vehicule/new.html.twig');
            }
        }

        return $this->render('type_utilisation_vehicule/new.html.twig');
    }

    #[Route('/show/{id}', name: 'app_type_utilisation_vehicule_show', methods: ['GET'])]
    public function show(TypeUtilisationVehicule $typeUtilisationVehicule): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_utilisation_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce type d\'utilisation.');
            return $this->redirectToRoute('app_type_utilisation_vehicule_index');
        }

        return $this->render('type_utilisation_vehicule/show.html.twig', [
            'type_utilisation_vehicule' => $typeUtilisationVehicule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_utilisation_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeUtilisationVehicule $typeUtilisationVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_utilisation_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce type d\'utilisation.');
            return $this->redirectToRoute('app_type_utilisation_vehicule_index');
        }

        // Sauvegarde des anciennes valeurs pour le log
        $oldData = [
            'nom' => $typeUtilisationVehicule->getNom(),
        ];

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            
            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom est obligatoire.');
                return $this->render('type_utilisation_vehicule/edit.html.twig', [
                    'type_utilisation_vehicule' => $typeUtilisationVehicule,
                ]);
            }
            
            // Vérifier l'unicité du nom (en excluant l'enregistrement actuel)
            $existingType = $entityManager->getRepository(TypeUtilisationVehicule::class)
                ->findOneBy(['nom' => $nom]);
            
            if ($existingType && $existingType->getId() !== $typeUtilisationVehicule->getId()) {
                $this->addFlash('error', 'Un autre type d\'utilisation avec ce nom existe déjà.');
                return $this->render('type_utilisation_vehicule/edit.html.twig', [
                    'type_utilisation_vehicule' => $typeUtilisationVehicule,
                ]);
            }
            
            // Nouvelles valeurs
            $newData = ['nom' => $nom];
            
            $typeUtilisationVehicule->setNom($nom);
            
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
                        TypeUtilisationVehicule::class,
                        $typeUtilisationVehicule->getId(),
                        $changes,
                        'success'
                    );
                }
                
                $this->addFlash('success', 'Type d\'utilisation modifié avec succès.');
                return $this->redirectToRoute('app_type_utilisation_vehicule_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Log de l'échec de modification
                $this->auditLogger->log(
                    'update',
                    TypeUtilisationVehicule::class,
                    $typeUtilisationVehicule->getId(),
                    ['attempted_data' => $newData, 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du type d\'utilisation.');
                return $this->render('type_utilisation_vehicule/edit.html.twig', [
                    'type_utilisation_vehicule' => $typeUtilisationVehicule,
                ]);
            }
        }

        return $this->render('type_utilisation_vehicule/edit.html.twig', [
            'type_utilisation_vehicule' => $typeUtilisationVehicule,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_type_utilisation_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, TypeUtilisationVehicule $typeUtilisationVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_utilisation_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce type d\'utilisation.');
            return $this->redirectToRoute('app_type_utilisation_vehicule_index');
        }

        if ($this->isCsrfTokenValid('delete'.$typeUtilisationVehicule->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des affectations associées avant de supprimer
            if ($typeUtilisationVehicule->getAffectationVehicules()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce type d\'utilisation car il est utilisé dans des affectations de véhicules.');
                return $this->redirectToRoute('app_type_utilisation_vehicule_show', ['id' => $typeUtilisationVehicule->getId()]);
            }
            
            try {
                // Log de l'action de suppression avec les données de l'entité
                $this->auditLogger->log(
                    'delete',
                    TypeUtilisationVehicule::class,
                    $typeUtilisationVehicule->getId(),
                    ['old' => ['nom' => $typeUtilisationVehicule->getNom()]],
                    'success'
                );
                
                $entityManager->remove($typeUtilisationVehicule);
                $entityManager->flush();
                
                $this->addFlash('success', 'Type d\'utilisation supprimé avec succès.');
            } catch (\Exception $e) {
                // Log de l'échec de suppression
                $this->auditLogger->log(
                    'delete',
                    TypeUtilisationVehicule::class,
                    $typeUtilisationVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du type d\'utilisation.');
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_type_utilisation_vehicule_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/list', name: 'app_type_utilisation_vehicule_list', methods: ['GET'])]
    public function typeUtilisationIndex(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('type_utilisation_vehicule.view')) {
            return new JsonResponse([
                'error' => 'Accès non autorisé.'
            ], Response::HTTP_FORBIDDEN);
        }

        $types = $entityManager->getRepository(TypeUtilisationVehicule::class)->findAll();
        $data = [];
        foreach ($types as $type) {
            $data[] = [
                'id' => $type->getId(),
                'nom' => $type->getNom(),
            ];
        }
        return $this->json($data);
    }
}