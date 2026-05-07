<?php
// src/Service/SearchService.php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SearchService
{
    private $em;
    private $weights;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params)
    {
        $this->em = $em;

        // Configuration des poids pour différents types de correspondance et entités
        $this->weights = $params->get('search_weights') ?? [
            'entities' => [
                'vehicule' => 1.0,
                'chauffeur' => 0.9,
                'voyage' => 0.8,
                'document' => 0.7,
                'affectation' => 0.7,
                'reparation' => 0.6,
            ],
            'matches' => [
                'exact' => 1.0,
                'beginning' => 0.8,
                'partial' => 0.6,
                'fuzzy' => 0.4,
            ]
        ];
    }

    public function search(string $query, int $page = 1, int $perPage = 10): array
    {
        if (empty($query)) {
            return [
                'results' => [],
                'total' => 0,
                'totalPages' => 0,
                'currentPage' => $page
            ];
        }

        // Préparer les termes de recherche
        $searchTerms = $this->prepareSearchTerms($query);

        // Rechercher dans les différentes entités
        $vehiculeResults = $this->searchVehicules($searchTerms);
        $chauffeurResults = $this->searchChauffeurs($searchTerms);
        $voyageResults = $this->searchVoyages($searchTerms);
        $documentResults = $this->searchDocuments($searchTerms);
        $affectationResults = $this->searchAffectations($searchTerms);
        $reparationResults = $this->searchReparations($searchTerms);

        // Fusionner tous les résultats
        $allResults = array_merge(
            $vehiculeResults,
            $chauffeurResults,
            $voyageResults,
            $documentResults,
            $affectationResults,
            $reparationResults
        );

        // Trier par score de pertinence (du plus élevé au plus bas)
        usort($allResults, fn($a, $b) => $b['score'] <=> $a['score']);

        // Pagination
        $totalCount = count($allResults);
        $paginatedResults = array_slice($allResults, ($page - 1) * $perPage, $perPage);

        return [
            'results' => $paginatedResults,
            'total' => $totalCount,
            'totalPages' => ceil($totalCount / $perPage),
            'currentPage' => $page
        ];
    }

    private function prepareSearchTerms(string $query): array
    {
        // Nettoyer et diviser la requête en termes
        $cleanedQuery = preg_replace('/[^\p{L}\p{N}\s]/u', '', $query);
        $terms = preg_split('/\s+/', $cleanedQuery, -1, PREG_SPLIT_NO_EMPTY);

        // Garder les termes significatifs (au moins 2 caractères)
        return array_filter($terms, fn($term) => mb_strlen($term) >= 2);
    }

    private function searchVehicules(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('v')
            ->from('App\Entity\Vehicule', 'v')
            ->leftJoin('v.modele_vehicule', 'm')
            ->leftJoin('v.id_marque', 'marque')
            ->where($qb->expr()->orX(
                $this->createSearchConditions($qb, $terms, 'v.immatriculation_vehicule', 'exact', 1.2),
                $this->createSearchConditions($qb, $terms, 'v.chassis_vehicule', 'exact', 1.1),
                $this->createSearchConditions($qb, $terms, 'm.nom_modele', 'partial', 1.0),
                $this->createSearchConditions($qb, $terms, 'marque.nom_marque', 'partial', 0.9)
            ));

        $vehicules = $qb->getQuery()->getResult();
        $results = [];

        foreach ($vehicules as $vehicule) {
            $score = 0;

            foreach ($terms as $term) {
                // Calculer le score basé sur les correspondances
                $score += $this->calculateMatchScore($term, $vehicule->getImmatriculationVehicule(), 'exact', 1.2);
                $score += $this->calculateMatchScore($term, $vehicule->getChassisVehicule(), 'exact', 1.1);

                if ($vehicule->getModeleVehicule()) {
                    $score += $this->calculateMatchScore($term, $vehicule->getModeleVehicule()->getNom(), 'partial', 1.0);
                }

                if ($vehicule->getIdMarque()) {
                    $score += $this->calculateMatchScore($term, $vehicule->getIdMarque()->getNom(), 'partial', 0.9);
                }
            }

            // Appliquer le poids de l'entité
            $score *= $this->weights['entities']['vehicule'];

            if ($score > 0) {
                $results[] = [
                    'title' => $vehicule->getImmatriculationVehicule() . ' - ' .
                        ($vehicule->getModeleVehicule() ? $vehicule->getModeleVehicule()->getNom() : ''),
                    'description' => 'Véhicule ' .
                        ($vehicule->getIdMarque() ? $vehicule->getIdMarque()->getNom() . ' ' : '') .
                        ($vehicule->getModeleVehicule() ? $vehicule->getModeleVehicule()->getNom() : ''),
                    'url' => '/vehicule/' . $vehicule->getId(),
                    'category' => 'Véhicules',
                    'score' => $score
                ];
            }
        }

        return $results;
    }

    private function searchChauffeurs(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('c')
            ->from('App\Entity\Chauffeur', 'c')
            ->where($qb->expr()->orX(
                $this->createSearchConditions($qb, $terms, 'c.nom', 'beginning', 1.0),
                $this->createSearchConditions($qb, $terms, 'c.prenom', 'beginning', 1.0),
                $this->createSearchConditions($qb, $terms, 'c.telephone', 'exact', 0.9)
            ));

        $chauffeurs = $qb->getQuery()->getResult();
        $results = [];

        foreach ($chauffeurs as $chauffeur) {
            $score = 0;

            foreach ($terms as $term) {
                $score += $this->calculateMatchScore($term, $chauffeur->getNom(), 'beginning', 1.0);
                $score += $this->calculateMatchScore($term, $chauffeur->getPrenom(), 'beginning', 1.0);
                $score += $this->calculateMatchScore($term, $chauffeur->getEmail(), 'partial', 0.8);
                $score += $this->calculateMatchScore($term, $chauffeur->getTelephone(), 'exact', 0.9);
            }

            // Appliquer le poids de l'entité
            $score *= $this->weights['entities']['chauffeur'];

            if ($score > 0) {
                $results[] = [
                    'title' => $chauffeur->getPrenom() . ' ' . $chauffeur->getNom(),
                    'description' => 'Chauffeur - ' . $chauffeur->getEmail(),
                    'url' => '/chauffeur/' . $chauffeur->getId(),
                    'category' => 'Chauffeurs',
                    'score' => $score
                ];
            }
        }

        return $results;
    }

    private function searchVoyages(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('v')
            ->from('App\Entity\Voyage', 'v')
            ->leftJoin('v.typeChargement', 'tc')
            ->leftJoin('v.voyageVehicules', 'vv')
            ->leftJoin('vv.destination', 'd')
            ->leftJoin('v.affectation', 'a') // Join through AffectationVehicule
            ->leftJoin('a.id_vehicule', 'veh') // Join vehicule from AffectationVehicule
            ->where($qb->expr()->orX(
                $this->createSearchConditions($qb, $terms, 'd.libelle', 'partial', 1.0),
                $this->createSearchConditions($qb, $terms, 'tc.libelle', 'partial', 0.9),
                $this->createSearchConditions($qb, $terms, 'v.titre', 'partial', 1.1),
                $this->createSearchConditions($qb, $terms, 'veh.immatriculation_vehicule', 'partial', 0.8)
            ));

        $voyages = $qb->getQuery()->getResult();
        $results = [];

        foreach ($voyages as $voyage) {
            $score = 0;

            foreach ($terms as $term) {
                // Calculate score for each destination in voyageVehicules
                foreach ($voyage->getVoyageVehicules() as $voyageVehicule) {
                    if ($voyageVehicule->getDestination()) {
                        $score += $this->calculateMatchScore($term, $voyageVehicule->getDestination()->getLibelle(), 'partial', 1.0);
                    }
                }

                if ($voyage->getTypeChargement()) {
                    $score += $this->calculateMatchScore($term, $voyage->getTypeChargement()->getLibelle(), 'partial', 0.9);
                }

                // Use titre instead of reference
                $score += $this->calculateMatchScore($term, $voyage->getTitre(), 'partial', 1.1);

                // Calculate score for vehicule from affectation
                if ($voyage->getAffectation() && $voyage->getAffectation()->getIdVehicule()) {
                    $score += $this->calculateMatchScore($term, $voyage->getAffectation()->getIdVehicule()->getImmatriculationVehicule(), 'partial', 0.8);
                }
            }

            // Apply entity weight
            $score *= $this->weights['entities']['voyage'];

            if ($score > 0) {
                // Build description with destinations
                $destinations = [];
                foreach ($voyage->getVoyageVehicules() as $voyageVehicule) {
                    if ($voyageVehicule->getDestination()) {
                        $destinations[] = $voyageVehicule->getDestination()->getLibelle();
                    }
                }

                // Get vehicle from affectation
                $vehiculeImmatriculation = null;
                if ($voyage->getAffectation() && $voyage->getAffectation()->getIdVehicule()) {
                    $vehiculeImmatriculation = $voyage->getAffectation()->getIdVehicule()->getImmatriculationVehicule();
                }

                $results[] = [
                    'title' => 'Voyage ' . $voyage->getTitre(),
                    'description' => (!empty($destinations) ? 'Destinations: ' . implode(', ', $destinations) : '') .
                        ($vehiculeImmatriculation ? ' - Véhicule: ' . $vehiculeImmatriculation : ''),
                    'url' => '/voyage/' . $voyage->getId(),
                    'category' => 'Voyages',
                    'score' => $score
                ];
            }
        }

        return $results;
    }

    private function searchDocuments(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('d')
            ->from('App\Entity\DocumentVehicule', 'd')
            ->leftJoin('d.vehicule', 'v')
            ->leftJoin('d.typeDocument', 'td')
            ->where($qb->expr()->orX(
                $this->createSearchConditions($qb, $terms, 'd.reference', 'exact', 1.2),
                $this->createSearchConditions($qb, $terms, 'td.nom', 'partial', 1.0),
                $this->createSearchConditions($qb, $terms, 'v.immatriculation_vehicule', 'partial', 0.9)
            ));

        $documents = $qb->getQuery()->getResult();
        $results = [];

        foreach ($documents as $document) {
            $score = 0;

            foreach ($terms as $term) {
                $score += $this->calculateMatchScore($term, $document->getReference(), 'exact', 1.2);

                if ($document->getTypeDocument()) {
                    $score += $this->calculateMatchScore($term, $document->getTypeDocument()->getNom(), 'partial', 1.0);
                }

                if ($document->getVehicule()) {
                    $score += $this->calculateMatchScore($term, $document->getVehicule()->getImmatriculationVehicule(), 'partial', 0.9);
                }
            }

            // Appliquer le poids de l'entité
            $score *= $this->weights['entities']['document'];

            if ($score > 0) {
                $results[] = [
                    'title' => 'Document ' . ($document->getTypeDocument() ? $document->getTypeDocument()->getNom() : '') .
                        ' - ' . $document->getReference(),
                    'description' => ($document->getVehicule() ? 'Véhicule: ' . $document->getVehicule()->getImmatriculationVehicule() : ''),
                    'url' => '/vehicule/document/' . $document->getVehicule()->getId().'/show/'. $document->getId(),
                    'category' => 'Documents',
                    'score' => $score
                ];
            }
        }

        return $results;
    }

    private function searchAffectations(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('App\Entity\AffectationVehicule', 'a')
            ->leftJoin('a.id_vehicule', 'v')
            ->leftJoin('a.id_chauffeur', 'c')
            ->leftJoin('a.id_mode_utilisation', 'mu')
            ->leftJoin('a.id_type_utilisation', 'tu')
            ->where($qb->expr()->orX(
                $this->createSearchConditions($qb, $terms, 'v.immatriculation_vehicule', 'partial', 0.9),
                $this->createSearchConditions($qb, $terms, 'c.nom', 'partial', 0.8),
                $this->createSearchConditions($qb, $terms, 'c.prenom', 'partial', 0.8),
                $this->createSearchConditions($qb, $terms, 'mu.nom', 'partial', 0.7),
                $this->createSearchConditions($qb, $terms, 'tu.nom', 'partial', 0.7)
            ));

        $affectations = $qb->getQuery()->getResult();
        $results = [];

        foreach ($affectations as $affectation) {
            $score = 0;

            foreach ($terms as $term) {
                if ($affectation->getIdVehicule()) {
                    $score += $this->calculateMatchScore($term, $affectation->getIdVehicule()->getImmatriculationVehicule(), 'partial', 0.9);
                }

                if ($affectation->getIdChauffeur()) {
                    $score += $this->calculateMatchScore($term, $affectation->getIdChauffeur()->getNom(), 'partial', 0.8);
                    $score += $this->calculateMatchScore($term, $affectation->getIdChauffeur()->getPrenom(), 'partial', 0.8);
                }

                if ($affectation->getIdModeUtilisation()) {
                    $score += $this->calculateMatchScore($term, $affectation->getIdModeUtilisation()->getNom(), 'partial', 0.7);
                }

                if ($affectation->getIdTypeUtilisation()) {
                    $score += $this->calculateMatchScore($term, $affectation->getIdTypeUtilisation()->getNom(), 'partial', 0.7);
                }
            }

            // Appliquer le poids de l'entité
            $score *= $this->weights['entities']['affectation'];

            if ($score > 0) {
                $title = 'Affectation ' . ($affectation->getIdVehicule() ? $affectation->getIdVehicule()->getImmatriculationVehicule() : '');
                $description = ($affectation->getIdChauffeur() ? 'Chauffeur: ' . $affectation->getIdChauffeur()->getPrenom() . ' ' . $affectation->getIdChauffeur()->getNom() : '');

                $results[] = [
                    'title' => $title,
                    'description' => $description,
                    'url' => '/affectation/vehicule/' . $affectation->getId(),
                    'category' => 'Affectations',
                    'score' => $score
                ];
            }
        }

        return $results;
    }

    private function searchReparations(array $terms): array
    {
        if (empty($terms)) {
            return [];
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('r')
            ->from('App\Entity\BonReparation', 'r')
            ->leftJoin('r.affectation', 'a')
            ->leftJoin('a.id_vehicule', 'v')
            ->leftJoin('r.prestataire', 'p')
            ->leftJoin('r.centreCout', 'cc')
            ->where($qb->expr()->orX(
                $this->createSearchConditions($qb, $terms, 'r.numero', 'exact', 1.2),
                $this->createSearchConditions($qb, $terms, 'r.diagnostic', 'partial', 1.0),
                $this->createSearchConditions($qb, $terms, 'v.immatriculation_vehicule', 'partial', 0.9),
                $this->createSearchConditions($qb, $terms, 'p.nom', 'partial', 0.8),
                $this->createSearchConditions($qb, $terms, 'cc.libelle', 'partial', 0.7)
            ));

        $reparations = $qb->getQuery()->getResult();
        $results = [];

        foreach ($reparations as $reparation) {
            $score = 0;
            $vehicule = $reparation->getAffectation()?->getIdVehicule();

            foreach ($terms as $term) {
                $score += $this->calculateMatchScore($term, $reparation->getNumero(), 'exact', 1.2);
                $score += $this->calculateMatchScore($term, $reparation->getDiagnostic(), 'partial', 1.0);

                if ($vehicule) {
                    $score += $this->calculateMatchScore($term, $vehicule->getImmatriculationVehicule(), 'partial', 0.9);
                }

                if ($reparation->getPrestataire()) {
                    $score += $this->calculateMatchScore($term, $reparation->getPrestataire()->getNom(), 'partial', 0.8);
                }

                if ($reparation->getCentreCout()) {
                    $score += $this->calculateMatchScore($term, $reparation->getCentreCout()->getLibelle(), 'partial', 0.7);
                }
            }

            $score *= $this->weights['entities']['reparation'];

            if ($score > 0) {
                $results[] = [
                    'title' => 'Réparation ' . $reparation->getNumero(),
                    'description' => ($vehicule ? 'Véhicule: ' . $vehicule->getImmatriculationVehicule() : '') .
                        ' - ' . $reparation->getDiagnostic(),
                    'url' => '/bon/reparation/' . $reparation->getId(),
                    'category' => 'Réparations',
                    'score' => $score
                ];
            }
        }

        return $results;
    }

    private function createSearchConditions($qb, array $terms, string $field, string $matchType, float $weight)
    {
        $conditions = $qb->expr()->orX();

        foreach ($terms as $term) {
            switch ($matchType) {
                case 'exact':
                    $conditions->add($qb->expr()->eq($field, $qb->expr()->literal($term)));
                    break;
                case 'beginning':
                    $conditions->add($qb->expr()->like($field, $qb->expr()->literal($term . '%')));
                    break;
                case 'partial':
                    $conditions->add($qb->expr()->like($field, $qb->expr()->literal('%' . $term . '%')));
                    break;
                case 'fuzzy':
                    $conditions->add($qb->expr()->like($field, $qb->expr()->literal('%' . $term . '%')));
                    break;
            }
        }

        return $conditions;
    }

    private function calculateMatchScore(string $term, ?string $fieldValue, string $matchType, float $weight): float
    {
        if (!$fieldValue) {
            return 0;
        }

        $term = mb_strtolower($term);
        $fieldValue = mb_strtolower($fieldValue);

        $score = 0;

        switch ($matchType) {
            case 'exact':
                if ($fieldValue === $term) {
                    $score = $weight * $this->weights['matches']['exact'];
                }
                break;

            case 'beginning':
                if (strpos($fieldValue, $term) === 0) {
                    $score = $weight * $this->weights['matches']['beginning'];
                }
                break;

            case 'partial':
                if (strpos($fieldValue, $term) !== false) {
                    $score = $weight * $this->weights['matches']['partial'];

                    // Bonus si le terme est long
                    if (strlen($term) > 3) {
                        $score *= 1.2;
                    }
                }
                break;

            case 'fuzzy':
                $similarity = $this->calculateLevenshteinSimilarity($term, $fieldValue);
                if ($similarity > 0.7) {
                    $score = $weight * $this->weights['matches']['fuzzy'] * $similarity;
                }
                break;
        }

        return $score;
    }

    private function calculateLevenshteinSimilarity(string $term, string $fieldValue): float
    {
        $termLength = mb_strlen($term);
        $fieldLength = mb_strlen($fieldValue);

        if ($termLength === 0 || $fieldLength === 0) {
            return 0;
        }

        // Calculer la distance de Levenshtein
        $levenshteinDistance = levenshtein($term, $fieldValue);

        // Calculer la similarité (1 - distance normalisée)
        $maxLength = max($termLength, $fieldLength);
        $similarity = 1 - ($levenshteinDistance / $maxLength);

        return max(0, $similarity);
    }
}
