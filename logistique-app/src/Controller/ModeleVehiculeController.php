<?php

namespace App\Controller;

use App\Entity\ModeleVehicule;
use App\Repository\ModeleVehiculeRepository;
use App\Repository\MarqueVehiculeRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/modele_vehicule')]
class ModeleVehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    private function getModeleVehiculeData(ModeleVehicule $modeleVehicule): array
    {
        return [
            'nomModele' => $modeleVehicule->getNomModele(),
            'idMarque' => $modeleVehicule->getIdMarque() ? $modeleVehicule->getIdMarque()->getId() : null
        ];
    }

    #[Route('/', name: 'app_modele_vehicule_index', methods: ['GET'])]
    public function index(ModeleVehiculeRepository $modeleVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('modele_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des modèles de véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        return $this->render('modele_vehicule/index.html.twig', [
            'modele_vehicules' => $modeleVehiculeRepository->findAll(),
        ]);
    }
    
    // Route pour obtenir tous les modèles au format JSON
    #[Route('/json', name: 'app_get_all_modeles_vehicules', methods: ['GET'])]
public function getAllModelesAsJson(ModeleVehiculeRepository $modeleVehiculeRepository): JsonResponse
{
    // Vérification manuelle de la permission
    if (!$this->isGranted('modele_vehicule.view')) {
        return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
    }

    $modeles = $modeleVehiculeRepository->findAll();
    $data = [];
    
    foreach ($modeles as $modele) {
        $data[] = [
            'id' => $modele->getId(),
            'textContent' => $modele->getNomModele(),
            'marqueId' => $modele->getIdMarque() ? $modele->getIdMarque()->getId() : null,
        ];
    }

    return $this->json($data);
}

    #[Route('/new', name: 'app_modele_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MarqueVehiculeRepository $marqueVehiculeRepository, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('modele_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un modèle de véhicule.');
            return $this->redirectToRoute('app_modele_vehicule_index');
        }

        if ($request->isMethod('POST')) {
            // Vérification du token CSRF
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('create_modele', $submittedToken)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_modele_vehicule_new');
            }
            
            $nomModele = trim($request->request->get('nom_modele', ''));
            $idMarque = $request->request->get('id_marque');
            
            // Validation des données
            $errors = [];
            if (empty($nomModele)) {
                $errors[] = 'Le nom du modèle est obligatoire.';
            }
            if (empty($idMarque)) {
                $errors[] = 'La marque est obligatoire.';
            }
            if (mb_strlen($nomModele) > 100) {
                $errors[] = 'Le nom du modèle ne doit pas dépasser 100 caractères.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('modele_vehicule/new.html.twig', [
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            $marque = $marqueVehiculeRepository->find($idMarque);
            if (!$marque) {
                $this->addFlash('error', 'Marque non trouvée.');
                return $this->render('modele_vehicule/new.html.twig', [
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            // Vérifier si un modèle avec le même nom existe déjà pour cette marque
            $existingModele = $entityManager->getRepository(ModeleVehicule::class)
                ->findOneBy(['nomModele' => $nomModele, 'idMarque' => $marque]);
                
            if ($existingModele) {
                $this->addFlash('error', 'Un modèle avec ce nom existe déjà pour cette marque.');
                return $this->render('modele_vehicule/new.html.twig', [
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            $modeleVehicule = new ModeleVehicule();
            $modeleVehicule->setNomModele($nomModele);
            $modeleVehicule->setIdMarque($marque);

            // Validation de l'entité
            $validationErrors = $validator->validate($modeleVehicule);
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('modele_vehicule/new.html.twig', [
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            try {
                $entityManager->persist($modeleVehicule);
                $entityManager->flush();
                
                // Audit log pour la création
                $this->auditLogger->log(
                    'create',
                    ModeleVehicule::class,
                    $modeleVehicule->getId(),
                    ['new' => $this->getModeleVehiculeData($modeleVehicule)]
                );
                
                $this->addFlash('success', 'Modèle de véhicule créé avec succès.');
                return $this->redirectToRoute('app_modele_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'create',
                    ModeleVehicule::class,
                    0,
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création du modèle: ' . $e->getMessage());
                return $this->render('modele_vehicule/new.html.twig', [
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }
        }

        return $this->render('modele_vehicule/new.html.twig', [
            'marques' => $marqueVehiculeRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_modele_vehicule_show', methods: ['GET'])]
    public function show(ModeleVehicule $modeleVehicule): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('modele_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce modèle de véhicule.');
            return $this->redirectToRoute('app_modele_vehicule_index');
        }

        return $this->render('modele_vehicule/show.html.twig', [
            'modele_vehicule' => $modeleVehicule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_modele_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ModeleVehicule $modeleVehicule, EntityManagerInterface $entityManager, MarqueVehiculeRepository $marqueVehiculeRepository, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('modele_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce modèle de véhicule.');
            return $this->redirectToRoute('app_modele_vehicule_index');
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = $this->getModeleVehiculeData($modeleVehicule);

        if ($request->isMethod('POST')) {
            // Vérification du token CSRF
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('edit_modele_' . $modeleVehicule->getId(), $submittedToken)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_modele_vehicule_edit', ['id' => $modeleVehicule->getId()]);
            }
            
            $nomModele = trim($request->request->get('nom_modele', ''));
            $idMarque = $request->request->get('id_marque');
            
            // Validation des données
            $errors = [];
            if (empty($nomModele)) {
                $errors[] = 'Le nom du modèle est obligatoire.';
            }
            if (empty($idMarque)) {
                $errors[] = 'La marque est obligatoire.';
            }
            if (mb_strlen($nomModele) > 100) {
                $errors[] = 'Le nom du modèle ne doit pas dépasser 100 caractères.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('modele_vehicule/edit.html.twig', [
                    'modele_vehicule' => $modeleVehicule,
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            $marque = $marqueVehiculeRepository->find($idMarque);
            if (!$marque) {
                $this->addFlash('error', 'Marque non trouvée.');
                return $this->render('modele_vehicule/edit.html.twig', [
                    'modele_vehicule' => $modeleVehicule,
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            // Vérifier si un autre modèle avec le même nom existe déjà pour cette marque
            $existingModele = $entityManager->getRepository(ModeleVehicule::class)
                ->findOneBy(['nomModele' => $nomModele, 'idMarque' => $marque]);
                
            if ($existingModele && $existingModele->getId() !== $modeleVehicule->getId()) {
                $this->addFlash('error', 'Un modèle avec ce nom existe déjà pour cette marque.');
                return $this->render('modele_vehicule/edit.html.twig', [
                    'modele_vehicule' => $modeleVehicule,
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            $modeleVehicule->setNomModele($nomModele);
            $modeleVehicule->setIdMarque($marque);

            // Validation de l'entité
            $validationErrors = $validator->validate($modeleVehicule);
            if (count($validationErrors) > 0) {
                foreach ($validationErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('modele_vehicule/edit.html.twig', [
                    'modele_vehicule' => $modeleVehicule,
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }

            try {
                $entityManager->flush();
                
                // Audit log pour la modification avec anciennes et nouvelles valeurs
                $this->auditLogger->log(
                    'update',
                    ModeleVehicule::class,
                    $modeleVehicule->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getModeleVehiculeData($modeleVehicule)
                    ]
                );
                
                $this->addFlash('success', 'Modèle de véhicule modifié avec succès.');
                return $this->redirectToRoute('app_modele_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    ModeleVehicule::class,
                    $modeleVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du modèle: ' . $e->getMessage());
                return $this->render('modele_vehicule/edit.html.twig', [
                    'modele_vehicule' => $modeleVehicule,
                    'marques' => $marqueVehiculeRepository->findAll(),
                    'nom_modele' => $nomModele,
                    'id_marque' => $idMarque,
                ]);
            }
        }
        
        return $this->render('modele_vehicule/edit.html.twig', [
            'modele_vehicule' => $modeleVehicule,
            'marques' => $marqueVehiculeRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_modele_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, ModeleVehicule $modeleVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('modele_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce modèle de véhicule.');
            return $this->redirectToRoute('app_modele_vehicule_index');
        }

        // Vérifier si le modèle est utilisé dans des véhicules
        if ($modeleVehicule->getVehicules()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce modèle car il est associé à des véhicules.');
            return $this->redirectToRoute('app_modele_vehicule_show', ['id' => $modeleVehicule->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $modeleVehicule->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour l'audit
                $oldData = $this->getModeleVehiculeData($modeleVehicule);
                
                $entityManager->remove($modeleVehicule);
                $entityManager->flush();
                
                // Audit log pour la suppression
                $this->auditLogger->log(
                    'delete',
                    ModeleVehicule::class,
                    $modeleVehicule->getId(),
                    ['old' => $oldData]
                );
                
                $this->addFlash('success', 'Modèle de véhicule supprimé avec succès.');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'delete',
                    ModeleVehicule::class,
                    $modeleVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du modèle: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_modele_vehicule_index');
    }
}