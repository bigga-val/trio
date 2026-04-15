<?php

namespace App\Controller\Admin;

use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/commentaires')]
class AdminCommentaireController extends AbstractController
{
    /**
     * Liste des commentaires en attente de modération.
     */
    #[Route('', name: 'app_admin_commentaires')]
    public function index(CommentaireRepository $repo): Response
    {
        $enAttente = $repo->findEnAttente();

        // Récupérer aussi les commentaires déjà approuvés
        $approuves = $repo->createQueryBuilder('c')
            ->leftJoin('c.produit', 'p')->addSelect('p')
            ->where('c.isVisible = :v')->setParameter('v', true)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()->getResult();

        return $this->render('admin/commentaires/index.html.twig', [
            'en_attente' => $enAttente,
            'approuves'  => $approuves,
        ]);
    }

    /**
     * Approuver un commentaire (le rendre visible).
     */
    #[Route('/{id}/approuver', name: 'app_admin_commentaire_approuver', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approuver(
        int                    $id,
        Request                $request,
        CommentaireRepository  $repo,
        EntityManagerInterface $em
    ): Response {
        $commentaire = $repo->find($id);
        if (!$commentaire) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('action_commentaire_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_commentaires');
        }

        $commentaire->setIsVisible(true);
        $em->flush();

        $this->addFlash('success', 'Commentaire approuvé et publié.');
        return $this->redirectToRoute('app_admin_commentaires');
    }

    /**
     * Rejeter (supprimer) un commentaire.
     */
    #[Route('/{id}/rejeter', name: 'app_admin_commentaire_rejeter', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function rejeter(
        int                    $id,
        Request                $request,
        CommentaireRepository  $repo,
        EntityManagerInterface $em
    ): Response {
        $commentaire = $repo->find($id);
        if (!$commentaire) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('action_commentaire_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_admin_commentaires');
        }

        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé.');
        return $this->redirectToRoute('app_admin_commentaires');
    }
}
