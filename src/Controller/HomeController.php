<?php

namespace App\Controller;

use App\Entity\CategorieVehicule;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitRepository;
use App\Entity\Produit;
use App\Repository\CategorieVehiculeRepository;
use App\Repository\ServicesRepository;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index( ProduitRepository $produitRepository, 
    CategorieVehiculeRepository $categorieVehiculeRepository,
    ServicesRepository $servicesRepository
    ): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'sliderProd'=> $produitRepository->findAll(),
            'topIntrant'=> $produitRepository->getIndexForIntrants(),
            'categorie'=>$categorieVehiculeRepository->findAll(),
            'services'=>$servicesRepository->findAll(),  
           
        ]);
    }

    // le detail du produit et affichage des categories

    #[Route('/produitDetail/{id}', name:'app_detail')]
    public function detailProduit(Produit $produit, CategorieVehiculeRepository $categorieVehiculeRepository, 
    ProduitRepository $produitRepository, ServicesRepository $servicesRepository
    
    ){
        return $this->render('home/detailProduit.html.twig', [
             'prod'=>$produit,
             'categorie'=>$categorieVehiculeRepository->findAll(),
             'services'=>$servicesRepository->findAll(),  
             'sliderProd'=>$produitRepository->findAll(),
             'autreServe'=>$servicesRepository->findAll(),
       
        ]);
    }

    // affichage produit selon categorie
    #[Route('prodByCategorie', name:'prod_by_categorie', methods: ['GET', 'POST'])]
    public function GetAllProdCategorie( Request $request,  CategorieVehiculeRepository $categorieVehiculeRepository, 
    ProduitRepository $produitRepository, ServicesRepository $servicesRepository){
          $cat = $request->get('id');
          $prodCategorie = $produitRepository->findBy(['categorie'=>$cat]);
        //   dd($prodCategorie);
          return $this->render( 'home/detailCategorie.html.twig', [
                 'getCateg'=>$prodCategorie,
                 'categorie'=>$categorieVehiculeRepository->findAll(),
                 'services'=>$servicesRepository->findAll(),
                 'FiterCat'=>$categorieVehiculeRepository->findAll(),
                 'autreServe'=>$servicesRepository->findAll()
                 

            ]);
    }

    // aller vers la page d administration

    #[Route('/tableau', name:"app_tableau")]
    public function AdminLogin(){
        return $this->render('admin/tableau.html.twig', [
    
       ]);
    }

    // slide produit vers la page autre produit

    #[Route('showSliderPro', name:"app_show_Slider",  methods:['GET'])]
    public function showSlider(ProduitRepository $produitRepository){
        return $this->render("extensions/autreProsuit.html.twig", [
            'sliderProd'=>$produitRepository->findAll()
        ]);
    }

    // categorie for navbar 

    public function navBarShow(CategorieVehiculeRepository $categorieVehiculeRepository,
    ServicesRepository $servicesRepository
    ){
        return $this->render('extensions/navbar.html.twig', [
             'categories'=>$categorieVehiculeRepository->findAll(),
             'services'=>$servicesRepository->findAll(),  
             
        ]);
    }
   
    // service for navbar
    #[Route('getService', name:'app_get_services', methods: ['GET'])]
    public function navService(ServicesRepository $servicesRepository){
        return $this->render('extensions/navbar.html.twig', [
            'services'=>$servicesRepository->findAll(),   
            'autreServe'=>$servicesRepository->findAll()
        ]);
    }


    // affichage de la boutique en ligne
     #[Route('/boutique', 'app_boutique', methods: ['GET','POST'])]
     public function Boutique(CategorieVehiculeRepository $categorieVehiculeRepository, ProduitRepository $produitRepository, 
    ServicesRepository $servicesRepository
     ){
        return $this->render('home/boutique.html.twig', [
            'boutique'=>$produitRepository->findAll(),
            'FiterCat'=>$categorieVehiculeRepository->findAll(),
            'categorie'=>$categorieVehiculeRepository->findAll(),
            'autreServe'=>$servicesRepository->findAll(),
            'services'=>$servicesRepository->findAll(),  

        ]);
     }

     // affichage de differents services 

    #[Route('prodService', name:'Prodservice_id', methods:['GET', 'POST'])]
    public function ServiceProdById(Request $request, ServicesRepository $servicesRepository,
    ProduitRepository $produitRepository, CategorieVehiculeRepository $categorieVehiculeRepository){
     $service = $request->get('id');
     $serviceProuit = $produitRepository->findBy(['service'=>$service]);
    //  dd($service);
     return $this->render('services/services.html.twig',[
        'categorie'=>$categorieVehiculeRepository->findAll(),
        'getServices'=>$serviceProuit,
        'FiterCat'=>$categorieVehiculeRepository->findAll(),
        'services'=>$servicesRepository->findAll(),  
        'autreServe'=>$servicesRepository->findAll()
     ]);
    }

}
