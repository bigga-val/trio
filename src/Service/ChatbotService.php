<?php

namespace App\Service;

use App\Entity\Message;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class ChatbotService
{
    private $entityManager;
    private $produitRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository
    ) {
        $this->entityManager = $entityManager;
        $this->produitRepository = $produitRepository;
    }

    /**
     * Génère une réponse automatique basée sur le message du client
     */
    public function generateResponse(string $userMessage, ?Message $lastMessage = null): ?string
    {
        $message = strtolower(trim($userMessage));

        // Réponses pour les questions courantes sur les produits
        if ($this->containsKeywords($message, ['prix', 'coût', 'combien', 'tarif'])) {
            return $this->handlePriceQuestion($message);
        }

        if ($this->containsKeywords($message, ['disponible', 'stock', 'en stock', 'livraison'])) {
            return $this->handleAvailabilityQuestion($message);
        }

        if ($this->containsKeywords($message, ['commande', 'acheter', 'commander', 'achat'])) {
            return $this->handleOrderQuestion($message);
        }

        if ($this->containsKeywords($message, ['contact', 'téléphone', 'email', 'adresse'])) {
            return $this->handleContactQuestion($message);
        }

        if ($this->containsKeywords($message, ['bonjour', 'salut', 'hello', 'hi'])) {
            return "Bonjour ! Je suis l'assistant virtuel de TrioBusiness. Je peux vous aider avec des informations sur nos produits, prix, disponibilité et commandes. Comment puis-je vous aider aujourd'hui ?";
        }

        if ($this->containsKeywords($message, ['merci', 'thank', 'thanks'])) {
            return "De rien ! N'hésitez pas à me poser d'autres questions si vous avez besoin d'aide.";
        }

        if ($this->containsKeywords($message, ['au revoir', 'bye', 'ciao'])) {
            return "Au revoir ! Passez une excellente journée. Si vous avez d'autres questions, n'hésitez pas à revenir.";
        }

        // Questions sur les produits spécifiques
        if ($this->containsKeywords($message, ['phosphate', 'dap', 'engrais'])) {
            return "Nous proposons différents types d'engrais phosphatés comme le phosphate diammonique (DAP). Pour connaître les prix et disponibilités, pouvez-vous me préciser le produit qui vous intéresse ?";
        }

        // Si aucune réponse automatique ne correspond, retourner null
        // Cela indiquera qu'un humain doit prendre le relais
        return null;
    }

    /**
     * Vérifie si le message contient certains mots-clés
     */
    private function containsKeywords(string $message, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gère les questions sur les prix
     */
    private function handlePriceQuestion(string $message): string
    {
        $produits = $this->produitRepository->findAll();

        if (empty($produits)) {
            return "Je ne peux pas accéder aux informations de prix pour le moment. Un conseiller va vous contacter sous peu.";
        }

        $response = "Voici nos produits et leurs prix :\n\n";

        foreach ($produits as $produit) {
            $response .= "• " . $produit->getNomProduit() . " : " .
                        $produit->getPrixProduit() . " " .
                        $produit->getDevise()->getDesignation() . "\n";
        }

        $response .= "\nLes prix sont sujets à variation selon les quantités et conditions. Contactez-nous pour un devis personnalisé.";

        return $response;
    }

    /**
     * Gère les questions sur la disponibilité
     */
    private function handleAvailabilityQuestion(string $message): string
    {
        return "Tous nos produits sont généralement disponibles. Cependant, les stocks peuvent varier selon la demande saisonnière. Pour une vérification précise de disponibilité, veuillez nous contacter directement avec le nom du produit qui vous intéresse.";
    }

    /**
     * Gère les questions sur les commandes
     */
    private function handleOrderQuestion(string $message): string
    {
        return "Pour passer une commande, vous pouvez :\n\n" .
               "1. Utiliser le formulaire de contact sur notre site\n" .
               "2. Nous appeler directement\n" .
               "3. Nous envoyer un email avec vos besoins\n\n" .
               "Nous vous répondrons dans les plus brefs délais avec un devis personnalisé.";
    }

    /**
     * Gère les questions sur le contact
     */
    private function handleContactQuestion(string $message): string
    {
        return "Vous pouvez nous contacter :\n\n" .
               "📧 Email : contact@triobusiness.com\n" .
               "📞 Téléphone : +225 XX XX XX XX XX\n" .
               "🏢 Adresse : [Votre adresse]\n\n" .
               "Nous sommes disponibles du lundi au vendredi, 8h-17h.";
    }

    /**
     * Détermine si un message nécessite une intervention humaine
     */
    public function needsHumanIntervention(string $userMessage): bool
    {
        $message = strtolower(trim($userMessage));

        // Mots-clés qui indiquent qu'un humain doit intervenir
        $humanKeywords = [
            'urgent', 'urgence', 'problème', 'réclamation', 'plainte',
            'remboursement', 'retour', 'défaut', 'cassé', 'endommagé',
            'réparation', 'service après-vente', 'sav', 'garantie',
            'personnel', 'directeur', 'responsable', 'chef',
            'rendez-vous', 'visite', 'rencontre', 'réunion'
        ];

        foreach ($humanKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        // Si le message est très long (> 200 caractères), possiblement complexe
        if (strlen($message) > 200) {
            return true;
        }

        // Si le message contient beaucoup de points d'interrogation
        if (substr_count($message, '?') > 2) {
            return true;
        }

        return false;
    }

    /**
     * Crée une réponse automatique du chatbot
     */
    public function createBotResponse(Message $userMessage): Message
    {
        $botResponse = new Message();
        $botResponse->setIsFromClient(false);
        $botResponse->setNomAuteur('Assistant TrioBusiness 🤖');
        $botResponse->setCreatedAt(new \DateTime());

        // Associer au même client que le message utilisateur
        if ($userMessage->getClient()) {
            $botResponse->setClient($userMessage->getClient());
        }

        $autoResponse = $this->generateResponse($userMessage->getContenu(), $userMessage);

        if ($autoResponse) {
            $botResponse->setContenu($autoResponse);
        } else {
            // Réponse par défaut quand aucune réponse automatique ne correspond
            $botResponse->setContenu(
                "Merci pour votre message. Je transmets votre demande à notre équipe qui vous contactera dans les plus brefs délais. " .
                "En attendant, vous pouvez consulter notre catalogue de produits sur le site."
            );
        }

        return $botResponse;
    }
}