<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\MessageRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/tableau', name: 'app_admin_tableau', methods: ['GET'])]
    public function tableau(
        ClientRepository $clientRepository,
        MessageRepository $messageRepository,
        ProduitRepository $produitRepository
    ): Response
    {
        $totalClients = $clientRepository->count([]);
        $totalMessages = $messageRepository->count(['isFromClient' => false]);
        $totalProduits = $produitRepository->count([]);

        return $this->render('admin/tableau.html.twig', [
            'controller_name' => 'AdminController',
            'totalClients' => $totalClients,
            'totalMessages' => $totalMessages,
            'totalProduits' => $totalProduits,
        ]);
    }
}
