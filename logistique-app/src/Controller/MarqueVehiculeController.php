<?php

namespace App\Controller;

use App\Entity\MarqueVehicule;
use App\Repository\MarqueVehiculeRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/marques')]
class MarqueVehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    private function getMarqueVehiculeData(MarqueVehicule $marqueVehicule): array
    {
        return [
            'nomMarque' => $marqueVehicule->getNomMarque()
        ];
    }

    #[Route('/', name: 'app_marque_vehicule_index', methods: ['GET'])]
    public function index(MarqueVehiculeRepository $marqueVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('marque_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des marques de véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('marque_vehicule/index.html.twig', [
            'marques' => $marqueVehiculeRepository->findBy([], ['nomMarque' => 'ASC']),
        ]);
    }

    // Route pour obtenir toutes les marques au format JSON
    #[Route('/json', name: 'app_get_all_marques_vehicules', methods: ['GET'])]
    public function getAllMarquesAsJson(MarqueVehiculeRepository $marqueVehiculeRepository): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('marque_vehicule.view')) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        // Récupère toutes les entités MarqueVehicule de la base de données
        $marques = $marqueVehiculeRepository->findBy([], ['nomMarque' => 'ASC']);

        // Renvoie un tableau d'objets JSON contenant les noms et les IDs des marques
        $data = [];
        foreach ($marques as $marque) {
            $data[] = [
                'id' => $marque->getId(),
                'textContent' => $marque->getNomMarque(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/new', name: 'app_marque_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('marque_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer une marque de véhicule.');
            return $this->redirectToRoute('app_marque_vehicule_index');
        }

        if ($request->isMethod('POST')) {
            $nomMarque = trim($request->request->get('nom_marque', ''));
            
            // Validation des données
            if (empty($nomMarque)) {
                $this->addFlash('error', 'Le nom de la marque est obligatoire.');
                return $this->render('marque_vehicule/new.html.twig', [
                    'nom_marque' => $nomMarque,
                ]);
            }
            
            if (mb_strlen($nomMarque) > 100) {
                $this->addFlash('error', 'Le nom de la marque ne doit pas dépasser 100 caractères.');
                return $this->render('marque_vehicule/new.html.twig', [
                    'nom_marque' => $nomMarque,
                ]);
            }
            
            // Vérifier si une marque avec le même nom existe déjà
            $existingMarque = $entityManager->getRepository(MarqueVehicule::class)->findOneBy(['nomMarque' => $nomMarque]);
            if ($existingMarque) {
                $this->addFlash('error', 'Une marque avec ce nom existe déjà.');
                return $this->render('marque_vehicule/new.html.twig', [
                    'nom_marque' => $nomMarque,
                ]);
            }

            $marqueVehicule = new MarqueVehicule();
            $marqueVehicule->setNomMarque($nomMarque);

            // Validation de l'entité
            $errors = $validator->validate($marqueVehicule);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('marque_vehicule/new.html.twig', [
                    'nom_marque' => $nomMarque,
                ]);
            }

            try {
                $entityManager->persist($marqueVehicule);
                $entityManager->flush();
                
                // Audit log pour la création
                $this->auditLogger->log(
                    'create',
                    MarqueVehicule::class,
                    $marqueVehicule->getId(),
                    ['new' => $this->getMarqueVehiculeData($marqueVehicule)]
                );
                
                $this->addFlash('success', 'Marque de véhicule créée avec succès.');
                return $this->redirectToRoute('app_marque_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'create',
                    MarqueVehicule::class,
                    0,
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la marque: ' . $e->getMessage());
                return $this->render('marque_vehicule/new.html.twig', [
                    'nom_marque' => $nomMarque,
                ]);
            }
        }

        return $this->render('marque_vehicule/new.html.twig');
    }

    #[Route('/{id}', name: 'app_marque_vehicule_show', methods: ['GET'])]
    public function show(MarqueVehicule $marqueVehicule): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('marque_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de cette marque de véhicule.');
            return $this->redirectToRoute('app_marque_vehicule_index');
        }

        return $this->render('marque_vehicule/show.html.twig', [
            'marque' => $marqueVehicule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_marque_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MarqueVehicule $marqueVehicule, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('marque_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier cette marque de véhicule.');
            return $this->redirectToRoute('app_marque_vehicule_index');
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = $this->getMarqueVehiculeData($marqueVehicule);

        if ($request->isMethod('POST')) {
            $nomMarque = trim($request->request->get('nom_marque', ''));
            
            // Validation des données
            if (empty($nomMarque)) {
                $this->addFlash('error', 'Le nom de la marque est obligatoire.');
                return $this->render('marque_vehicule/edit.html.twig', [
                    'marque' => $marqueVehicule,
                    'nom_marque' => $nomMarque,
                ]);
            }
            
            if (mb_strlen($nomMarque) > 100) {
                $this->addFlash('error', 'Le nom de la marque ne doit pas dépasser 100 caractères.');
                return $this->render('marque_vehicule/edit.html.twig', [
                    'marque' => $marqueVehicule,
                    'nom_marque' => $nomMarque,
                ]);
            }
            
            // Vérifier si une autre marque avec le même nom existe déjà
            $existingMarque = $entityManager->getRepository(MarqueVehicule::class)->findOneBy(['nomMarque' => $nomMarque]);
            if ($existingMarque && $existingMarque->getId() !== $marqueVehicule->getId()) {
                $this->addFlash('error', 'Une marque avec ce nom existe déjà.');
                return $this->render('marque_vehicule/edit.html.twig', [
                    'marque' => $marqueVehicule,
                    'nom_marque' => $nomMarque,
                ]);
            }

            $marqueVehicule->setNomMarque($nomMarque);

            // Validation de l'entité
            $errors = $validator->validate($marqueVehicule);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('marque_vehicule/edit.html.twig', [
                    'marque' => $marqueVehicule,
                    'nom_marque' => $nomMarque,
                ]);
            }

            try {
                $entityManager->flush();
                
                // Audit log pour la modification avec anciennes et nouvelles valeurs
                $this->auditLogger->log(
                    'update',
                    MarqueVehicule::class,
                    $marqueVehicule->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getMarqueVehiculeData($marqueVehicule)
                    ]
                );
                
                $this->addFlash('success', 'Marque de véhicule modifiée avec succès.');
                return $this->redirectToRoute('app_marque_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    MarqueVehicule::class,
                    $marqueVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de la marque: ' . $e->getMessage());
                return $this->render('marque_vehicule/edit.html.twig', [
                    'marque' => $marqueVehicule,
                    'nom_marque' => $nomMarque,
                ]);
            }
        }
        
        return $this->render('marque_vehicule/edit.html.twig', [
            'marque' => $marqueVehicule,
        ]);
    }

    #[Route('/{id}', name: 'app_marque_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, MarqueVehicule $marqueVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('marque_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer cette marque de véhicule.');
            return $this->redirectToRoute('app_marque_vehicule_index');
        }

        // Vérifier si la marque est utilisée dans des modèles de véhicules
        if ($marqueVehicule->getModeleVehicules()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette marque car elle est associée à des modèles de véhicules.');
            return $this->redirectToRoute('app_marque_vehicule_show', ['id' => $marqueVehicule->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $marqueVehicule->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour l'audit
                $oldData = $this->getMarqueVehiculeData($marqueVehicule);
                
                $entityManager->remove($marqueVehicule);
                $entityManager->flush();
                
                // Audit log pour la suppression
                $this->auditLogger->log(
                    'delete',
                    MarqueVehicule::class,
                    $marqueVehicule->getId(),
                    ['old' => $oldData]
                );
                
                $this->addFlash('success', 'Marque de véhicule supprimée avec succès.');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'delete',
                    MarqueVehicule::class,
                    $marqueVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de la marque: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_marque_vehicule_index');
    }
}