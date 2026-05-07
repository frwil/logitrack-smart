<?php

namespace App\Controller;

use App\Entity\AffectationVehicule;
use App\Entity\Vehicule;
use App\Entity\Chauffeur;
use App\Entity\TypeUtilisationVehicule;
use App\Entity\ModeUtilisationVehicule;
use App\Entity\Entite;
use App\Entity\Region;
use App\Repository\AffectationVehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\AuditLogger;


#[Route('/affectation/vehicule')]
class AffectationVehiculeController extends AbstractController
{
    private AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    #[Route('/', name: 'app_affectation_vehicule_index', methods: ['GET'])]
    public function index(AffectationVehiculeRepository $affectationVehiculeRepository, Request $request): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('affectation_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des affectations de véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $selectedRegionId = $request->getSession()->get('selected_region');

        if ($selectedRegionId) {
            $affectations = $affectationVehiculeRepository->findBy(['id_region' => $selectedRegionId]);
        } else {
            $affectations = $affectationVehiculeRepository->findAll();
        }

        return $this->render('affectation_vehicule/index.html.twig', [
            'affectations' => $affectations,
        ]);
    }

    #[Route('/new', name: 'app_affectation_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('affectation_vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer une affectation de véhicule.');
            return $this->redirectToRoute('app_affectation_vehicule_index');
        }

        $affectation = new AffectationVehicule();

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $data = $request->request->all();
            
            // Validation des données obligatoires
            $errors = $this->validateFormData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                    $this->auditLogger->log(
                        'CREATE',
                        'AffectationVehicule',
                        0,
                        ['error' => $error, 'form_data' => $data],
                        'error'
                    );
                }
                return $this->redirectToRoute('app_affectation_vehicule_new');
            }

            // Définir les propriétés de l'affectation
            if (!empty($data['id_vehicule'])) {
                $vehicule = $entityManager->getRepository(Vehicule::class)->find($data['id_vehicule']);
                if ($vehicule) {
                    $affectation->setIdVehicule($vehicule);
                    
                    // Vérifier si le véhicule est déjà affecté
                    if ($this->isVehiculeAlreadyAffected($vehicule, $entityManager)) {
                        $errorMsg = 'Ce véhicule est déjà affecté à un autre chauffeur.';
                        $this->addFlash('error', $errorMsg);
                        $this->auditLogger->log(
                            'CREATE',
                            'AffectationVehicule',
                            0,
                            ['error' => $errorMsg, 'vehicule_id' => $vehicule->getId()],
                            'error'
                        );
                        return $this->redirectToRoute('app_affectation_vehicule_new');
                    }
                }
            }

            if (!empty($data['id_chauffeur'])) {
                $chauffeur = $entityManager->getRepository(Chauffeur::class)->find($data['id_chauffeur']);
                if ($chauffeur) {
                    $affectation->setIdChauffeur($chauffeur);
                    
                    // Vérifier si le chauffeur est déjà affecté
                    if ($this->isChauffeurAlreadyAffected($chauffeur, $entityManager)) {
                        $errorMsg = 'Ce chauffeur est déjà affecté à un autre véhicule.';
                        $this->addFlash('error', $errorMsg);
                        $this->auditLogger->log(
                            'CREATE',
                            'AffectationVehicule',
                            0,
                            ['error' => $errorMsg, 'chauffeur_id' => $chauffeur->getId()],
                            'error'
                        );
                        return $this->redirectToRoute('app_affectation_vehicule_new');
                    }
                }
            }

            // Répéter pour les autres champs...
            if (!empty($data['id_type_utilisation'])) {
                $typeUtilisation = $entityManager->getRepository(TypeUtilisationVehicule::class)->find($data['id_type_utilisation']);
                if ($typeUtilisation) {
                    $affectation->setIdTypeUtilisation($typeUtilisation);
                }
            }

            if (!empty($data['id_mode_utilisation'])) {
                $modeUtilisation = $entityManager->getRepository(ModeUtilisationVehicule::class)->find($data['id_mode_utilisation']);
                if ($modeUtilisation) {
                    $affectation->setIdModeUtilisation($modeUtilisation);
                }
            }

            if (!empty($data['id_entite'])) {
                $entite = $entityManager->getRepository(Entite::class)->find($data['id_entite']);
                if ($entite) {
                    $affectation->setIdEntite($entite);
                }
            }

            if (!empty($data['id_region'])) {
                $region = $entityManager->getRepository(Region::class)->find($data['id_region']);
                if ($region) {
                    $affectation->setIdRegion($region);
                }
            }

            $affectation->setObjetAffectation($data['objet_affectation'] ?? null);

            if (!empty($data['date_debut_affectation'])) {
                $dateDebut = \DateTime::createFromFormat('Y-m-d', $data['date_debut_affectation']);
                if ($dateDebut) {
                    $affectation->setDateDebutAffectation($dateDebut);
                }
            }

            if (!empty($data['date_fin_affectation'])) {
                $dateFin = \DateTime::createFromFormat('Y-m-d', $data['date_fin_affectation']);
                if ($dateFin) {
                    $affectation->setDateFinAffectation($dateFin);
                    
                    // Vérifier que la date de fin est après la date de début
                    if ($affectation->getDateDebutAffectation() && $dateFin < $affectation->getDateDebutAffectation()) {
                        $errorMsg = 'La date de fin doit être postérieure à la date de début.';
                        $this->addFlash('error', $errorMsg);
                        $this->auditLogger->log(
                            'CREATE',
                            'AffectationVehicule',
                            0,
                            ['error' => $errorMsg],
                            'error'
                        );
                        return $this->redirectToRoute('app_affectation_vehicule_new');
                    }
                }
            }

            $affectation->setIsFerme(isset($data['is_ferme']) && $data['is_ferme'] === 'on');
            $affectation->setDateAffectation(new \DateTime());

            // Valider l'entité
            $errors = $this->validateAffectation($affectation);

            if (empty($errors)) {
                try {
                    $entityManager->persist($affectation);
                    $entityManager->flush();

                    // Enregistrer l'opération de création
                    $this->auditLogger->log(
                        'CREATE',
                        'AffectationVehicule',
                        $affectation->getId(),
                        [
                            'vehicule' => $affectation->getIdVehicule()->getId(),
                            'chauffeur' => $affectation->getIdChauffeur()->getId(),
                            'type_utilisation' => $affectation->getIdTypeUtilisation()->getId(),
                            'mode_utilisation' => $affectation->getIdModeUtilisation()->getId(),
                            'entite' => $affectation->getIdEntite()->getId(),
                            'region' => $affectation->getIdRegion()->getId(),
                            'objet_affectation' => $affectation->getObjetAffectation(),
                            'date_debut' => $affectation->getDateDebutAffectation()->format('Y-m-d'),
                            'date_fin' => $affectation->getDateFinAffectation() ? $affectation->getDateFinAffectation()->format('Y-m-d') : null,
                            'is_ferme' => $affectation->isIsFerme(),
                            'date_affectation' => $affectation->getDateAffectation()->format('Y-m-d H:i:s')
                        ],
                        'success'
                    );

                    $this->addFlash('success', 'Affectation créée avec succès.');
                    return $this->redirectToRoute('app_affectation_vehicule_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $errorMsg = 'Une erreur est survenue lors de la création de l\'affectation: ' . $e->getMessage();
                    $this->addFlash('error', $errorMsg);
                    $this->auditLogger->log(
                        'CREATE',
                        'AffectationVehicule',
                        0,
                        ['error' => $errorMsg],
                        'error'
                    );
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                    $this->auditLogger->log(
                        'CREATE',
                        'AffectationVehicule',
                        0,
                        ['error' => $error],
                        'error'
                    );
                }
            }
        }

        // Récupérer les données pour les listes déroulantes
        $vehicules = $entityManager->getRepository(Vehicule::class)->findBy(['statut' => true]);
        $chauffeurs = $entityManager->getRepository(Chauffeur::class)->findBy(['estActif' => true]);
        $typesUtilisation = $entityManager->getRepository(TypeUtilisationVehicule::class)->findAll();
        $modesUtilisation = $entityManager->getRepository(ModeUtilisationVehicule::class)->findAll();
        $entites = $entityManager->getRepository(Entite::class)->findBy(['statut' => true]);
        $regions = $entityManager->getRepository(Region::class)->findAll();

        return $this->render('affectation_vehicule/new.html.twig', [
            'affectation' => $affectation,
            'vehicules' => $vehicules,
            'chauffeurs' => $chauffeurs,
            'typesUtilisation' => $typesUtilisation,
            'modesUtilisation' => $modesUtilisation,
            'entites' => $entites,
            'regions' => $regions,
        ]);
    }

    #[Route('/{id}', name: 'app_affectation_vehicule_show', methods: ['GET'])]
    public function show(AffectationVehicule $affectation): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('affectation_vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de cette affectation de véhicule.');
            return $this->redirectToRoute('app_affectation_vehicule_index');
        }

        return $this->render('affectation_vehicule/show.html.twig', [
            'affectation' => $affectation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_affectation_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AffectationVehicule $affectation, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('affectation_vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier cette affectation de véhicule.');
            return $this->redirectToRoute('app_affectation_vehicule_index');
        }

        // Sauvegarder les anciennes valeurs pour l'audit
        $oldValues = [
            'vehicule' => $affectation->getIdVehicule()->getId(),
            'chauffeur' => $affectation->getIdChauffeur()->getId(),
            'type_utilisation' => $affectation->getIdTypeUtilisation()->getId(),
            'mode_utilisation' => $affectation->getIdModeUtilisation()->getId(),
            'entite' => $affectation->getIdEntite()->getId(),
            'region' => $affectation->getIdRegion()->getId(),
            'objet_affectation' => $affectation->getObjetAffectation(),
            'date_debut' => $affectation->getDateDebutAffectation()->format('Y-m-d'),
            'date_fin' => $affectation->getDateFinAffectation() ? $affectation->getDateFinAffectation()->format('Y-m-d') : null,
            'is_ferme' => $affectation->isIsFerme()
        ];

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $data = $request->request->all();
            
            // Validation des données obligatoires
            $errors = $this->validateFormData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                    $this->auditLogger->log(
                        'UPDATE',
                        'AffectationVehicule',
                        $affectation->getId(),
                        ['error' => $error, 'form_data' => $data],
                        'error'
                    );
                }
                return $this->redirectToRoute('app_affectation_vehicule_edit', ['id' => $affectation->getId()]);
            }

            // Mettre à jour les propriétés de l'affectation
            if (!empty($data['id_vehicule'])) {
                $vehicule = $entityManager->getRepository(Vehicule::class)->find($data['id_vehicule']);
                if ($vehicule && $vehicule->getId() !== $affectation->getIdVehicule()->getId()) {
                    // Vérifier si le véhicule est déjà affecté à un autre chauffeur
                    if ($this->isVehiculeAlreadyAffected($vehicule, $entityManager, $affectation->getId())) {
                        $errorMsg = 'Ce véhicule est déjà affecté à un autre chauffeur.';
                        $this->addFlash('error', $errorMsg);
                        $this->auditLogger->log(
                            'UPDATE',
                            'AffectationVehicule',
                            $affectation->getId(),
                            ['error' => $errorMsg, 'vehicule_id' => $vehicule->getId()],
                            'error'
                        );
                        return $this->redirectToRoute('app_affectation_vehicule_edit', ['id' => $affectation->getId()]);
                    }
                    $affectation->setIdVehicule($vehicule);
                }
            }

            if (!empty($data['id_chauffeur'])) {
                $chauffeur = $entityManager->getRepository(Chauffeur::class)->find($data['id_chauffeur']);
                if ($chauffeur && $chauffeur->getId() !== $affectation->getIdChauffeur()->getId()) {
                    // Vérifier si le chauffeur est déjà affecté à un autre véhicule
                    if ($this->isChauffeurAlreadyAffected($chauffeur, $entityManager, $affectation->getId())) {
                        $errorMsg = 'Ce chauffeur est déjà affecté à un autre véhicule.';
                        $this->addFlash('error', $errorMsg);
                        $this->auditLogger->log(
                            'UPDATE',
                            'AffectationVehicule',
                            $affectation->getId(),
                            ['error' => $errorMsg, 'chauffeur_id' => $chauffeur->getId()],
                            'error'
                        );
                        return $this->redirectToRoute('app_affectation_vehicule_edit', ['id' => $affectation->getId()]);
                    }
                    $affectation->setIdChauffeur($chauffeur);
                }
            }

            if (!empty($data['id_type_utilisation'])) {
                $typeUtilisation = $entityManager->getRepository(TypeUtilisationVehicule::class)->find($data['id_type_utilisation']);
                if ($typeUtilisation) {
                    $affectation->setIdTypeUtilisation($typeUtilisation);
                }
            }

            if (!empty($data['id_mode_utilisation'])) {
                $modeUtilisation = $entityManager->getRepository(ModeUtilisationVehicule::class)->find($data['id_mode_utilisation']);
                if ($modeUtilisation) {
                    $affectation->setIdModeUtilisation($modeUtilisation);
                }
            }

            if (!empty($data['id_entite'])) {
                $entite = $entityManager->getRepository(Entite::class)->find($data['id_entite']);
                if ($entite) {
                    $affectation->setIdEntite($entite);
                }
            }

            if (!empty($data['id_region'])) {
                $region = $entityManager->getRepository(Region::class)->find($data['id_region']);
                if ($region) {
                    $affectation->setIdRegion($region);
                }
            }

            $affectation->setObjetAffectation($data['objet_affectation'] ?? null);

            if (!empty($data['date_debut_affectation'])) {
                $dateDebut = \DateTime::createFromFormat('Y-m-d', $data['date_debut_affectation']);
                if ($dateDebut) {
                    $affectation->setDateDebutAffectation($dateDebut);
                }
            }

            if (!empty($data['date_fin_affectation'])) {
                $dateFin = \DateTime::createFromFormat('Y-m-d', $data['date_fin_affectation']);
                if ($dateFin) {
                    // Vérifier que la date de fin est après la date de début
                    if ($affectation->getDateDebutAffectation() && $dateFin < $affectation->getDateDebutAffectation()) {
                        $errorMsg = 'La date de fin doit être postérieure à la date de début.';
                        $this->addFlash('error', $errorMsg);
                        $this->auditLogger->log(
                            'UPDATE',
                            'AffectationVehicule',
                            $affectation->getId(),
                            ['error' => $errorMsg],
                            'error'
                        );
                        return $this->redirectToRoute('app_affectation_vehicule_edit', ['id' => $affectation->getId()]);
                    }
                    
                    if (isset($data['is_ferme']) && $data['is_ferme'] == '1') {
                        $affectation->setDateFinAffectation(new \DateTime());
                    } else {
                        $affectation->setDateFinAffectation($dateFin);
                    }
                }
            }

            // Mettre à jour l'état de l'affectation (fermée ou non)
            $affectation->setIsFerme(isset($data['is_ferme']) && $data['is_ferme'] == '1');

            // Valider l'entité
            $errors = $this->validateAffectation($affectation);

            if (empty($errors)) {
                try {
                    $entityManager->flush();

                    // Préparer les nouvelles valeurs pour l'audit
                    $newValues = [
                        'vehicule' => $affectation->getIdVehicule()->getId(),
                        'chauffeur' => $affectation->getIdChauffeur()->getId(),
                        'type_utilisation' => $affectation->getIdTypeUtilisation()->getId(),
                        'mode_utilisation' => $affectation->getIdModeUtilisation()->getId(),
                        'entite' => $affectation->getIdEntite()->getId(),
                        'region' => $affectation->getIdRegion()->getId(),
                        'objet_affectation' => $affectation->getObjetAffectation(),
                        'date_debut' => $affectation->getDateDebutAffectation()->format('Y-m-d'),
                        'date_fin' => $affectation->getDateFinAffectation() ? $affectation->getDateFinAffectation()->format('Y-m-d') : null,
                        'is_ferme' => $affectation->isIsFerme()
                    ];

                    // Enregistrer l'opération de mise à jour avec les anciennes et nouvelles valeurs
                    $this->auditLogger->log(
                        'UPDATE',
                        'AffectationVehicule',
                        $affectation->getId(),
                        [
                            'old_values' => $oldValues,
                            'new_values' => $newValues
                        ],
                        'success'
                    );

                    $this->addFlash('success', 'Affectation mise à jour avec succès.');
                    return $this->redirectToRoute('app_affectation_vehicule_index', [], Response::HTTP_SEE_OTHER);
                } catch (\Exception $e) {
                    $errorMsg = 'Une erreur est survenue lors de la mise à jour de l\'affectation: ' . $e->getMessage();
                    $this->addFlash('error', $errorMsg);
                    $this->auditLogger->log(
                        'UPDATE',
                        'AffectationVehicule',
                        $affectation->getId(),
                        ['error' => $errorMsg],
                        'error'
                    );
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                    $this->auditLogger->log(
                        'UPDATE',
                        'AffectationVehicule',
                        $affectation->getId(),
                        ['error' => $error],
                        'error'
                    );
                }
            }
        }

        // Récupérer les données pour les listes déroulantes
        $vehicules = $entityManager->getRepository(Vehicule::class)->findBy(['statut' => true]);
        $chauffeurs = $entityManager->getRepository(Chauffeur::class)->findBy(['estActif' => true]);
        $typesUtilisation = $entityManager->getRepository(TypeUtilisationVehicule::class)->findAll();
        $modesUtilisation = $entityManager->getRepository(ModeUtilisationVehicule::class)->findAll();
        $entites = $entityManager->getRepository(Entite::class)->findBy(['statut' => true]);
        $regions = $entityManager->getRepository(Region::class)->findAll();

        return $this->render('affectation_vehicule/edit.html.twig', [
            'affectation' => $affectation,
            'vehicules' => $vehicules,
            'chauffeurs' => $chauffeurs,
            'typesUtilisation' => $typesUtilisation,
            'modesUtilisation' => $modesUtilisation,
            'entites' => $entites,
            'regions' => $regions,
        ]);
    }

    #[Route('/{id}', name: 'app_affectation_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, AffectationVehicule $affectation, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('affectation_vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer cette affectation de véhicule.');
            return $this->redirectToRoute('app_affectation_vehicule_index');
        }

        if ($this->isCsrfTokenValid('delete' . $affectation->getId(), $request->request->get('_token'))) {
            try {
                // Enregistrer les données avant suppression
                $affectationData = [
                    'vehicule' => $affectation->getIdVehicule()->getId(),
                    'chauffeur' => $affectation->getIdChauffeur()->getId(),
                    'type_utilisation' => $affectation->getIdTypeUtilisation()->getId(),
                    'mode_utilisation' => $affectation->getIdModeUtilisation()->getId(),
                    'entite' => $affectation->getIdEntite()->getId(),
                    'region' => $affectation->getIdRegion()->getId(),
                    'objet_affectation' => $affectation->getObjetAffectation(),
                    'date_debut' => $affectation->getDateDebutAffectation()->format('Y-m-d'),
                    'date_fin' => $affectation->getDateFinAffectation() ? $affectation->getDateFinAffectation()->format('Y-m-d') : null,
                    'is_ferme' => $affectation->isIsFerme()
                ];

                $entityManager->remove($affectation);
                $entityManager->flush();

                // Enregistrer l'opération de suppression
                $this->auditLogger->log(
                    'DELETE',
                    'AffectationVehicule',
                    $affectation->getId(),
                    $affectationData,
                    'success'
                );

                $this->addFlash('success', 'Affectation supprimée avec succès.');
            } catch (\Exception $e) {
                $errorMsg = 'Une erreur est survenue lors de la suppression de l\'affectation: ' . $e->getMessage();
                $this->addFlash('error', $errorMsg);
                $this->auditLogger->log(
                    'DELETE',
                    'AffectationVehicule',
                    $affectation->getId(),
                    ['error' => $errorMsg],
                    'error'
                );
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_affectation_vehicule_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Valider une affectation de véhicule
     */
    private function validateAffectation(AffectationVehicule $affectation): array
    {
        $errors = [];

        if (!$affectation->getIdVehicule()) {
            $errors[] = 'Veuillez sélectionner un véhicule.';
        }

        if (!$affectation->getIdChauffeur()) {
            $errors[] = 'Veuillez sélectionner un chauffeur.';
        }

        if (!$affectation->getIdTypeUtilisation()) {
            $errors[] = 'Veuillez sélectionner un type d\'utilisation.';
        }

        if (!$affectation->getIdModeUtilisation()) {
            $errors[] = 'Veuillez sélectionner un mode d\'utilisation.';
        }

        if (!$affectation->getIdEntite()) {
            $errors[] = 'Veuillez sélectionner une entité.';
        }

        if (!$affectation->getDateDebutAffectation()) {
            $errors[] = 'Veuillez spécifier une date de début.';
        }

        // Vérifier que la date de fin est après la date de début si les deux sont définies
        if ($affectation->getDateDebutAffectation() && $affectation->getDateFinAffectation() && 
            $affectation->getDateFinAffectation() < $affectation->getDateDebutAffectation()) {
            $errors[] = 'La date de fin doit être postérieure à la date de début.';
        }

        return $errors;
    }

    /**
     * Valider les données du formulaire
     */
    private function validateFormData(array $data): array
    {
        $errors = [];

        if (empty($data['id_vehicule'])) {
            $errors[] = 'Le véhicule est obligatoire.';
        }

        if (empty($data['id_chauffeur'])) {
            $errors[] = 'Le chauffeur est obligatoire.';
        }

        if (empty($data['id_type_utilisation'])) {
            $errors[] = 'Le type d\'utilisation est obligatoire.';
        }

        if (empty($data['id_mode_utilisation'])) {
            $errors[] = 'Le mode d\'utilisation est obligatoire.';
        }

        if (empty($data['id_entite'])) {
            $errors[] = 'L\'entité est obligatoire.';
        }

        if (empty($data['date_debut_affectation'])) {
            $errors[] = 'La date de début est obligatoire.';
        } elseif (!\DateTime::createFromFormat('Y-m-d', $data['date_debut_affectation'])) {
            $errors[] = 'Le format de la date de début est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        if (!empty($data['date_fin_affectation']) && !\DateTime::createFromFormat('Y-m-d', $data['date_fin_affectation'])) {
            $errors[] = 'Le format de la date de fin est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        return $errors;
    }

    /**
     * Vérifier si un véhicule est déjà affecté
     */
    private function isVehiculeAlreadyAffected(Vehicule $vehicule, EntityManagerInterface $entityManager, int $excludeAffectationId = null): bool
    {
        $repository = $entityManager->getRepository(AffectationVehicule::class);
        
        $queryBuilder = $repository->createQueryBuilder('a')
            ->where('a.id_vehicule = :vehicule')
            ->andWhere('a.is_ferme = false')
            ->setParameter('vehicule', $vehicule);
            
        if ($excludeAffectationId) {
            $queryBuilder->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeAffectationId);
        }
            
        $existingAffectation = $queryBuilder->getQuery()->getOneOrNullResult();
        
        return $existingAffectation !== null;
    }

    /**
     * Vérifier si un chauffeur est déjà affecté
     */
    private function isChauffeurAlreadyAffected(Chauffeur $chauffeur, EntityManagerInterface $entityManager, int $excludeAffectationId = null): bool
    {
        $repository = $entityManager->getRepository(AffectationVehicule::class);
        
        $queryBuilder = $repository->createQueryBuilder('a')
            ->where('a.id_chauffeur = :chauffeur')
            ->andWhere('a.is_ferme = false')
            ->setParameter('chauffeur', $chauffeur);
            
        if ($excludeAffectationId) {
            $queryBuilder->andWhere('a.id != :excludeId')
                ->setParameter('excludeId', $excludeAffectationId);
        }
            
        $existingAffectation = $queryBuilder->getQuery()->getOneOrNullResult();
        
        return $existingAffectation !== null;
    }
}