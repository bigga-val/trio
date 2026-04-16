<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Message;
use App\Repository\CategorieRepository;
use App\Repository\CommentaireRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BoutiqueController extends AbstractController
{
    /**
     * Liste des produits avec filtres, recherche et pagination.
     */
    #[Route('/boutique', name: 'app_boutique')]
    public function index(
        Request             $request,
        ProduitRepository   $produitRepo,
        CategorieRepository $categorieRepo
    ): Response {
        $page        = max(1, (int) $request->query->get('page', 1));
        $parPage     = 12;
        $categorieId = $request->query->get('categorie') ? (int) $request->query->get('categorie') : null;
        $type        = $request->query->get('type');
        $recherche   = $request->query->get('q');

        $produits   = $produitRepo->findFiltered($categorieId, $type, $recherche, $page, $parPage);
        $total      = $produitRepo->countFiltered($categorieId, $type, $recherche);
        $totalPages = (int) ceil($total / $parPage);

        // Affiche uniquement les catégories du type actif (ou toutes si aucun type)
        $categories = $type
            ? $categorieRepo->findBy(['type' => $type], ['nom' => 'ASC'])
            : $categorieRepo->findAll();

        return $this->render('boutique/index.html.twig', [
            'produits'      => $produits,
            'categories'    => $categories,
            'page'          => $page,
            'total_pages'   => $totalPages,
            'total'         => $total,
            'categorie_id'  => $categorieId,
            'type'          => $type,
            'recherche'     => $recherche,
        ]);
    }

    /**
     * Page de détail d'un produit avec galerie, commentaires et formulaire message.
     */
    #[Route('/produit/{id}', name: 'app_produit_detail', requirements: ['id' => '\d+'])]
    public function detail(
        int                     $id,
        Request                 $request,
        ProduitRepository       $produitRepo,
        CommentaireRepository   $commentaireRepo,
        EntityManagerInterface  $em
    ): Response {
        $produit = $produitRepo->find($id);

        if (!$produit) {
            throw $this->createNotFoundException('Produit introuvable.');
        }

        $commentaires = $commentaireRepo->findVisiblesByProduit($id);

        // Traitement formulaire commentaire (POST)
        if ($request->isMethod('POST') && $request->request->get('_form') === 'commentaire') {
            $contenu = trim($request->request->get('contenu', ''));
            $nom     = trim($request->request->get('nom', ''));

            if ($contenu !== '') {
                $commentaire = new Commentaire();
                $commentaire->setContenu($contenu);
                $commentaire->setProduit($produit);

                if ($this->getUser()) {
                    $commentaire->setUser($this->getUser());
                } else {
                    $commentaire->setNomVisiteur($nom ?: 'Anonyme');
                }

                $em->persist($commentaire);
                $em->flush();

                $this->addFlash('success', 'Votre commentaire a été soumis et sera affiché après modération.');
                return $this->redirectToRoute('app_produit_detail', ['id' => $id]);
            }
        }

        // Traitement formulaire message (POST) — nécessite connexion
        if ($request->isMethod('POST') && $request->request->get('_form') === 'message') {
            if (!$this->getUser()) {
                $this->addFlash('info', 'Connectez-vous pour envoyer un message.');
                return $this->redirectToRoute('app_connexion');
            }

            $contenu = trim($request->request->get('contenu', ''));
            if ($contenu !== '') {
                $message = new Message();
                $message->setContenu($contenu);
                $message->setProduit($produit);
                $message->setExpediteur($this->getUser());

                $em->persist($message);
                $em->flush();

                $this->addFlash('success', 'Votre message a été envoyé. L\'équipe vous répondra bientôt.');
                return $this->redirectToRoute('app_produit_detail', ['id' => $id]);
            }
        }

        return $this->render('boutique/detail.html.twig', [
            'produit'      => $produit,
            'commentaires' => $commentaires,
        ]);
    }
}
