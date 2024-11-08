<?php

namespace App\Controller;

use App\Entity\Services;
use App\Entity\Produit;
use App\Form\ServicesType;
use App\Repository\CategorieVehiculeRepository;
use App\Repository\ProduitRepository;
use App\Repository\ServicesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

  #[Route('services')]
final class ServicesController extends AbstractController
{
    #[Route('/indexService', name: 'app_services_index', methods: ['GET'])]
    public function index(ServicesRepository $servicesRepository): Response
    {
        return $this->render('services/index.html.twig', [
            'services' => $servicesRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_services_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ServicesRepository $servicesRepository): Response
    {
        $service = new Services();
        $form = $this->createForm(ServicesType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            return $this->redirectToRoute('app_services_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('services/new.html.twig', [
            'service' => $service,
            'form' => $form,
            'services'=>$servicesRepository->findAll()
        ]);
    }

    #[Route('/{id}', name: 'app_services_show', methods: ['GET'])]
    public function show(Services $service): Response
    {
        return $this->render('services/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_services_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Services $service, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ServicesType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_services_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('services/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_services_delete', methods: ['POST'])]
    public function delete(Request $request, Services $service, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($service);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_services_index', [], Response::HTTP_SEE_OTHER);
    }

    
     // affichage de differents services 

    // #[Route('/prodService', name:'Prodservice_id', methods:['GET', 'POST'])]
    // public function ServiceProdById(Request $request, ServicesRepository $servicesRepository,
    // ProduitRepository $produitRepository, CategorieVehiculeRepository $categorieVehiculeRepository){
    //  $service = $request->get('id');
    //  $serviceProuit = $produitRepository->findBy(['service'=>$service]);
    // //  dd($service);
    //  return $this->render('services/services.html.twig',[
    //     'categorie'=>$categorieVehiculeRepository->findAll(),
    //     'getServices'=>$serviceProuit,
    //     'FiterCat'=>$categorieVehiculeRepository->findAll(),
    //     'services'=>$servicesRepository->findAll(),  
    //  ]);
    // }
}
