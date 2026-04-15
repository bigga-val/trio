<?php

namespace App\Command;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\ProduitImage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-fixtures',
    description: 'Charge les données de démonstration (catégories + produits + images).',
)]
class LoadFixturesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('reset', null, InputOption::VALUE_NONE, 'Supprimer les produits existants avant de charger');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Chargement des fixtures New Day Groupe SARL');

        // ── Optionnel : reset des produits ─────────────────────
        if ($input->getOption('reset')) {
            $io->warning('Suppression des produits existants...');
            foreach ($this->em->getRepository(Produit::class)->findAll() as $p) {
                $this->em->remove($p);
            }
            $this->em->flush();
        }

        // ── Catégories ──────────────────────────────────────────
        $cats = $this->getOrCreateCategories($io);

        // ── Produits ────────────────────────────────────────────
        $produits = $this->getProduitData();
        $created  = 0;

        foreach ($produits as $data) {
            // Éviter les doublons
            $existe = $this->em->getRepository(Produit::class)->findOneBy(['titre' => $data['titre']]);
            if ($existe) {
                $io->writeln("  <comment>Existe déjà :</comment> {$data['titre']}");
                continue;
            }

            $cat = $cats[$data['categorie']] ?? null;
            if (!$cat) {
                $io->error("Catégorie introuvable : {$data['categorie']}");
                continue;
            }

            $produit = new Produit();
            $produit->setTitre($data['titre'])
                    ->setDescription($data['description'])
                    ->setPrix($data['prix'] ?? null)
                    ->setVille($data['ville'] ?? 'Kolwezi')
                    ->setIsDisponible($data['disponible'] ?? true)
                    ->setCategorie($cat);

            // Images
            foreach ($data['images'] as $i => $fichier) {
                $img = new ProduitImage();
                $img->setCheminFichier($fichier)
                    ->setOrdre($i)
                    ->setIsPrincipale($i === 0);
                $produit->addImage($img);
                $this->em->persist($img);
            }

            $this->em->persist($produit);
            $created++;
            $io->writeln("  <info>Créé :</info> {$data['titre']}");
        }

        $this->em->flush();
        $io->success("{$created} produit(s) créé(s) avec succès.");

        return Command::SUCCESS;
    }

    /** Récupère ou crée les catégories nécessaires. */
    private function getOrCreateCategories(SymfonyStyle $io): array
    {
        $definitions = [
            'vehicules-neufs'     => ['nom' => 'Véhicules neufs',      'type' => 'vehicule',       'slug' => 'vehicules-neufs'],
            'vehicules-occasion'  => ['nom' => "Véhicules d'occasion", 'type' => 'vehicule',       'slug' => 'vehicules-occasion'],
            'pieces-de-rechange'  => ['nom' => 'Pièces de rechange',   'type' => 'piece_rechange', 'slug' => 'pieces-de-rechange'],
            'moteurs'             => ['nom' => 'Moteurs',              'type' => 'piece_rechange', 'slug' => 'moteurs'],
        ];

        $repo = $this->em->getRepository(Categorie::class);
        $map  = [];

        foreach ($definitions as $key => $def) {
            $cat = $repo->findOneBy(['slug' => $def['slug']]);
            if (!$cat) {
                $cat = new Categorie();
                $cat->setNom($def['nom'])->setSlug($def['slug'])->setType($def['type']);
                $this->em->persist($cat);
                $io->writeln("  <info>Catégorie créée :</info> {$def['nom']}");
            }
            $map[$key] = $cat;
        }

        $this->em->flush();
        return $map;
    }

    /** Définition de tous les produits avec leurs images réelles. */
    private function getProduitData(): array
    {
        return [

            // ── VÉHICULES NEUFS ──────────────────────────────────

            [
                'titre'       => 'Toyota Alphard 2024 — Minivan de Luxe',
                'categorie'   => 'vehicules-neufs',
                'description' => "Le Toyota Alphard 2024 redéfinit le luxe en matière de minivan premium.\n\n"
                    . "Dotée d'un moteur hybride puissant et silencieux, cette icône du confort offre 7 places dans une cabine somptueuse. "
                    . "Sièges capitaine arrière inclinables, toit panoramique, système de sonorisation premium et finitions bois véritable.\n\n"
                    . "• Motorisation : 2.5L Hybride — 248 ch\n"
                    . "• Transmission : E-CVT automatique\n"
                    . "• Traction : 4WD\n"
                    . "• Couleur : Blanc nacré\n"
                    . "• Kilométrage : 0 km (neuf)\n"
                    . "• Importé directement du Japon via BE FORWARD",
                'prix'        => '42000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['toyota-alphard.jpg'],
            ],

            [
                'titre'       => 'Ford Raptor F-150 Off-Road 2023',
                'categorie'   => 'vehicules-neufs',
                'description' => "Le Ford Raptor F-150 est LE pick-up de référence pour les terrains difficiles de la RDC.\n\n"
                    . "Préparé pour l'off-road extrême, ce exemplaire dispose d'une suspension Fox Racing rehaussée, "
                    . "de pneus tout-terrain BF Goodrich et d'un look agressif qui impose le respect.\n\n"
                    . "• Motorisation : 3.5L EcoBoost V6 — 450 ch\n"
                    . "• Transmission : 10 vitesses automatique\n"
                    . "• Traction : 4x4 permanent\n"
                    . "• Couleur : Orange Fury\n"
                    . "• Charge utile : 1 200 kg\n"
                    . "• Idéal mines, chantiers et expéditions",
                'prix'        => '68000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['ford-raptor.jpg'],
            ],

            [
                'titre'       => 'Nissan Juke 2022 — Crossover Dynamique',
                'categorie'   => 'vehicules-neufs',
                'description' => "Le Nissan Juke 2022 nouvelle génération s'impose comme le crossover urbain le plus stylé du marché.\n\n"
                    . "Son design audacieux et sa motorisation turbochargée efficace en font le compagnon idéal pour la ville et la route.\n\n"
                    . "• Motorisation : 1.0L DIG-T Turbo — 117 ch\n"
                    . "• Transmission : 7 vitesses DCT automatique\n"
                    . "• Couleur : Bleu Vivid\n"
                    . "• Kilométrage : 8 000 km\n"
                    . "• Année : 2022\n"
                    . "• Équipements : Apple CarPlay, caméra 360°, aide au stationnement",
                'prix'        => '24500',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['nissan-juke.jpg'],
            ],

            [
                'titre'       => 'Hyundai Grandeur 2024 — Berline Executive',
                'categorie'   => 'vehicules-neufs',
                'description' => "La Hyundai Grandeur 2024 incarne l'élégance coréenne portée à son summum.\n\n"
                    . "Sa calandre paramétrique illuminée, ses lignes futuristes et son habitacle grand luxe classent cette berline "
                    . "dans la catégorie des voitures de représentation.\n\n"
                    . "• Motorisation : 2.5L V6 — 290 ch\n"
                    . "• Transmission : 8 vitesses automatique\n"
                    . "• Couleur : Gris Platine\n"
                    . "• Kilométrage : 5 000 km\n"
                    . "• Toit ouvrant panoramique\n"
                    . "• Système BOSE 17 haut-parleurs",
                'prix'        => '38000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['hyundai-grandeur.jpg'],
            ],

            [
                'titre'       => 'BMW Z4 Roadster 2023 — Cabriolet Sport',
                'categorie'   => 'vehicules-neufs',
                'description' => "Le BMW Z4 Roadster 2023 est une invitation à conduire, la capote ouverte et les sensations décuplées.\n\n"
                    . "Issu d'une collaboration BMW–Toyota, ce roadster mêle technologie bavaroise et rigueur japonaise pour un résultat "
                    . "exceptionnel sur route comme sur circuit.\n\n"
                    . "• Motorisation : 3.0L TwinPower Turbo I6 — 340 ch\n"
                    . "• 0-100 km/h : 4.5 secondes\n"
                    . "• Capote électrique en 10 secondes\n"
                    . "• Couleur : Or Frozen Galvanic\n"
                    . "• Jantes 19 pouces\n"
                    . "• Finition M Sport",
                'prix'        => '55000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['bmw-z4.jpg'],
            ],

            [
                'titre'       => 'Rolls-Royce Phantom — Prestige Absolu',
                'categorie'   => 'vehicules-neufs',
                'description' => "La Rolls-Royce Phantom représente le summum de l'automobile mondiale. Rien n'est trop beau pour celui qui la choisit.\n\n"
                    . "Chaque exemplaire est fabriqué à la main dans les ateliers de Goodwood, Angleterre. "
                    . "Ce modèle Black Badge est l'expression ultime du pouvoir et de la discrétion.\n\n"
                    . "• Motorisation : 6.75L V12 biturbo — 571 ch\n"
                    . "• 0-100 km/h : 5.1 secondes\n"
                    . "• Transmission : 8 vitesses ZF\n"
                    . "• Couleur : Noir Diamond\n"
                    . "• Toit étoilé — 1 344 fibres optiques\n"
                    . "• Disponible sur commande — délai 4-6 semaines",
                'prix'        => '380000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['rolls-royce-phantom.jpg'],
            ],

            // ── VÉHICULES D'OCCASION ─────────────────────────────

            [
                'titre'       => "Toyota IST Urban Cruiser — SUV Compact",
                'categorie'   => 'vehicules-occasion',
                'description' => "Le Toyota IST Urban Cruiser est un SUV compact robuste et économique, parfait pour les routes de Kolwezi.\n\n"
                    . "Reconnu pour sa fiabilité légendaire Toyota et son faible coût d'entretien, il reste l'un des meilleurs rapports qualité-prix du marché.\n\n"
                    . "• Motorisation : 1.5L VVTi — 108 ch\n"
                    . "• Transmission : 4 vitesses automatique\n"
                    . "• Couleur : Gris Métal\n"
                    . "• Kilométrage : 87 000 km\n"
                    . "• Année : 2018\n"
                    . "• Contrôle technique valide",
                'prix'        => '12500',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['toyota-ist-1.jpg', 'toyota-ist-2.jpg'],
            ],

            [
                'titre'       => 'BMW 640i Gran Coupé — Berline de Sport',
                'categorie'   => 'vehicules-occasion',
                'description' => "La BMW 640i Gran Coupé allie sport et grand tourisme dans un design irrésistible.\n\n"
                    . "Ce coupé 4 portes au look de sportive offre des performances impressionnantes couplées au confort d'une grande berline. "
                    . "Entretien BMW complet, aucun accident.\n\n"
                    . "• Motorisation : 3.0L TwinPower Turbo I6 — 320 ch\n"
                    . "• Transmission : 8 vitesses automatique\n"
                    . "• Couleur : Noir Saphir\n"
                    . "• Kilométrage : 112 000 km\n"
                    . "• Année : 2017\n"
                    . "• Pack M Sport, toit panoramique, sièges chauffants",
                'prix'        => '28000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['bmw-640.jpg', 'bmw-640-tableau.jpg'],
            ],

            [
                'titre'       => 'Nissan Datsun 280ZX — Collection',
                'categorie'   => 'vehicules-occasion',
                'description' => "Un véritable bijou de collection : la Nissan Datsun 280ZX, icône des années 80 en parfait état.\n\n"
                    . "Entièrement restaurée et préservée, cette sportive japonaise vintage est idéale pour les amateurs de voitures de collection "
                    . "ou simplement pour rouler avec classe et originalité.\n\n"
                    . "• Motorisation : 2.8L L6 — 145 ch\n"
                    . "• Transmission : 5 vitesses manuelle\n"
                    . "• Couleur : Bordeaux classique\n"
                    . "• Kilométrage : 148 000 km (restauré)\n"
                    . "• Année : 1982\n"
                    . "• Carrosserie et intérieur en excellent état",
                'prix'        => '18000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['nissan-datsun.jpg'],
            ],

            [
                'titre'       => 'Nissan Note 2019 — Citadine Économique',
                'categorie'   => 'vehicules-occasion',
                'description' => "La Nissan Note 2019 est la citadine parfaite pour les déplacements urbains quotidiens à Kolwezi.\n\n"
                    . "Légère, maniable et très économique en carburant, elle est idéale pour les jeunes conducteurs ou pour un deuxième véhicule de famille.\n\n"
                    . "• Motorisation : 1.2L e-POWER — 109 ch\n"
                    . "• Transmission : Automatique\n"
                    . "• Couleur : Orange Pumpkin\n"
                    . "• Kilométrage : 54 000 km\n"
                    . "• Année : 2019\n"
                    . "• Climatisation, écran tactile, caméra de recul",
                'prix'        => '9800',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['nissan-note.jpg'],
            ],

            [
                'titre'       => 'Porsche Cayenne — SUV Prestige',
                'categorie'   => 'vehicules-occasion',
                'description' => "Le Porsche Cayenne combine performances de supercar et praticité de SUV dans un seul véhicule exceptionnel.\n\n"
                    . "Cet exemplaire en excellent état est le choix parfait pour ceux qui refusent de choisir entre puissance et confort.\n\n"
                    . "• Motorisation : 3.6L V6 — 290 ch\n"
                    . "• Transmission : 8 vitesses Tiptronic\n"
                    . "• Traction : Xdrive 4x4\n"
                    . "• Couleur : Gris Arctique\n"
                    . "• Kilométrage : 98 000 km\n"
                    . "• Année : 2016\n"
                    . "• Toit panoramique, cuir beige, BOSE audio",
                'prix'        => '35000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['porsche-cayenne.jpg'],
            ],

            [
                'titre'       => 'Mercedes-Benz Classe G — Gelandewagen 4x4',
                'categorie'   => 'vehicules-occasion',
                'description' => "Le Mercedes Classe G (Gelandewagen) est une légende vivante de l'automobile tout-terrain.\n\n"
                    . "Produit depuis 1979, ce 4x4 indestructible est aussi à l'aise en ville que dans les terrains les plus hostiles. "
                    . "Un must-have pour l'aventure et le prestige.\n\n"
                    . "• Motorisation : 3.0L Diesel — 211 ch\n"
                    . "• Transmission : 5 vitesses automatique\n"
                    . "• 3 blocages de différentiel\n"
                    . "• Couleur : Vert militaire\n"
                    . "• Kilométrage : 178 000 km\n"
                    . "• Année : 2005\n"
                    . "• Carrosserie solide, moteur refait à neuf",
                'prix'        => '22000',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['mercedes-gclass.jpg'],
            ],

            // ── PIÈCES DE RECHANGE ───────────────────────────────

            [
                'titre'       => 'Filtre à huile universel — Haute Performance',
                'categorie'   => 'pieces-de-rechange',
                'description' => "Filtre à huile de haute performance compatible avec la majorité des véhicules japonais et européens.\n\n"
                    . "Fabriqué selon les normes OEM, ce filtre garantit une filtration optimale des impuretés pour prolonger la vie de votre moteur.\n\n"
                    . "• Compatibilité : Toyota, Nissan, Honda, Mitsubishi, Ford, BMW\n"
                    . "• Filetage : M20x1.5\n"
                    . "• Pression d'ouverture : 0.8–1.2 bar\n"
                    . "• Norme : ISO/TS 16949\n"
                    . "• Vendu à l'unité — stock disponible\n"
                    . "• Livraison possible sur Kolwezi et environs",
                'prix'        => '15',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['filtre-huile.png'],
            ],

            [
                'titre'       => 'Moteur hydraulique Bosch Rexroth — Industriel',
                'categorie'   => 'moteurs',
                'description' => "Moteur hydraulique à pistons axiaux Bosch Rexroth, conçu pour les applications industrielles lourdes.\n\n"
                    . "Utilisé dans les engins de mines, chantiers et équipements lourds présents à Kolwezi. "
                    . "Pièce importée directement d'Europe, garantie 12 mois.\n\n"
                    . "• Marque : Bosch Rexroth\n"
                    . "• Type : Pistons axiaux — déplacement variable\n"
                    . "• Pression max : 450 bar\n"
                    . "• Cylindrée : 55 cc/tr\n"
                    . "• Applications : chargeuses, excavatrices, presses\n"
                    . "• État : Neuf en boîte d'origine",
                'prix'        => '4200',
                'ville'       => 'Kolwezi',
                'disponible'  => true,
                'images'      => ['moteur-hydraulique.jpg'],
            ],

        ];
    }
}
