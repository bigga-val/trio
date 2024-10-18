<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitRepository;
use App\Entity\Produit;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index( ProduitRepository $produitRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'sliderProd'=> $produitRepository->findAll(),
            'topIntrant'=> $produitRepository->getIndexForIntrants()
        ]);
    }

    // le detail du produit

    #[Route('/produitDetail/{id}', name:'app_detail')]
    public function detailProduit(Produit $produit){
        return $this->render('home/detailProduit.html.twig', [
             'prod'=>$produit
        ]);
    }

    // aller vers la page d administration

    #[Route('/tableau', name:"app_tableau")]
    public function AdminLogin(){
        return $this->render('admin/tableau.html.twig', [
    
       ]);
    }

}
