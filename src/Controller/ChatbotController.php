<?php

namespace App\Controller;

use App\Service\ChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/chatbot')]
#[IsGranted('ROLE_ADMIN')]
class ChatbotController extends AbstractController
{
    #[Route('', name: 'app_chatbot_index', methods: ['GET'])]
    public function index(ChatbotService $chatbotService): Response
    {
        $stats = $chatbotService->getStats();

        return $this->render('admin/chatbot/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/test', name: 'app_chatbot_test', methods: ['GET', 'POST'])]
    public function test(
        Request $request,
        ChatbotService $chatbotService
    ): Response {
        $response = null;
        $testMessage = '';

        if ($request->isMethod('POST')) {
            $testMessage = $request->request->get('message', '');
            if (!empty($testMessage)) {
                $response = $chatbotService->generateResponse($testMessage);
                $needsHuman = $chatbotService->needsHumanIntervention($testMessage);
            }
        }

        return $this->render('admin/chatbot/test.html.twig', [
            'testMessage' => $testMessage,
            'response' => $response,
            'needsHuman' => $needsHuman ?? false,
        ]);
    }
}