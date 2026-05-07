<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SearchService;

class SearchController extends AbstractController
{
    #[Route('/search/ajax', name: 'app_search_ajax')]
    public function ajaxSearch(Request $request, SearchService $searchService): JsonResponse
    {
        $query = $request->query->get('q', '');
        $page = $request->query->get('page', 1);
        
        $results = $searchService->search($query, $page);
        
        return $this->json($results);
    }
    
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, SearchService $searchService)
    {
        $query = $request->query->get('q', '');
        $selectedRegionId = $request->getSession()->get('selected_region');
        $page = $request->query->get('page', 1);
        
        $results = $searchService->search($query, $page, 15);
        //dd($results);
        
        return $this->render('search/results.html.twig', [
            'results' => $results['results'],
            'query' => $query,
            'total' => $results['total'],
            'totalPages' => $results['totalPages'],
            'currentPage' => $results['currentPage'],
            
        ]);
    }
}
