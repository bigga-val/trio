<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur New Day.',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Nom d\'utilisateur admin')
            ->addArgument('email',    InputArgument::REQUIRED, 'Email admin')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe')
            ->addArgument('nom',      InputArgument::OPTIONAL, 'Nom',    'Admin')
            ->addArgument('prenom',   InputArgument::OPTIONAL, 'Prénom', 'New Day');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getArgument('username');
        $email    = $input->getArgument('email');

        $existing = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existing) {
            $existing->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            $this->em->flush();
            $io->success("Compte existant promu ROLE_ADMIN : {$username}");
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setUsername($username)
             ->setEmail($email)
             ->setNom($input->getArgument('nom'))
             ->setPrenom($input->getArgument('prenom'))
             ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
             ->setPassword($this->hasher->hashPassword($user, $input->getArgument('password')));

        $this->em->persist($user);
        $this->em->flush();

        $io->success("Admin créé : {$email}");
        return Command::SUCCESS;
    }
}
