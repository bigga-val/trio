<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Service\ChatbotService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat')]
final class ChatController extends AbstractController
{
    #[Route('', name: 'app_chat_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository
    ): Response {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le client connecté
            $user = $this->getUser();
            
            $message->setIsFromClient(true);
            
            // Si l'utilisateur est un client connecté, associer le message au client
            if ($user instanceof Client) {
                $message->setClient($user);
                $message->setNomAuteur($user->getNomClient() ?? $user->getAdresseEmail());
            } else {
                // Fallback: récupérer du session ou utiliser 'Client Anonyme'
                $nomUtilisateur = $request->getSession()->get('user_name', 'Client Anonyme');
                $message->setNomAuteur($nomUtilisateur);
            }
            
            $message->setCreatedAt(new \DateTime());

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('app_chat_index');
        }

        // Récupérer uniquement les messages du client connecté
        $messages = [];
        $user = $this->getUser();
        
        if ($user instanceof Client) {
            // D'abord essayer par ID client si disponible
            if ($user->getId()) {
                $messages = $messageRepository->findConversationsByClientId($user->getId());
            } 
            // Si pas de messages trouvés, chercher par le nom du client
            if (empty($messages) && ($user->getNomClient() || $user->getAdresseEmail())) {
                $clientName = $user->getNomClient() ?? $user->getAdresseEmail();
                $messages = $messageRepository->findConversationsByClientName($clientName);
            }
        }

        return $this->render('chat/index.html.twig', [
            'form' => $form,
            'messages' => $messages,
        ]);
    }

    #[Route('/admin', name: 'app_chat_admin', methods: ['GET'])]
    public function admin(MessageRepository $messageRepository): Response
    {
        // Récupérer les derniers messages de chaque conversation
        $conversations = $messageRepository->findLatestConversations();

        // Filtrer les conversations valides (avec un nomAuteur)
        $validConversations = array_filter($conversations, function($conv) {
            return !empty($conv->getNomAuteur());
        });

        // Calculer le nombre de messages non-lus pour chaque conversation
        $unreadCounts = [];
        foreach ($validConversations as $conv) {
            $clientId = $conv->getClient()?->getId();
            $clientName = $conv->getNomAuteur();
            
            if ($clientId) {
                $unreadCounts[$clientId] = $messageRepository->countUnreadMessagesByClientId($clientId);
            } else {
                $unreadCounts[$clientName] = $messageRepository->countUnreadMessagesByClientName($clientName);
            }
        }

        // Si aucune conversation valide, rediriger vers le tableau de bord
        if (empty($validConversations)) {
            return $this->redirectToRoute('app_admin_tableau');
        }

        return $this->render('chat/admin.html.twig', [
            'conversations' => $validConversations,
            'selectedMessages' => [],
            'selectedClientId' => null,
            'selectedClientName' => null,
            'unreadCounts' => $unreadCounts,
        ]);
    }
//   pour repondre aux clients depuis l'interface admin

    #[Route('/admin/conversation', name: 'app_chat_admin_conversation', methods: ['GET'])]
    public function adminConversation(
        Request $request,
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository
    ): Response {
        $selectedClientId = $request->query->get('id');
        $selectedClientName = $request->query->get('name');
        
        $uniqueClients = $messageRepository->getUniqueClientNames();
        $conversations = [];
        
        // Récupérer le dernier message de chaque client
        foreach ($uniqueClients as $client) {
            if (!empty($client)) {
                $msgs = $messageRepository->findConversationsByClientName($client);
                if (!empty($msgs) && !empty($msgs[0]->getNomAuteur())) {
                    $conversations[] = $msgs[0];
                }
            }
        }
        
        // Calculer le nombre de messages non-lus pour chaque conversation
        $unreadCounts = [];
        foreach ($conversations as $conv) {
            $convClientId = $conv->getClient()?->getId();
            $convClientName = $conv->getNomAuteur();
            
            if ($convClientId) {
                $unreadCounts[$convClientId] = $messageRepository->countUnreadMessagesByClientId($convClientId);
            } else {
                $unreadCounts[$convClientName] = $messageRepository->countUnreadMessagesByClientName($convClientName);
            }
        }
        
        $selectedMessages = [];
        $selectedClientNameResult = null;
        $selectedClientIdResult = null;
        
        // Si clientId est fourni et valide, charger les messages par ID
        if ($selectedClientId && $selectedClientId > 0) {
            $selectedMessages = $messageRepository->findConversationsByClientId((int)$selectedClientId);
            if (!empty($selectedMessages)) {
                $selectedClientNameResult = $selectedMessages[0]->getNomAuteur();
                $selectedClientIdResult = $selectedClientId;
                
                // Marquer les messages du service client comme lus
                foreach ($selectedMessages as $msg) {
                    if (!$msg->isFromClient() && !$msg->isRead()) {
                        $msg->setIsRead(true);
                    }
                }
                $entityManager->flush();
            }
        } // Sinon si clientName est fourni, charger les messages par nom
        elseif ($selectedClientName) {
            $selectedMessages = $messageRepository->findConversationsByClientName($selectedClientName);
            if (!empty($selectedMessages)) {
                $selectedClientNameResult = $selectedClientName;
                $selectedClientIdResult = $selectedMessages[0]->getClient()?->getId();
                
                // Marquer les messages du service client comme lus
                foreach ($selectedMessages as $msg) {
                    if (!$msg->isFromClient() && !$msg->isRead()) {
                        $msg->setIsRead(true);
                    }
                }
                $entityManager->flush();
            }
        }

        return $this->render('chat/admin.html.twig', [
            'conversations' => $conversations,
            'selectedMessages' => $selectedMessages,
            'selectedClientId' => $selectedClientIdResult,
            'selectedClientName' => $selectedClientNameResult,
            'unreadCounts' => $unreadCounts,
        ]);
    }

    #[Route('/admin/reply', name: 'app_chat_admin_reply', methods: ['POST'])]
    public function adminReply(
        Request $request,
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository
    ): Response {
        $clientId = $request->request->get('client_id');
        $clientName = $request->request->get('client_name');
        $response = $request->request->get('response');

        if (!empty($response) && ($clientId || $clientName)) {
            $reponse = new Message();
            $reponse->setContenu($response);
            $reponse->setIsFromClient(false);
            $reponse->setNomAuteur('Service Client');
            $reponse->setCreatedAt(new \DateTime());

            // Si on a un clientId, charger le client
            if ($clientId && $clientId > 0) {
                $existingMessage = $messageRepository->findOneBy(['client' => $clientId]);
                if ($existingMessage && $existingMessage->getClient()) {
                    $reponse->setClient($existingMessage->getClient());
                }
            } // Sinon chercher par nom
            elseif ($clientName) {
                $existingMessage = $messageRepository->findOneBy(['nomAuteur' => $clientName]);
                if ($existingMessage && $existingMessage->getClient()) {
                    $reponse->setClient($existingMessage->getClient());
                }
            }

            $entityManager->persist($reponse);
            $entityManager->flush();
        }

        // Rediriger vers la conversation
        if ($clientId) {
            return $this->redirectToRoute('app_chat_admin_conversation', ['id' => $clientId]);
        } elseif ($clientName) {
            return $this->redirectToRoute('app_chat_admin_conversation', ['name' => $clientName]);
        }

        return $this->redirectToRoute('app_chat_admin');
    }

    #[Route('/message/{id}/edit', name: 'app_message_edit', methods: ['POST'])]
    public function editMessage(
        Message $message,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $content = $request->request->get('content');

        // Vérifier que l'utilisateur a le droit de modifier le message
        if (!$content) {
            return $this->json(['success' => false, 'error' => 'Content is required'], 400);
        }

        // Vérifier le CSRF
        if (!$this->isCsrfTokenValid('edit_message_' . $message->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        // Vérifier que c'est le client ou l'admin qui peut modifier
        if ($user instanceof Client) {
            // Le client ne peut modifier que ses propres messages
            if ($message->getClient() !== $user) {
                return $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        $message->setContenu($content);
        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Message updated successfully']);
    }

    #[Route('/message/{id}/delete', name: 'app_message_delete', methods: ['POST'])]
    public function deleteMessage(
        Message $message,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Vérifier le CSRF
        if (!$this->isCsrfTokenValid('delete_message_' . $message->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        // Vérifier que c'est le client qui peut supprimer (ou que c'est l'admin)
        if ($user instanceof Client) {
            if ($message->getClient() !== $user) {
                return $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        $entityManager->remove($message);
        $entityManager->flush();

        return $this->json(['success' => true, 'message' => 'Message deleted successfully']);
    }

    #[Route('/send-message', name: 'app_send_message', methods: ['POST'])]
    public function sendMessage(
        Request $request,
        EntityManagerInterface $entityManager,
        MessageRepository $messageRepository,
        ChatbotService $chatbotService
    ): Response {
        $user = $this->getUser();
        $contenu = $request->request->get('contenu', '');

        if (!$contenu) {
            return $this->json(['success' => false, 'error' => 'Message cannot be empty'], 400);
        }

        $message = new Message();
        $message->setContenu($contenu);
        $message->setIsFromClient(true);

        // Si l'utilisateur est un client connecté, associer le message au client
        if ($user instanceof Client) {
            $message->setClient($user);
            $message->setNomAuteur($user->getNomClient() ?? $user->getAdresseEmail());
        } else {
            // Fallback: récupérer du session ou utiliser 'Client Anonyme'
            $nomUtilisateur = $request->getSession()->get('user_name', 'Client Anonyme');
            $message->setNomAuteur($nomUtilisateur);
        }

        $message->setCreatedAt(new \DateTime());
        $entityManager->persist($message);
        $entityManager->flush();

        // Générer une réponse automatique du chatbot
        $botResponse = $chatbotService->createBotResponse($message);
        $entityManager->persist($botResponse);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'messages' => [
                [
                    'id' => $message->getId(),
                    'contenu' => $message->getContenu(),
                    'nomAuteur' => $message->getNomAuteur(),
                    'isFromClient' => $message->isFromClient(),
                    'createdAt' => $message->getCreatedAt()->format('H:i'),
                ],
                [
                    'id' => $botResponse->getId(),
                    'contenu' => $botResponse->getContenu(),
                    'nomAuteur' => $botResponse->getNomAuteur(),
                    'isFromClient' => $botResponse->isFromClient(),
                    'createdAt' => $botResponse->getCreatedAt()->format('H:i'),
                ]
            ]
        ]);
    }

    #[Route('/keep-alive', name: 'app_keep_alive', methods: ['POST'])]
    public function keepAlive(Request $request): Response
    {
        // Mettre à jour l'activité de la session
        $request->getSession()->set('last_activity', time());
        
        return $this->json(['status' => 'ok']);
    }
}
