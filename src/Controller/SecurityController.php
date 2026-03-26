<?php

namespace App\Controller;

use App\Repository\CategorieVehiculeRepository;
use App\Repository\ServicesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, CategorieVehiculeRepository $categorieVehiculeRepository, ServicesRepository $servicesRepository): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('client/login.html.twig', [
            'adresseEmail' => $lastUsername,
            'error' => $error,
            'categorie'=>$categorieVehiculeRepository->findAll(),
            'autreServe'=>$servicesRepository->findAll(),
            'services'=>$servicesRepository->findAll(),  
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): Response
    {
        // This method can be blank - it will be intercepted by the logout key on your firewall
    }
}
