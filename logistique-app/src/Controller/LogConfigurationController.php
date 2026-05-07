<?php

namespace App\Controller;

use App\Entity\LogConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/log-configuration')]
class LogConfigurationController extends AbstractController
{
    #[Route('/', name: 'app_log_configuration')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_dashboard'); 
        }
        
        // Récupérer la configuration existante ou en créer une nouvelle
        $logConfiguration = $entityManager->getRepository(LogConfiguration::class)->find(1);

        if (!$logConfiguration) {
            $logConfiguration = new LogConfiguration();
            $logConfiguration->setId(1);
            $logConfiguration->setCleaningFrequency(1); // Valeur par défaut: 1 jour
            $entityManager->persist($logConfiguration);
            $entityManager->flush();
        }

        if ($request->isMethod('POST')) {
            $cleaningFrequency = $request->request->get('cleaningFrequency');

            // Validation simple
            if (!is_numeric($cleaningFrequency) || $cleaningFrequency < 1) {
                $this->addFlash('error', 'La fréquence doit être un nombre supérieur ou égal à 1.');
            } else {
                $logConfiguration->setCleaningFrequency((int)$cleaningFrequency);
                $entityManager->flush();

                $this->addFlash('success', 'La configuration des logs a été mise à jour.');
            }

            return $this->redirectToRoute('app_log_configuration');
        }

        return $this->render('log_configuration/index.html.twig', [
            'configuration' => $logConfiguration,
        ]);
    }
}