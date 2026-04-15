<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/messages')]
class AdminMessageController extends AbstractController
{
    /**
     * Liste de tous les messages clients (non-lus en premier).
     */
    #[Route('', name: 'app_admin_messages')]
    public function index(MessageRepository $repo): Response
    {
        // Récupérer tous les messages racines
        $messages = $repo->createQueryBuilder('m')
            ->leftJoin('m.produit', 'p')->addSelect('p')
            ->leftJoin('m.expediteur', 'u')->addSelect('u')
            ->where('m.parentMessage IS NULL')
            ->orderBy('m.lu', 'ASC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/messages/index.html.twig', [
            'messages' => $messages,
        ]);
    }

    /**
     * Détail d'une conversation — réponse admin.
     */
    #[Route('/{id}', name: 'app_admin_message_detail', requirements: ['id' => '\d+'])]
    public function detail(
        int                    $id,
        Request                $request,
        MessageRepository      $messageRepo,
        EntityManagerInterface $em
    ): Response {
        $messageRacine = $messageRepo->find($id);
        if (!$messageRacine || !$messageRacine->isRacine()) {
            throw $this->createNotFoundException();
        }

        $fil = $messageRepo->findFilConversation($id);

        // Marquer comme lu
        if (!$messageRacine->isLu()) {
            $messageRacine->setLu(true);
            $em->flush();
        }

        // Réponse admin
        if ($request->isMethod('POST')) {
            $contenu = trim($request->request->get('contenu', ''));
            if ($contenu !== '') {
                $reponse = new Message();
                $reponse->setContenu($contenu);
                $reponse->setProduit($messageRacine->getProduit());
                $reponse->setExpediteur($this->getUser());
                $reponse->setParentMessage($messageRacine);
                $reponse->setLu(false); // Le client n'a pas encore lu

                $em->persist($reponse);
                $em->flush();

                $this->addFlash('success', 'Réponse envoyée au client.');
                return $this->redirectToRoute('app_admin_message_detail', ['id' => $id]);
            }
        }

        return $this->render('admin/messages/detail.html.twig', [
            'message_racine' => $messageRacine,
            'fil'            => $fil,
        ]);
    }
}
