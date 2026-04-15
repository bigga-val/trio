<?php

namespace App\Controller\Admin;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/utilisateurs')]
class AdminUserController extends AbstractController
{
    /**
     * Liste de tous les utilisateurs inscrits.
     */
    #[Route('', name: 'app_admin_users')]
    public function index(UserRepository $repo): Response
    {
        $users = $repo->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()->getResult();

        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * Activer ou désactiver un compte utilisateur.
     */
    #[Route('/{id}/toggle', name: 'app_admin_user_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(
        int                    $id,
        Request                $request,
        UserRepository         $repo,
        EntityManagerInterface $em
    ): Response {
        $user = $repo->find($id);
        if (!$user) throw $this->createNotFoundException();

        // Empêcher de se désactiver soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre statut.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (!$this->isCsrfTokenValid('toggle_user_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setIsActif(!$user->isActif());
        $em->flush();

        $etat = $user->isActif() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Compte de {$user->getNomComplet()} {$etat}.");
        return $this->redirectToRoute('app_admin_users');
    }
}
