<?php
// src/Controller/RegionFilterController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RegionFilterController extends AbstractController
{
    #[Route('/change-region', name: 'app_change_region', methods: ['POST'])]
    public function changeRegion(Request $request): Response
    {
        $regionId = $request->request->get('region_id');
        $session = $request->getSession();
        
        if ($regionId === '') {
            $session->remove('selected_region');
        } else {
            $session->set('selected_region', $regionId);
        }
        
        return new Response('OK', 200);
    }
}