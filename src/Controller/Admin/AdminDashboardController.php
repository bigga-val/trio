<?php

namespace App\Controller\Admin;

use App\Repository\CommentaireRepository;
use App\Repository\MessageRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminDashboardController extends AbstractController
{
    /**
     * Dashboard admin — statistiques globales du site.
     */
    #[Route('', name: 'app_admin_dashboard')]
    public function index(
        ProduitRepository    $produitRepo,
        MessageRepository    $messageRepo,
        CommentaireRepository $commentaireRepo,
        UserRepository       $userRepo
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'nb_produits'      => count($produitRepo->findAll()),
            'nb_messages_nlus' => $messageRepo->countNonLus(),
            'nb_commentaires'  => count($commentaireRepo->findEnAttente()),
            'nb_users'         => count($userRepo->findActifs()),
        ]);
    }
}
