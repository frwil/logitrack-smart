<?php

namespace App\Controller;

use App\Entity\Vehicule;
use App\Enum\CarburantType;
use App\Enum\ImmatriculationType;
use App\Repository\VehiculeRepository;
use App\Repository\MarqueVehiculeRepository;
use App\Repository\ModeleVehiculeRepository;
use App\Repository\EntiteRepository;
use App\Repository\DocumentVehiculeRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/vehicule')]
class VehiculeController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    #[Route('/', name: 'app_vehicule_index', methods: ['GET'])]
    public function index(VehiculeRepository $vehiculeRepository, DocumentVehiculeRepository $documentVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder à la liste des véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $vehicules = $vehiculeRepository->findActiveVehicules();

        // Calcul des statistiques
        $vehiculesActifs = $vehiculeRepository->count(['statut' => true]);
        $dateExpirationLimite = new \DateTime('+30 days');
        $vehiculesAvecDocumentsExpirant = $documentVehiculeRepository->countDocumentsExpirant($dateExpirationLimite);
        $dateNow = new \DateTime();
        $vehiculesAvecDocumentsExpires = $documentVehiculeRepository->countDocumentsExpires($dateNow);
        $vehiculesAlerte = $documentVehiculeRepository->findDocumentsAlerte();

        return $this->render('vehicule/index.html.twig', [
            'vehicules' => $vehicules,
            'vehiculesActifs' => $vehiculesActifs,
            'vehiculesAvecDocumentsExpirant' => $vehiculesAvecDocumentsExpirant,
            'vehiculesAvecDocumentsExpires' => $vehiculesAvecDocumentsExpires,
            'vehiculesAlerte' => $vehiculesAlerte,
        ]);
    }

    #[Route('/new', name: 'app_vehicule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MarqueVehiculeRepository $marqueVehiculeRepository, ModeleVehiculeRepository $modeleVehiculeRepository, EntiteRepository $entiteRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vehicule.create')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour créer un véhicule.');
            return $this->redirectToRoute('app_vehicule_index');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Validation des données
            $errors = $this->validateVehiculeData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_vehicule_new');
            }

            try {
                $vehicule = new Vehicule();
                $vehicule->setImmatriculationVehicule($data['immatriculation_vehicule']);
                $vehicule->setPuissanceVehicule((int)$data['puissance_vehicule']);
                $vehicule->setChassisVehicule($data['chassis_vehicule'] ?? null);

                if (!empty($data['premiere_utilisation'])) {
                    $vehicule->setPremiereUtilisation(new \DateTimeImmutable($data['premiere_utilisation']));
                }

                if (!empty($data['expiration_carte_grise'])) {
                    $vehicule->setExpirationCarteGrise(new \DateTimeImmutable($data['expiration_carte_grise']));
                }

                $vehicule->setNbPlace((int)$data['nb_place']);
                $vehicule->setTypeCarburant(CarburantType::from($data['type_carburant']));
                $vehicule->setCapaciteConsommationVehicule((float)$data['capacite_consommation_vehicule']);
                $vehicule->setTypeImmatriculation(ImmatriculationType::from($data['type_immatriculation']));
                $vehicule->setStatut((bool)($data['statut'] ?? false));

                $marque = $marqueVehiculeRepository->find($data['id_marque']);
                if (!$marque) {
                    $this->addFlash('error', 'Marque de véhicule invalide.');
                    return $this->redirectToRoute('app_vehicule_new');
                }
                $vehicule->setIdMarque($marque);

                $modele = $modeleVehiculeRepository->find($data['id_modele_vehicule']);
                if (!$modele) {
                    $this->addFlash('error', 'Modèle de véhicule invalide.');
                    return $this->redirectToRoute('app_vehicule_new');
                }
                $vehicule->setModeleVehicule($modele);

                $entite = $entiteRepository->find($data['id_entite']);
                if (!$entite) {
                    $this->addFlash('error', 'Entité invalide.');
                    return $this->redirectToRoute('app_vehicule_new');
                }
                $vehicule->setIdEntite($entite);

                $entityManager->persist($vehicule);
                $entityManager->flush();

                // Log de l'action de création
                $this->auditLogger->log(
                    'create',
                    Vehicule::class,
                    $vehicule->getId(),
                    ['new' => [
                        'immatriculation_vehicule' => $data['immatriculation_vehicule'],
                        'puissance_vehicule' => (int)$data['puissance_vehicule'],
                        'chassis_vehicule' => $data['chassis_vehicule'] ?? null,
                        'premiere_utilisation' => !empty($data['premiere_utilisation']) ? $data['premiere_utilisation'] : null,
                        'expiration_carte_grise' => !empty($data['expiration_carte_grise']) ? $data['expiration_carte_grise'] : null,
                        'nb_place' => (int)$data['nb_place'],
                        'type_carburant' => $data['type_carburant'],
                        'capacite_consommation_vehicule' => (float)$data['capacite_consommation_vehicule'],
                        'type_immatriculation' => $data['type_immatriculation'],
                        'statut' => (bool)($data['statut'] ?? false),
                        'id_marque' => $data['id_marque'],
                        'id_modele_vehicule' => $data['id_modele_vehicule'],
                        'id_entite' => $data['id_entite']
                    ]],
                    'success'
                );

                $this->addFlash('success', 'Véhicule créé avec succès.');
                return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Log de l'échec de création
                $this->auditLogger->log(
                    'create',
                    Vehicule::class,
                    0,
                    ['attempted_data' => $data, 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création du véhicule: ' . $e->getMessage());
            }
        }

        return $this->render('vehicule/new.html.twig', [
            'marques' => $marqueVehiculeRepository->findAll(),
            'entites' => $entiteRepository->findAll(),
            'immatriculation_types' => ImmatriculationType::cases(),
            'carburant_types' => CarburantType::cases(),
        ]);
    }

    #[Route('/{id}', name: 'app_vehicule_show', methods: ['GET'])]
    public function show(Vehicule $vehicule): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour voir les détails de ce véhicule.');
            return $this->redirectToRoute('app_vehicule_index');
        }

        return $this->render('vehicule/show.html.twig', [
            'vehicule' => $vehicule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_vehicule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager, MarqueVehiculeRepository $marqueVehiculeRepository, ModeleVehiculeRepository $modeleVehiculeRepository, EntiteRepository $entiteRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vehicule.update')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour modifier ce véhicule.');
            return $this->redirectToRoute('app_vehicule_index');
        }

        // Sauvegarde des anciennes valeurs pour le log
        $oldData = [
            'immatriculation_vehicule' => $vehicule->getImmatriculationVehicule(),
            'puissance_vehicule' => $vehicule->getPuissanceVehicule(),
            'chassis_vehicule' => $vehicule->getChassisVehicule(),
            'premiere_utilisation' => $vehicule->getPremiereUtilisation() ? $vehicule->getPremiereUtilisation()->format('Y-m-d') : null,
            'expiration_carte_grise' => $vehicule->getExpirationCarteGrise() ? $vehicule->getExpirationCarteGrise()->format('Y-m-d') : null,
            'nb_place' => $vehicule->getNbPlace(),
            'type_carburant' => $vehicule->getTypeCarburant()->value,
            'capacite_consommation_vehicule' => $vehicule->getCapaciteConsommationVehicule(),
            'type_immatriculation' => $vehicule->getTypeImmatriculation()->value,
            'statut' => $vehicule->getStatut(),
            'id_marque' => $vehicule->getIdMarque()->getId(),
            'id_modele_vehicule' => $vehicule->getModeleVehicule() ? $vehicule->getModeleVehicule()->getId() : '',
            'id_entite' => $vehicule->getIdEntite() ? $vehicule->getIdEntite()->getId() : ''
        ];

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            // Validation des données
            $errors = $this->validateVehiculeData($data);
            
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->redirectToRoute('app_vehicule_edit', ['id' => $vehicule->getId()]);
            }

            try {
                $vehicule->setImmatriculationVehicule($data['immatriculation_vehicule']);
                $vehicule->setPuissanceVehicule((int)$data['puissance_vehicule']);
                $vehicule->setChassisVehicule($data['chassis_vehicule'] ?? null);

                if (!empty($data['premiere_utilisation'])) {
                    $vehicule->setPremiereUtilisation(new \DateTimeImmutable($data['premiere_utilisation']));
                } else {
                    $vehicule->setPremiereUtilisation(null);
                }

                if (!empty($data['expiration_carte_grise'])) {
                    $vehicule->setExpirationCarteGrise(new \DateTimeImmutable($data['expiration_carte_grise']));
                } else {
                    $vehicule->setExpirationCarteGrise(null);
                }

                $vehicule->setNbPlace((int)$data['nb_place']);
                $vehicule->setTypeCarburant(CarburantType::from($data['type_carburant']));
                $vehicule->setCapaciteConsommationVehicule((float)$data['capacite_consommation_vehicule']);
                $vehicule->setTypeImmatriculation(ImmatriculationType::from($data['type_immatriculation']));
                $vehicule->setStatut((bool)($data['statut'] ?? false));

                $marque = $marqueVehiculeRepository->find($data['id_marque']);
                if (!$marque) {
                    $this->addFlash('error', 'Marque de véhicule invalide.');
                    return $this->redirectToRoute('app_vehicule_edit', ['id' => $vehicule->getId()]);
                }
                $vehicule->setIdMarque($marque);

                $modele = $modeleVehiculeRepository->find($data['id_modele_vehicule']);
                if (!$modele) {
                    $this->addFlash('error', 'Modèle de véhicule invalide.');
                    return $this->redirectToRoute('app_vehicule_edit', ['id' => $vehicule->getId()]);
                }
                $vehicule->setModeleVehicule($modele);

                $entite = $entiteRepository->find($data['id_entite']);
                if (!$entite) {
                    $this->addFlash('error', 'Entité invalide.');
                    return $this->redirectToRoute('app_vehicule_edit', ['id' => $vehicule->getId()]);
                }
                $vehicule->setIdEntite($entite);

                $entityManager->flush();

                // Log de l'action de modification avec anciennes et nouvelles valeurs
                $newData = [
                    'immatriculation_vehicule' => $data['immatriculation_vehicule'],
                    'puissance_vehicule' => (int)$data['puissance_vehicule'],
                    'chassis_vehicule' => $data['chassis_vehicule'] ?? null,
                    'premiere_utilisation' => !empty($data['premiere_utilisation']) ? $data['premiere_utilisation'] : null,
                    'expiration_carte_grise' => !empty($data['expiration_carte_grise']) ? $data['expiration_carte_grise'] : null,
                    'nb_place' => (int)$data['nb_place'],
                    'type_carburant' => $data['type_carburant'],
                    'capacite_consommation_vehicule' => (float)$data['capacite_consommation_vehicule'],
                    'type_immatriculation' => $data['type_immatriculation'],
                    'statut' => (bool)($data['statut'] ?? false),
                    'id_marque' => $data['id_marque'],
                    'id_modele_vehicule' => $data['id_modele_vehicule'],
                    'id_entite' => $data['id_entite']
                ];

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
                        Vehicule::class,
                        $vehicule->getId(),
                        $changes,
                        'success'
                    );
                }

                $this->addFlash('success', 'Véhicule mis à jour avec succès.');
                return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
            } catch (\Exception $e) {
                // Log de l'échec de modification
                $this->auditLogger->log(
                    'update',
                    Vehicule::class,
                    $vehicule->getId(),
                    ['attempted_data' => $data, 'error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour du véhicule: ' . $e->getMessage());
            }
        }

        return $this->render('vehicule/edit.html.twig', [
            'vehicule' => $vehicule,
            'marques' => $marqueVehiculeRepository->findAll(),
            'entites' => $entiteRepository->findAll(),
            'immatriculation_types' => ImmatriculationType::cases(),
            'carburant_types' => CarburantType::cases(),
        ]);
    }

    #[Route('/{id}', name: 'app_vehicule_delete', methods: ['POST'])]
    public function delete(Request $request, Vehicule $vehicule, EntityManagerInterface $entityManager): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vehicule.delete')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour supprimer ce véhicule.');
            return $this->redirectToRoute('app_vehicule_index');
        }

        if ($this->isCsrfTokenValid('delete' . $vehicule->getId(), $request->request->get('_token'))) {
            try {
                // Log de l'action de suppression avec les données de l'entité
                $this->auditLogger->log(
                    'delete',
                    Vehicule::class,
                    $vehicule->getId(),
                    ['old' => [
                        'immatriculation_vehicule' => $vehicule->getImmatriculationVehicule(),
                        'puissance_vehicule' => $vehicule->getPuissanceVehicule(),
                        'chassis_vehicule' => $vehicule->getChassisVehicule(),
                        'premiere_utilisation' => $vehicule->getPremiereUtilisation() ? $vehicule->getPremiereUtilisation()->format('Y-m-d') : null,
                        'expiration_carte_grise' => $vehicule->getExpirationCarteGrise() ? $vehicule->getExpirationCarteGrise()->format('Y-m-d') : null,
                        'nb_place' => $vehicule->getNbPlace(),
                        'type_carburant' => $vehicule->getTypeCarburant()->value,
                        'capacite_consommation_vehicule' => $vehicule->getCapaciteConsommationVehicule(),
                        'type_immatriculation' => $vehicule->getTypeImmatriculation()->value,
                        'statut' => $vehicule->getStatut(),
                        'id_marque' => $vehicule->getIdMarque()->getId(),
                        'id_modele_vehicule' => $vehicule->getModeleVehicule()->getId(),
                        'id_entite' => $vehicule->getIdEntite()->getId()
                    ]],
                    'success'
                );

                $entityManager->remove($vehicule);
                $entityManager->flush();
                $this->addFlash('success', 'Véhicule supprimé avec succès.');
            } catch (\Exception $e) {
                // Log de l'échec de suppression
                $this->auditLogger->log(
                    'delete',
                    Vehicule::class,
                    $vehicule->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression du véhicule: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_vehicule_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/vehicules/alertes', name: 'app_vehicule_alertes', methods: ['GET'])]
    public function alertes(DocumentVehiculeRepository $documentVehiculeRepository): Response
    {
        // Vérification manuelle de la permission
        if (!$this->isGranted('vehicule.view')) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions nécessaires pour accéder aux alertes véhicules.');
            return $this->redirectToRoute('app_homepage_index');
        }

        $vehiculesAlerte = $documentVehiculeRepository->findDocumentsAlerte();

        // Regrouper par véhicule pour une meilleure organisation
        $vehiculesAvecAlertes = [];
        foreach ($vehiculesAlerte as $alerte) {
            $vehiculeId = $alerte['vehicule']->getId();
            if (!isset($vehiculesAvecAlertes[$vehiculeId])) {
                $vehiculesAvecAlertes[$vehiculeId] = [
                    'vehicule' => $alerte['vehicule'],
                    'documents' => []
                ];
            }
            $vehiculesAvecAlertes[$vehiculeId]['documents'][] = [
                'document' => $alerte['document'],
                'joursRestants' => $alerte['joursRestants']
            ];
        }

        return $this->render('vehicule/alertes.html.twig', [
            'vehiculesAvecAlertes' => $vehiculesAvecAlertes,
        ]);
    }

    /**
     * Valider les données du formulaire véhicule
     */
    private function validateVehiculeData(array $data): array
    {
        $errors = [];

        if (empty($data['immatriculation_vehicule'])) {
            $errors[] = 'L\'immatriculation est obligatoire.';
        }

        if (empty($data['puissance_vehicule']) || !is_numeric($data['puissance_vehicule'])) {
            $errors[] = 'La puissance est obligatoire et doit être un nombre.';
        }

        if (empty($data['nb_place']) || !is_numeric($data['nb_place'])) {
            $errors[] = 'Le nombre de places est obligatoire et doit être un nombre.';
        }

        if (empty($data['type_carburant']) || !CarburantType::tryFrom($data['type_carburant'])) {
            $errors[] = 'Le type de carburant est obligatoire et doit être valide.';
        }

        if (empty($data['capacite_consommation_vehicule']) || !is_numeric($data['capacite_consommation_vehicule'])) {
            $errors[] = 'La capacité de consommation est obligatoire et doit être un nombre.';
        }

        if (empty($data['type_immatriculation']) || !ImmatriculationType::tryFrom($data['type_immatriculation'])) {
            $errors[] = 'Le type d\'immatriculation est obligatoire et doit être valide.';
        }

        if (empty($data['id_marque'])) {
            $errors[] = 'La marque est obligatoire.';
        }

        if (empty($data['id_modele_vehicule'])) {
            $errors[] = 'Le modèle est obligatoire.';
        }

        if (empty($data['id_entite'])) {
            $errors[] = 'L\'entité est obligatoire.';
        }

        if (!empty($data['premiere_utilisation']) && !\DateTimeImmutable::createFromFormat('Y-m-d', $data['premiere_utilisation'])) {
            $errors[] = 'Le format de la date de première utilisation est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        if (!empty($data['expiration_carte_grise']) && !\DateTimeImmutable::createFromFormat('Y-m-d', $data['expiration_carte_grise'])) {
            $errors[] = 'Le format de la date d\'expiration de la carte grise est invalide. Utilisez le format AAAA-MM-JJ.';
        }

        return $errors;
    }
}