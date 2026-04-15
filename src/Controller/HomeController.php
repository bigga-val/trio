<?php

namespace App\Controller;

use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    /**
     * Page d'accueil — Hero + services + produits vedettes + offres.
     */
    #[Route('/', name: 'app_home')]
    public function index(
        ProduitRepository  $produitRepo,
        CategorieRepository $categorieRepo
    ): Response {
        $produitsVedettes = $produitRepo->findDerniers(6);
        $categories       = $categorieRepo->findAll();

        return $this->render('home/index.html.twig', [
            'produits_vedettes' => $produitsVedettes,
            'categories'        => $categories,
        ]);
    }
}
