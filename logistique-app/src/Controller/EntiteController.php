<?php

namespace App\Controller;

use App\Entity\Entite;
use App\Repository\EntiteRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/entite')]
class EntiteController extends AbstractController
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}

    private function getEntiteData(Entite $entite): array
    {
        return [
            'libelle' => $entite->getLibelle(),
            'statut' => $entite->getStatut()
        ];
    }

    #[Route('/', name: 'app_entite_index', methods: ['GET'])]
    public function index(EntiteRepository $entiteRepository): Response
    {
        return $this->render('entite/index.html.twig', [
            'entites' => $entiteRepository->findBy([], ['libelle' => 'ASC']),
        ]);
    }

    // Route pour obtenir toutes les entités au format JSON
    #[Route('/json', name: 'app_get_all_entites', methods: ['GET'])]
    public function getAllEntitesAsJson(EntiteRepository $entiteRepository): JsonResponse
    {
        // Récupère toutes les entités avec statut actif
        $entites = $entiteRepository->findBy(['statut' => true], ['libelle' => 'ASC']);

        $data = [];
        foreach ($entites as $entite) {
            $data[] = [
                'id' => $entite->getId(),
                'textContent' => $entite->getLibelle(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/new', name: 'app_entite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $entite = new Entite();

        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            
            // Validation des données
            if (empty($libelle)) {
                $this->addFlash('error', 'Le libellé est obligatoire.');
                return $this->render('entite/new.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                ]);
            }
            
            if (mb_strlen($libelle) > 255) {
                $this->addFlash('error', 'Le libellé ne doit pas dépasser 255 caractères.');
                return $this->render('entite/new.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                ]);
            }
            
            // Vérifier si une entité avec le même libellé existe déjà
            $existingEntite = $entityManager->getRepository(Entite::class)->findOneBy(['libelle' => $libelle]);
            if ($existingEntite) {
                $this->addFlash('error', 'Une entité avec ce libellé existe déjà.');
                return $this->render('entite/new.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                ]);
            }

            $entite->setLibelle($libelle);
            $entite->setStatut(true);

            // Validation de l'entité
            $errors = $validator->validate($entite);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('entite/new.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                ]);
            }

            try {
                $entityManager->persist($entite);
                $entityManager->flush();

                // Audit log pour la création
                $this->auditLogger->log(
                    'create',
                    Entite::class,
                    $entite->getId(),
                    ['new' => $this->getEntiteData($entite)]
                );

                // Si c'est une requête AJAX, retourner une réponse JSON
                if ($request->isXmlHttpRequest() || $request->request->get('ajax')) {
                    return $this->json([
                        'success' => true,
                        'id' => $entite->getId(),
                        'libelle' => $entite->getLibelle()
                    ]);
                }

                $this->addFlash('success', 'Entité créée avec succès.');
                return $this->redirectToRoute('app_entite_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'create',
                    Entite::class,
                    0,
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la création de l\'entité: ' . $e->getMessage());
                return $this->render('entite/new.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                ]);
            }
        }

        return $this->render('entite/new.html.twig', [
            'entite' => $entite,
        ]);
    }

    #[Route('/{id}', name: 'app_entite_show', methods: ['GET'])]
    public function show(Entite $entite): Response
    {
        return $this->render('entite/show.html.twig', [
            'entite' => $entite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_entite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entite $entite, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Sauvegarder les anciennes valeurs avant modification
        $oldData = $this->getEntiteData($entite);
        
        if ($request->isMethod('POST')) {
            $libelle = trim($request->request->get('libelle', ''));
            $statut = (bool)$request->request->get('statut', true);
            
            // Validation des données
            if (empty($libelle)) {
                $this->addFlash('error', 'Le libellé est obligatoire.');
                return $this->render('entite/edit.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                    'statut' => $statut,
                ]);
            }
            
            if (mb_strlen($libelle) > 255) {
                $this->addFlash('error', 'Le libellé ne doit pas dépasser 255 caractères.');
                return $this->render('entite/edit.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                    'statut' => $statut,
                ]);
            }
            
            // Vérifier si une autre entité avec le même libellé existe déjà
            $existingEntite = $entityManager->getRepository(Entite::class)->findOneBy(['libelle' => $libelle]);
            if ($existingEntite && $existingEntite->getId() !== $entite->getId()) {
                $this->addFlash('error', 'Une entité avec ce libellé existe déjà.');
                return $this->render('entite/edit.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                    'statut' => $statut,
                ]);
            }

            $entite->setLibelle($libelle);
            $entite->setStatut($statut);

            // Validation de l'entité
            $errors = $validator->validate($entite);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
                return $this->render('entite/edit.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                    'statut' => $statut,
                ]);
            }

            try {
                $entityManager->flush();
                
                // Audit log pour la modification avec anciennes et nouvelles valeurs
                $this->auditLogger->log(
                    'update',
                    Entite::class,
                    $entite->getId(),
                    [
                        'old' => $oldData,
                        'new' => $this->getEntiteData($entite)
                    ]
                );

                $this->addFlash('success', 'Entité modifiée avec succès.');
                return $this->redirectToRoute('app_entite_index');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'update',
                    Entite::class,
                    $entite->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la modification de l\'entité: ' . $e->getMessage());
                return $this->render('entite/edit.html.twig', [
                    'entite' => $entite,
                    'libelle' => $libelle,
                    'statut' => $statut,
                ]);
            }
        }

        return $this->render('entite/edit.html.twig', [
            'entite' => $entite,
        ]);
    }

    #[Route('/{id}', name: 'app_entite_delete', methods: ['POST'])]
    public function delete(Request $request, Entite $entite, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'entité est utilisée dans des affectations
        if ($entite->getAffectationVehicules()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette entité car elle est utilisée dans des affectations.');
            return $this->redirectToRoute('app_entite_show', ['id' => $entite->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $entite->getId(), $request->request->get('_token'))) {
            try {
                // Sauvegarder les données avant suppression pour l'audit
                $oldData = $this->getEntiteData($entite);
                
                $entityManager->remove($entite);
                $entityManager->flush();
                
                // Audit log pour la suppression
                $this->auditLogger->log(
                    'delete',
                    Entite::class,
                    $entite->getId(),
                    ['old' => $oldData]
                );

                $this->addFlash('success', 'Entité supprimée avec succès.');
            } catch (\Exception $e) {
                // Log de l'erreur
                $this->auditLogger->log(
                    'delete',
                    Entite::class,
                    $entite->getId(),
                    ['error' => $e->getMessage()],
                    'error'
                );
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'entité: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_entite_index');
    }
}