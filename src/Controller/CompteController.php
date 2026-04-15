<?php

namespace App\Controller;

use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/compte')]
class CompteController extends AbstractController
{
    /**
     * Tableau de bord — résumé des messages et réponses reçues.
     */
    #[Route('', name: 'app_compte_dashboard')]
    public function dashboard(MessageRepository $messageRepo): Response
    {
        $user     = $this->getUser();
        $messages = $messageRepo->findRacinesParUser($user);

        // Compter les réponses non lues (messages dont l'expéditeur n'est pas l'user)
        $nonLues = 0;
        foreach ($messages as $msg) {
            foreach ($msg->getReponses() as $rep) {
                if (!$rep->isLu() && $rep->getExpediteur() !== $user) {
                    $nonLues++;
                }
            }
        }

        return $this->render('compte/dashboard.html.twig', [
            'messages'  => $messages,
            'non_lues'  => $nonLues,
        ]);
    }

    /**
     * Liste des conversations groupées par produit.
     */
    #[Route('/messages', name: 'app_compte_messages')]
    public function messages(MessageRepository $messageRepo): Response
    {
        $messages = $messageRepo->findRacinesParUser($this->getUser());

        return $this->render('compte/messages.html.twig', [
            'messages' => $messages,
        ]);
    }

    /**
     * Détail d'une conversation — fil de messages avec l'admin.
     */
    #[Route('/messages/{id}', name: 'app_compte_message_detail', requirements: ['id' => '\d+'])]
    public function messageDetail(
        int                    $id,
        Request                $request,
        MessageRepository      $messageRepo,
        EntityManagerInterface $em
    ): Response {
        $messageRacine = $messageRepo->find($id);

        // Vérifier que ce message appartient bien à l'utilisateur connecté
        if (!$messageRacine || $messageRacine->getExpediteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $fil = $messageRepo->findFilConversation($id);

        // Marquer les réponses de l'admin comme lues
        foreach ($fil as $msg) {
            if (!$msg->isLu() && $msg->getExpediteur() !== $this->getUser()) {
                $msg->setLu(true);
            }
        }
        $em->flush();

        // Formulaire de réponse
        if ($request->isMethod('POST')) {
            $contenu = trim($request->request->get('contenu', ''));
            if ($contenu !== '') {
                $reponse = new Message();
                $reponse->setContenu($contenu);
                $reponse->setProduit($messageRacine->getProduit());
                $reponse->setExpediteur($this->getUser());
                $reponse->setParentMessage($messageRacine);

                $em->persist($reponse);
                $em->flush();

                $this->addFlash('success', 'Réponse envoyée.');
                return $this->redirectToRoute('app_compte_message_detail', ['id' => $id]);
            }
        }

        return $this->render('compte/message_detail.html.twig', [
            'message_racine' => $messageRacine,
            'fil'            => $fil,
        ]);
    }

    /**
     * Profil utilisateur — modifier infos et mot de passe.
     */
    #[Route('/profil', name: 'app_compte_profil')]
    public function profil(
        Request                     $request,
        EntityManagerInterface      $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user   = $this->getUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $action = $request->request->get('_action');

            // Mise à jour des infos personnelles
            if ($action === 'infos') {
                $nom       = trim($request->request->get('nom', ''));
                $prenom    = trim($request->request->get('prenom', ''));
                $telephone = trim($request->request->get('telephone', ''));

                if (empty($nom))    $errors['nom']    = 'Le nom est requis.';
                if (empty($prenom)) $errors['prenom'] = 'Le prénom est requis.';

                if (empty($errors)) {
                    $user->setNom($nom)->setPrenom($prenom)->setTelephone($telephone ?: null);
                    $em->flush();
                    $this->addFlash('success', 'Informations mises à jour.');
                    return $this->redirectToRoute('app_compte_profil');
                }
            }

            // Changement de mot de passe
            if ($action === 'password') {
                $actuel    = $request->request->get('password_actuel', '');
                $nouveau   = $request->request->get('password_nouveau', '');
                $confirmer = $request->request->get('password_confirmer', '');

                if (!$hasher->isPasswordValid($user, $actuel)) {
                    $errors['password_actuel'] = 'Mot de passe actuel incorrect.';
                }
                if (strlen($nouveau) < 6) {
                    $errors['password_nouveau'] = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
                }
                if ($nouveau !== $confirmer) {
                    $errors['password_confirmer'] = 'Les mots de passe ne correspondent pas.';
                }

                if (empty($errors)) {
                    $user->setPassword($hasher->hashPassword($user, $nouveau));
                    $em->flush();
                    $this->addFlash('success', 'Mot de passe modifié avec succès.');
                    return $this->redirectToRoute('app_compte_profil');
                }
            }
        }

        return $this->render('compte/profil.html.twig', [
            'errors' => $errors,
        ]);
    }
}
