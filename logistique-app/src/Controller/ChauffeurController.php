<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Entity\Permis;
use App\Entity\TypePermisVehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\AuditLogger;

#[Route('/chauffeurs')]
class ChauffeurController extends AbstractController
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    private function getChauffeurData(Chauffeur $chauffeur): array
    {
        $permisData = [];
        foreach ($chauffeur->getPermis() as $permis) {
            $permisData[] = [
                'type' => $permis->getTypePermisVehicule()->getId(),
                'numero' => $permis->getNumero(),
                'dateValidite' => $permis->getDateValidite()->format('Y-m-d')
            ];
        }

        return [
            'nom' => $chauffeur->getNom(),
            'prenom' => $chauffeur->getPrenom(),
            'telephone' => $chauffeur->getTelephone(),
            'estActif' => $chauffeur->getEstActif(),
            'permis' => $permisData
        ];
    }

    /**
     * Affiche la liste de tous les chauffeurs.
     */
    #[Route('/', name: 'app_chauffeurs_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('chauffeur.view')) {
            $this->auditLogger->log(
                'tentative_access', 
                'Chauffeur', 
                0, 
                ['route' => 'app_chauffeurs_index', 'reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des chauffeurs.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $selectedRegionId = $request->getSession()->get('selected_region');

        if ($selectedRegionId) {
            // Filtrer les chauffeurs par région via les affectations
            $chauffeurs = $entityManager->createQueryBuilder()
                ->select('c')
                ->from(Chauffeur::class, 'c')
                ->join('c.affectations', 'a')
                ->join('a.id_region', 'r')
                ->where('r.id = :regionId')
                ->setParameter('regionId', $selectedRegionId)
                ->groupBy('c.id')
                ->getQuery()
                ->getResult();
        } else {
            $chauffeurs = $entityManager->getRepository(Chauffeur::class)->findAll();
        }

        return $this->render('chauffeurs/index.html.twig', [
            'chauffeurs' => $chauffeurs,
        ]);
    }

    /**
     * Crée un nouveau chauffeur avec ses permis associés.
     */
    #[Route('/new', name: 'app_chauffeurs_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('chauffeur.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'Chauffeur', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un chauffeur.');
            return $this->redirectToRoute('app_chauffeurs_index');
        }

        $chauffeur = new Chauffeur();
        $typesPermis = $entityManager->getRepository(TypePermisVehicule::class)->findAll();

        if ($request->isMethod('POST')) {
            // Récupération et validation des données
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $estActif = (bool)$request->request->get('estActif', true);

            // Validation des données obligatoires
            $errors = [];
            if (empty($nom)) {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (empty($prenom)) {
                $errors[] = 'Le prénom est obligatoire.';
            }
            if (mb_strlen($nom) > 100) {
                $errors[] = 'Le nom ne doit pas dépasser 100 caractères.';
            }
            if (mb_strlen($prenom) > 100) {
                $errors[] = 'Le prénom ne doit pas dépasser 100 caractères.';
            }
            if (mb_strlen($telephone) > 20) {
                $errors[] = 'Le téléphone ne doit pas dépasser 20 caractères.';
            }

            // Vérification des permis
            $categories = $request->request->all('permis_categories');
            $numeros = $request->request->all('permis_numeros');
            $dates = $request->request->all('permis_dates');

            if (empty($categories) || empty($categories[0])) {
                $errors[] = 'Au moins un permis est requis.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                $this->auditLogger->log(
                    'tentative_create', 
                    'Chauffeur', 
                    0, 
                    ['errors' => $errors], 
                    'error'
                );
                return $this->render('chauffeurs/new.html.twig', [
                    'types_permis' => $typesPermis,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'telephone' => $telephone,
                    'estActif' => $estActif,
                ]);
            }

            // Définition des propriétés du chauffeur
            $chauffeur->setNom($nom);
            $chauffeur->setPrenom($prenom);
            $chauffeur->setTelephone($telephone);
            $chauffeur->setEstActif($estActif);

            // Validation de l'entité
            $entityErrors = $validator->validate($chauffeur);
            if (count($entityErrors) > 0) {
                foreach ($entityErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                $this->auditLogger->log(
                    'tentative_create', 
                    'Chauffeur', 
                    0, 
                    ['errors' => $entityErrors], 
                    'error'
                );
                return $this->render('chauffeurs/new.html.twig', [
                    'types_permis' => $typesPermis,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'telephone' => $telephone,
                    'estActif' => $estActif,
                ]);
            }

            // Gestion des permis
            for ($i = 0; $i < count($categories); $i++) {
                if (empty($categories[$i]) || empty($numeros[$i]) || empty($dates[$i])) {
                    continue; // Ignorer les lignes vides
                }

                $permis = new Permis();
                $permis->setTypePermisVehicule($categories[$i]);
                $permis->setNumero($numeros[$i]);
                $permis->setDateValidite(new \DateTimeImmutable($dates[$i]));
                $permis->setChauffeur($chauffeur);
                
                $permisErrors = $validator->validate($permis);
                if (count($permisErrors) > 0) {
                    foreach ($permisErrors as $error) {
                        $this->addFlash('error', 'Erreur dans le permis: ' . $error->getMessage());
                    }
                    $this->auditLogger->log(
                        'tentative_create', 
                        'Chauffeur', 
                        0, 
                        ['errors' => $permisErrors], 
                        'error'
                    );
                    return $this->render('chauffeurs/new.html.twig', [
                        'types_permis' => $typesPermis,
                        'nom' => $nom,
                        'prenom' => $prenom,
                        'telephone' => $telephone,
                        'estActif' => $estActif,
                    ]);
                }
                
                $entityManager->persist($permis);
            }

            try {
                $entityManager->persist($chauffeur);
                $entityManager->flush();

                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    'Chauffeur',
                    $chauffeur->getId(),
                    ['new_data' => $this->getChauffeurData($chauffeur)]
                );

                $this->addFlash('success', 'Le chauffeur a été créé avec succès.');
                return $this->redirectToRoute('app_chauffeurs_index');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'create', 
                    'Chauffeur', 
                    0, 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la création du chauffeur: ' . $e->getMessage());
            }
        }

        return $this->render('chauffeurs/new.html.twig', [
            'types_permis' => $typesPermis,
        ]);
    }

    /**
     * Affiche les détails d'un chauffeur, y compris ses permis.
     */
    #[Route('/{id}', name: 'app_chauffeurs_show', methods: ['GET'])]
    public function show(Chauffeur $chauffeur): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('chauffeur.view')) {
            $this->auditLogger->log(
                'tentative_view', 
                'Chauffeur', 
                $chauffeur->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce chauffeur.');
            return $this->redirectToRoute('app_chauffeurs_index');
        }

        return $this->render('chauffeurs/show.html.twig', [
            'chauffeur' => $chauffeur,
        ]);
    }

    /**
     * Modifie un chauffeur et ses permis.
     */
    #[Route('/{id}/edit', name: 'app_chauffeurs_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Chauffeur $chauffeur, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('chauffeur.update')) {
            $this->auditLogger->log(
                'tentative_update', 
                'Chauffeur', 
                $chauffeur->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce chauffeur.');
            return $this->redirectToRoute('app_chauffeurs_index');
        }

        // Sauvegarder les anciennes données pour le log
        $oldData = $this->getChauffeurData($chauffeur);

        $typesPermis = $entityManager->getRepository(TypePermisVehicule::class)->findAll();

        if ($request->isMethod('POST')) {
            // Récupération et validation des données
            $nom = trim($request->request->get('nom', ''));
            $prenom = trim($request->request->get('prenom', ''));
            $telephone = trim($request->request->get('telephone', ''));
            $estActif = (bool)$request->request->get('estActif', true);

            // Validation des données obligatoires
            $errors = [];
            if (empty($nom)) {
                $errors[] = 'Le nom est obligatoire.';
            }
            if (empty($prenom)) {
                $errors[] = 'Le prénom est obligatoire.';
            }
            if (mb_strlen($nom) > 100) {
                $errors[] = 'Le nom ne doit pas dépasser 100 caractères.';
            }
            if (mb_strlen($prenom) > 100) {
                $errors[] = 'Le prénom ne doit pas dépasser 100 caractères.';
            }
            if (mb_strlen($telephone) > 20) {
                $errors[] = 'Le téléphone ne doit pas dépasser 20 caractères.';
            }

            // Vérification des permis
            $categories = $request->request->all('permis');
            $numeros = $request->request->all('permis_numeros');
            $dates = $request->request->all('permis_dates');

            if (empty($categories) || empty($categories[0])) {
                $errors[] = 'Au moins un permis est requis.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                $this->auditLogger->log(
                    'tentative_update', 
                    'Chauffeur', 
                    $chauffeur->getId(), 
                    ['errors' => $errors], 
                    'error'
                );
                return $this->render('chauffeurs/edit.html.twig', [
                    'chauffeur' => $chauffeur,
                    'types_permis' => $typesPermis,
                ]);
            }

            // Mise à jour des informations du chauffeur
            $chauffeur->setNom($nom);
            $chauffeur->setPrenom($prenom);
            $chauffeur->setTelephone($telephone);
            $chauffeur->setEstActif($estActif);

            // Validation de l'entité
            $entityErrors = $validator->validate($chauffeur);
            if (count($entityErrors) > 0) {
                foreach ($entityErrors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                $this->auditLogger->log(
                    'tentative_update', 
                    'Chauffeur', 
                    $chauffeur->getId(), 
                    ['errors' => $entityErrors], 
                    'error'
                );
                return $this->render('chauffeurs/edit.html.twig', [
                    'chauffeur' => $chauffeur,
                    'types_permis' => $typesPermis,
                ]);
            }

            // Suppression des anciens permis
            foreach ($chauffeur->getPermis() as $permis) {
                $entityManager->remove($permis);
            }
            $entityManager->flush();

            // Ajout des nouveaux permis
            for ($i = 0; $i < count($categories); $i++) {
                if (empty($categories[$i]) || empty($numeros[$i]) || empty($dates[$i])) {
                    continue; // Ignorer les lignes vides
                }

                $permis = new Permis();
                $permis->setTypePermisVehicule($entityManager->getRepository(TypePermisVehicule::class)->find($categories[$i]));
                $permis->setNumero($numeros[$i]);
                $permis->setDateValidite(new \DateTimeImmutable($dates[$i]));
                $permis->setChauffeur($chauffeur);
                
                $permisErrors = $validator->validate($permis);
                if (count($permisErrors) > 0) {
                    foreach ($permisErrors as $error) {
                        $this->addFlash('error', 'Erreur dans le permis: ' . $error->getMessage());
                    }
                    $this->auditLogger->log(
                        'tentative_update', 
                        'Chauffeur', 
                        $chauffeur->getId(), 
                        ['errors' => $permisErrors], 
                        'error'
                    );
                    return $this->render('chauffeurs/edit.html.twig', [
                        'chauffeur' => $chauffeur,
                        'types_permis' => $typesPermis,
                    ]);
                }
                
                $entityManager->persist($permis);
            }

            try {
                $entityManager->flush();

                // Log de l'action de modification
                $this->auditLogger->log(
                    'update',
                    'Chauffeur',
                    $chauffeur->getId(),
                    [
                        'old_data' => $oldData,
                        'new_data' => $this->getChauffeurData($chauffeur)
                    ]
                );

                $this->addFlash('success', 'Le chauffeur a été mis à jour avec succès.');
                return $this->redirectToRoute('app_chauffeurs_index');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'update', 
                    'Chauffeur', 
                    $chauffeur->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du chauffeur: ' . $e->getMessage());
            }
        }

        return $this->render('chauffeurs/edit.html.twig', [
            'chauffeur' => $chauffeur,
            'types_permis' => $typesPermis,
        ]);
    }

    /**
     * Supprime un chauffeur.
     */
    #[Route('/{id}', name: 'app_chauffeurs_delete', methods: ['POST'])]
    public function delete(Request $request, Chauffeur $chauffeur, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('chauffeur.delete')) {
            $this->auditLogger->log(
                'tentative_delete', 
                'Chauffeur', 
                $chauffeur->getId(), 
                ['reason' => 'permission_denied'], 
                'error'
            );
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce chauffeur.');
            return $this->redirectToRoute('app_chauffeurs_index');
        }

        // Vérifier si le chauffeur a des affectations actives
        if ($chauffeur->getAffectationVehicules()->count() > 0) {
            $this->auditLogger->log(
                'tentative_delete', 
                'Chauffeur', 
                $chauffeur->getId(), 
                ['error' => 'Impossible de supprimer - associé à des affectations de véhicules'], 
                'error'
            );
            $this->addFlash('error', 'Ce chauffeur ne peut pas être supprimé car il est associé à des affectations de véhicules.');
            return $this->redirectToRoute('app_chauffeurs_show', ['id' => $chauffeur->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $chauffeur->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour le log
                $oldData = $this->getChauffeurData($chauffeur);
                
                $entityManager->remove($chauffeur);
                $entityManager->flush();

                // Log de l'action de suppression
                $this->auditLogger->log(
                    'delete',
                    'Chauffeur',
                    $chauffeur->getId(),
                    ['deleted_data' => $oldData]
                );

                $this->addFlash('success', 'Le chauffeur a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->auditLogger->log(
                    'delete', 
                    'Chauffeur', 
                    $chauffeur->getId(), 
                    ['error' => $e->getMessage()], 
                    'error'
                );
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du chauffeur: ' . $e->getMessage());
            }
        } else {
            $this->auditLogger->log(
                'tentative_delete', 
                'Chauffeur', 
                $chauffeur->getId(), 
                ['error' => 'CSRF token invalide'], 
                'error'
            );
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_chauffeurs_index');
    }

    #[Route('/permis-type/new', name: 'app_permis_type_new', methods: ['POST'])]
    public function newPermisType(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('chauffeur.create')) {
            $this->auditLogger->log(
                'tentative_create', 
                'TypePermisVehicule', 
                0, 
                ['reason' => 'permission_denied'], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'Vous n\'avez pas les permissions nécessaires pour créer un type de permis.'], 403);
        }

        // Vérifier que la requête est AJAX
        if (!$request->isXmlHttpRequest()) {
            $this->auditLogger->log(
                'tentative_create', 
                'TypePermisVehicule', 
                0, 
                ['error' => 'Requête non AJAX'], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'Requête invalide'], 400);
        }

        // Récupérer les données
        $data = json_decode($request->getContent(), true);
        $categorie = trim($data['categorie'] ?? '');
        $description = trim($data['description'] ?? '');

        // Validation des données
        if (empty($categorie)) {
            $this->auditLogger->log(
                'tentative_create', 
                'TypePermisVehicule', 
                0, 
                ['error' => 'Catégorie manquante'], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'Catégorie manquante'], 400);
        }

        if (mb_strlen($categorie) > 50) {
            $this->auditLogger->log(
                'tentative_create', 
                'TypePermisVehicule', 
                0, 
                ['error' => 'Catégorie trop longue'], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'La catégorie ne doit pas dépasser 50 caractères'], 400);
        }

        if (mb_strlen($description) > 255) {
            $this->auditLogger->log(
                'tentative_create', 
                'TypePermisVehicule', 
                0, 
                ['error' => 'Description trop longue'], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'La description ne doit pas dépasser 255 caractères'], 400);
        }

        // Vérifier si le type de permis existe déjà
        $existingType = $entityManager->getRepository(TypePermisVehicule::class)->findOneBy(['libellePermis' => $categorie]);
        if ($existingType) {
            $this->auditLogger->log(
                'tentative_create', 
                'TypePermisVehicule', 
                0, 
                ['error' => 'Type de permis déjà existant'], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'Ce type de permis existe déjà'], 400);
        }

        // Créer et enregistrer le nouveau type de permis
        $permisType = new TypePermisVehicule();
        $permisType->setLibellePermis($categorie);
        
        // Si vous avez un champ description dans TypePermisVehicule, décommentez la ligne suivante
        // $permisType->setDescription($description);

        try {
            $entityManager->persist($permisType);
            $entityManager->flush();

            // Log de l'action de création de type de permis
            $this->auditLogger->log(
                'create',
                'TypePermisVehicule',
                $permisType->getId(),
                ['new_data' => [
                    'libellePermis' => $permisType->getLibellePermis(),
                    'description' => $description
                ]]
            );

            return new JsonResponse([
                'success' => true, 
                'message' => 'Type de permis ajouté avec succès',
                'id' => $permisType->getId(),
                'libelle' => $permisType->getLibellePermis()
            ]);
        } catch (\Exception $e) {
            $this->auditLogger->log(
                'create', 
                'TypePermisVehicule', 
                0, 
                ['error' => $e->getMessage()], 
                'error'
            );
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()], 500);
        }
    }
}