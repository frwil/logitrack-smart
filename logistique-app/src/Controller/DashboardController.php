<?php

namespace App\Controller;

use App\Repository\VehiculeRepository;
use App\Repository\ChauffeurRepository;
use App\Repository\VoyageRepository;
use App\Repository\AffectationVehiculeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        VehiculeRepository $vehiculeRepository,
        ChauffeurRepository $chauffeurRepository,
        VoyageRepository $voyageRepository,
        AffectationVehiculeRepository $affectationRepository
    ): Response {
        // Cette route nécessite une authentification
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Récupérer les statistiques
        $stats = [
            'vehicules' => $vehiculeRepository->count(['statut' => true]),
            'chauffeurs' => $chauffeurRepository->count(['estActif' => true]),
            'voyages' => $voyageRepository->countThisMonth(),
            'affectations_actives' => $affectationRepository->count(['is_ferme' => false]),
        ];

        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
        ]);
    }
}