<?php

namespace App\Command;

use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-categories',
    description: 'Crée les catégories de base du catalogue New Day.',
)]
class CreateCategoriesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categories = [
            ['nom' => 'Véhicules',          'slug' => 'vehicules',          'type' => 'vehicule'],
            ['nom' => 'Véhicules d\'occasion','slug' => 'vehicules-occasion', 'type' => 'vehicule'],
            ['nom' => 'Pièces de rechange',  'slug' => 'pieces-de-rechange', 'type' => 'piece_rechange'],
            ['nom' => 'Moteurs',             'slug' => 'moteurs',            'type' => 'piece_rechange'],
            ['nom' => 'Immobilier',          'slug' => 'immobilier',         'type' => 'immobilier'],
        ];

        $repo    = $this->em->getRepository(Categorie::class);
        $created = 0;

        foreach ($categories as $data) {
            if ($repo->findOneBy(['slug' => $data['slug']])) {
                $io->writeln("Existe déjà : {$data['nom']}");
                continue;
            }

            $cat = new Categorie();
            $cat->setNom($data['nom'])
                ->setSlug($data['slug'])
                ->setType($data['type']);

            $this->em->persist($cat);
            $created++;
            $io->writeln("Créée : {$data['nom']}");
        }

        $this->em->flush();
        $io->success("{$created} catégorie(s) créée(s).");

        return Command::SUCCESS;
    }
}
