<?php

namespace App\Command;

use App\Entity\Client;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:hash-passwords',
    description: 'Hash all client passwords that are not already hashed',
)]
class HashPasswordsCommand extends Command
{
    public function __construct(
        private ClientRepository $clientRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clients = $this->clientRepository->findAll();
        $hashed = 0;
        $skipped = 0;

        foreach ($clients as $client) {
            $password = $client->getPassword();
            
            // Vérifier si le mot de passe semble déjà hashé (commence par $2y$ ou $argon2)
            if ($password && !str_starts_with($password, '$2y$') && !str_starts_with($password, '$argon2')) {
                // Hacher le mot de passe
                $hashedPassword = $this->passwordHasher->hashPassword($client, $password);
                $client->setPassword($hashedPassword);
                $this->entityManager->persist($client);
                $hashed++;
                $output->writeln("✓ Email: {$client->getAdresseEmail()} - Mot de passe hashé");
            } else {
                $skipped++;
                $output->writeln("⊘ Email: {$client->getAdresseEmail()} - Déjà hashé");
            }
        }

        $this->entityManager->flush();

        $output->writeln("\n<info>Résumé:</info>");
        $output->writeln("<info>Mots de passe hashés: $hashed</info>");
        $output->writeln("<info>Mots de passe ignorés: $skipped</info>");

        return Command::SUCCESS;
    }
}
