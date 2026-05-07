<?php

namespace App\Controller;

use App\Entity\ModeUtilisationVehicule;
use App\Repository\ModeUtilisationVehiculeRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/mode/utilisation/vehicule')]
class ModeUtilisationVehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    private function getModeUtilisationVehiculeData(ModeUtilisationVehicule $modeUtilisationVehicule): array
    {
        return [
            'nom' => $modeUtilisationVehicule->getNom()
        ];
    }

    #[Route('/', name: 'app_mode_utilisation_vehicule_index', methods: ['GET'])]
    public function index(Request $request, ModeUtilisationVehiculeRepository $modeUtilisationVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('mode_utilisation_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des modes d\'utilisation de véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $modes = $modeUtilisationVehiculeRepository->findAll();
        
        // Si c'est une requête AJAX, retourner les données en JSON
        if ($request->query->get('ajax')) {
            $data = [];
            foreach ($modes as $mode) {
                $data[] = [
                    'id' => $mode->getId(),
                    'libelle' => $mode->getNom(),
                ];
            }
            return $this->json($data);
        }
        
        return $this->render('mode_utilisation_vehicule/index.html.twig', [
            'mode_utilisation_vehicules' => $modes,
        ]);
    }

    #[Route('/new', name: 'app_mode_utilisation_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('mode_utilisation_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un mode d\'utilisation de véhicule.');
            return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
        }

        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            
            // Validation des données
            if (empty($libelle)) {
                $this->addFlash('error', 'Le libellé est obligatoire.');
                return $this->render('mode_utilisation_vehicule/new.html.twig', [
                    'libelle' => $libelle,
                ]);
            }
            
            if (mb_strlen($libelle) > 100) {
                $this->addFlash('error', 'Le libellé ne doit pas dépasser 100 caractères.');
                return $this->render('mode_utilisation_vehicule/new.html.twig', [
                    'libelle' => $libelle,
                ]);
            }
            
            // Vérifier si un mode avec le même libellé existe déjà
            $existingMode = $entityManager->getRepository(ModeUtilisationVehicule::class)
                ->findOneBy(['nom' => $libelle]);
                
            if ($existingMode) {
                $this->addFlash('error', 'Un mode d\'utilisation avec ce libellé existe déjà.');
                return $this->render('mode_utilisation_vehicule/new.html.twig', [
                    'libelle' => $libelle,
                ]);
            }

            $modeUtilisationVehicule = new ModeUtilisationVehicule();
            $modeUtilisationVehicule->setNom($libelle);

            // Validation de l'entité
            $errors = $validator->validate($modeUtilisationVehicule);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('mode_utilisation_vehicule/new.html.twig', [
                    'libelle' => $libelle,
                ]);
            }

            try {
                $entityManager->persist($modeUtilisationVehicule);
                $entityManager->flush();

                // Audit log pour la création
                $this->auditLogger->log(
                    'create',
                    ModeUtilisationVehicule::class,
                    $modeUtilisationVehicule->getId(),
                    ['new' => $this->getModeUtilisationVehiculeData($modeUtilisationVehicule)]
                );

                // Vérifier si c'est une requête AJAX
                if ($request->request->get('ajax')) {
                    return $this->json([
                        'success' => true,
                        'id' => $modeUtilisationVehicule->getId(),
                        'libelle' => $modeUtilisationVehicule->getNom()
                    ]);
                }

                $this->addFlash('success', 'Mode d\'utilisation de véhicule créé avec succès.');
                return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'create',
                    ModeUtilisationVehicule::class,
                    0,
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création du mode d\'utilisation: ' . $e->getMessage());
                return $this->render('mode_utilisation_vehicule/new.html.twig', [
                    'libelle' => $libelle,
                ]);
            }
        }

        return $this->render('mode_utilisation_vehicule/new.html.twig');
    }

    #[Route('/{id}/show', name: 'app_mode_utilisation_vehicule_show', methods: ['GET'])]
    public function show(ModeUtilisationVehicule $modeUtilisationVehicule): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('mode_utilisation_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce mode d\'utilisation de véhicule.');
            return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
        }

        return $this->render('mode_utilisation_vehicule/show.html.twig', [
            'mode_utilisation_vehicule' => $modeUtilisationVehicule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_mode_utilisation_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ModeUtilisationVehicule $modeUtilisationVehicule, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('mode_utilisation_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce mode d\'utilisation de véhicule.');
            return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
        }

        // Sauvegarder les anciennes valeurs avant modification
        $oldData = $this->getModeUtilisationVehiculeData($modeUtilisationVehicule);

        if ($request->isMethod('POST')) {
            $nom = trim($request->request->get('nom', ''));
            
            // Validation des données
            if (empty($nom)) {
                $this->addFlash('error', 'Le nom est obligatoire.');
                return $this->render('mode_utilisation_vehicule/edit.html.twig', [
                    'mode_utilisation_vehicule' => $modeUtilisationVehicule,
                    'nom' => $nom,
                ]);
            }
            
            if (mb_strlen($nom) > 100) {
                $this->addFlash('error', 'Le nom ne doit pas dépasser 100 caractères.');
                return $this->render('mode_utilisation_vehicule/edit.html.twig', [
                    'mode_utilisation_vehicule' => $modeUtilisationVehicule,
                    'nom' => $nom,
                ]);
            }
            
            // Vérifier si un autre mode avec le même nom existe déjà
            $existingMode = $entityManager->getRepository(ModeUtilisationVehicule::class)
                ->findOneBy(['nom' => $nom]);
                
            if ($existingMode && $existingMode->getId() !== $modeUtilisationVehicule->getId()) {
                $this->addFlash('error', 'Un mode d\'utilisation avec ce nom existe déjà.');
                return $this->render('mode_utilisation_vehicule/edit.html.twig', [
                    'mode_utilisation_vehicule' => $modeUtilisationVehicule,
                    'nom' => $nom,
                ]);
            }

            $modeUtilisationVehicule->setNom($nom);

            // Validation de l'entité
            $errors = $validator->validate($modeUtilisationVehicule);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('mode_utilisation_vehicule/edit.html.twig', [
                    'mode_utilisation_vehicule' => $modeUtilisationVehicule,
                    'nom' => $nom,
                ]);
            }

            try {
                $entityManager->flush();
                
                // Audit log pour la modification avec anciennes et nouvelles valeurs
                $this->auditLogger->log(
                    'update',
                    ModeUtilisationVehicule::class,
                    $modeUtilisationVehicule->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getModeUtilisationVehiculeData($modeUtilisationVehicule)
                    ]
                );
                
                $this->addFlash('success', 'Mode d\'utilisation de véhicule modifié avec succès.');
                return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    ModeUtilisationVehicule::class,
                    $modeUtilisationVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du mode d\'utilisation: ' . $e->getMessage());
                return $this->render('mode_utilisation_vehicule/edit.html.twig', [
                    'mode_utilisation_vehicule' => $modeUtilisationVehicule,
                    'nom' => $nom,
                ]);
            }
        }

        return $this->render('mode_utilisation_vehicule/edit.html.twig', [
            'mode_utilisation_vehicule' => $modeUtilisationVehicule,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_mode_utilisation_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, ModeUtilisationVehicule $modeUtilisationVehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('mode_utilisation_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce mode d\'utilisation de véhicule.');
            return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
        }

        // Vérifier si le mode est utilisé dans des affectations
        if ($modeUtilisationVehicule->getAffectationVehicules()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce mode d\'utilisation car il est associé à des affectations de véhicules.');
            return $this->redirectToRoute('app_mode_utilisation_vehicule_show', ['id' => $modeUtilisationVehicule->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$modeUtilisationVehicule->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour l'audit
                $oldData = $this->getModeUtilisationVehiculeData($modeUtilisationVehicule);
                
                $entityManager->remove($modeUtilisationVehicule);
                $entityManager->flush();
                
                // Audit log pour la suppression
                $this->auditLogger->log(
                    'delete',
                    ModeUtilisationVehicule::class,
                    $modeUtilisationVehicule->getId(),
                    ['old' => $oldData]
                );
                
                $this->addFlash('success', 'Mode d\'utilisation de véhicule supprimé avec succès.');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'delete',
                    ModeUtilisationVehicule::class,
                    $modeUtilisationVehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du mode d\'utilisation: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_mode_utilisation_vehicule_index');
    }

    #[Route('/list/json', name: 'app_mode_utilisation_vehicule_list_json', methods: ['GET'])]
    public function modeUtilisationList(EntityManagerInterface $entityManager): JsonResponse
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('mode_utilisation_vehicule.view')) {
            return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        $modes = $entityManager->getRepository(ModeUtilisationVehicule::class)->findAll();
        $data = [];
        foreach ($modes as $mode) {
            $data[] = [
                'id' => $mode->getId(),
                'nom' => $mode->getNom(),
            ];
        }
        return $this->json($data);
    }
}