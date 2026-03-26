<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DebugController extends AbstractController
{
    #[Route('/debug/clients', name: 'app_debug_clients')]
    public function listClients(ClientRepository $clientRepository): Response
    {
        $clients = $clientRepository->findAll();
        
        return $this->json([
            'total_clients' => count($clients),
            'clients' => array_map(fn($c) => [
                'id' => $c->getId(),
                'email' => $c->getAdresseEmail(),
                'nom' => $c->getNomClient(),
            ], $clients)
        ]);
    }

    #[Route('/debug/user', name: 'app_debug_user')]
    #[IsGranted('ROLE_USER')]
    public function currentUser(): Response
    {
        return $this->json([
            'user' => $this->getUser()->getAdresseEmail(),
            'roles' => $this->getUser()->getRoles(),
        ]);
    }
}
