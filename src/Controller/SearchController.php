<?php

namespace App\Controller;

use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use App\Repository\AuteurRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function search(
        Request $request,
        LivreRepository $livreRepository,
        UserRepository $userRepository,
        AuteurRepository $auteurRepository,
        CategorieRepository $categorieRepository
    ): Response {
        $query = trim($request->query->get('q', ''));
        
        $results = [
            'livres' => [],
            'users' => [],
            'auteurs' => [],
            'categories' => [],
        ];
        
        if (strlen($query) >= 2) {
            // Search books
            $results['livres'] = $livreRepository->createQueryBuilder('l')
                ->leftJoin('l.auteur', 'a')
                ->leftJoin('l.categorie', 'c')
                ->where('l.titre LIKE :query')
                ->orWhere('l.isbn LIKE :query')
                ->orWhere('CONCAT(a.prenom, \' \', a.nom) LIKE :query')
                ->orWhere('c.designation LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(10)
                ->getQuery()
                ->getResult();
            
            // Search users (admin only)
            if ($this->isGranted('ROLE_ADMIN')) {
                $results['users'] = $userRepository->createQueryBuilder('u')
                    ->where('u.lastName LIKE :query')
                    ->orWhere('u.firstName LIKE :query')
                    ->orWhere('u.email LIKE :query')
                    ->orWhere('u.username LIKE :query')
                    ->setParameter('query', '%' . $query . '%')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();
            }
            
            // Search authors
            $results['auteurs'] = $auteurRepository->createQueryBuilder('a')
                ->where('a.nom LIKE :query')
                ->orWhere('a.prenom LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
            
            // Search categories
            $results['categories'] = $categorieRepository->createQueryBuilder('c')
                ->where('c.designation LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }
        
        $totalResults = count($results['livres']) + count($results['users']) + 
                        count($results['auteurs']) + count($results['categories']);
        
        return $this->render('search/results.html.twig', [
            'query' => $query,
            'results' => $results,
            'totalResults' => $totalResults,
        ]);
    }
    
    #[Route('/search/autocomplete', name: 'app_search_autocomplete', methods: ['GET'])]
    public function autocomplete(
        Request $request,
        LivreRepository $livreRepository,
        AuteurRepository $auteurRepository
    ): JsonResponse {
        $query = trim($request->query->get('q', ''));
        $suggestions = [];
        
        if (strlen($query) >= 2) {
            // Get book suggestions
            $livres = $livreRepository->createQueryBuilder('l')
                ->select('l.id', 'l.titre', 'l.image')
                ->where('l.titre LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(5)
                ->getQuery()
                ->getArrayResult();
            
            foreach ($livres as $livre) {
                $suggestions[] = [
                    'type' => 'livre',
                    'id' => $livre['id'],
                    'label' => $livre['titre'],
                    'image' => $livre['image'],
                    'url' => $this->generateUrl('app_livre_show', ['id' => $livre['id']]),
                ];
            }
            
            // Get author suggestions
            $auteurs = $auteurRepository->createQueryBuilder('a')
                ->select('a.id', 'a.prenom', 'a.nom')
                ->where('CONCAT(a.prenom, \' \', a.nom) LIKE :query')
                ->orWhere('a.nom LIKE :query')
                ->setParameter('query', '%' . $query . '%')
                ->setMaxResults(3)
                ->getQuery()
                ->getArrayResult();
            
            foreach ($auteurs as $auteur) {
                $suggestions[] = [
                    'type' => 'auteur',
                    'id' => $auteur['id'],
                    'label' => $auteur['prenom'] . ' ' . $auteur['nom'],
                    'url' => $this->generateUrl('app_auteur_show', ['id' => $auteur['id']]),
                ];
            }
        }
        
        return new JsonResponse($suggestions);
    }
}
