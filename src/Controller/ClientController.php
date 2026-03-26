<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\CategorieVehicule;
use App\Repository\CategorieVehiculeRepository;
use App\Entity\Produit;
use App\Form\ClientType;
use App\Repository\ClientRepository;
use App\Repository\ServicesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/client')]
final class ClientController extends AbstractController
{
    #[Route(name: 'app_client_index', methods: ['GET'])]
    public function index(ClientRepository $clientRepository, ServicesRepository $servicesRepository): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $clientRepository->findAll(),
            'autreServe'=>$servicesRepository->findAll(),
            'services'=>$servicesRepository->findAll(),  

        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, 
    CategorieVehiculeRepository $categorieVehiculeRepository, ServicesRepository $servicesRepository,
    UserPasswordHasherInterface $passwordHasher): Response
    {
        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $client->setRoles(['ROLE_USER']);
            
            // Hasher le mot de passe
            if ($client->getPassword()) {
                $hashedPassword = $passwordHasher->hashPassword($client, $client->getPassword());
                $client->setPassword($hashedPassword);
            }
            
            $entityManager->persist($client);
            $entityManager->flush();

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
            'categorie'=>$categorieVehiculeRepository->findAll(),
            'autreServe'=>$servicesRepository->findAll(),
            'services'=>$servicesRepository->findAll(),  

        ]);
    }

    #[Route('/clientHome', name:'client_home')]
    public function clientPage(CategorieVehiculeRepository $categorieVehiculeRepository){
        return $this->render('client/new.html.twig', [
            'categorie'=>$categorieVehiculeRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager, 
    UserPasswordHasherInterface $passwordHasher): Response
    {
        // Sauvegarder le mot de passe d'origine pour vérifier les changements
        $originalPassword = $client->getPassword();
        
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le mot de passe a changé, le hasher
            if ($client->getPassword() !== $originalPassword && $client->getPassword()) {
                $hashedPassword = $passwordHasher->hashPassword($client, $client->getPassword());
                $client->setPassword($hashedPassword);
            }
            
            $entityManager->flush();

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }

    // pour la connexion du client
    #[Route('login', name:'login_client', methods: ['GET', 'POST'] )]
    public function loginUser(Request $request, ClientRepository $clientRepository): Response{
        // $passwordClient = $password->hashPassword($password, $client->getPassword());
        // $password->setPassword($passwordClient);
        return $this->render('client/login.html.twig');
    }
}
