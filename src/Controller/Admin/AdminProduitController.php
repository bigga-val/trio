<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Entity\ProduitImage;
use App\Repository\CategorieRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/produits')]
class AdminProduitController extends AbstractController
{
    public function __construct(
        private readonly string $uploadsDir = ''
    ) {}

    /**
     * Liste de tous les produits (admin).
     */
    #[Route('', name: 'app_admin_produits')]
    public function index(ProduitRepository $repo): Response
    {
        return $this->render('admin/produits/index.html.twig', [
            'produits' => $repo->findAll(),
        ]);
    }

    /**
     * Formulaire de création d'un nouveau produit avec upload multi-images.
     */
    #[Route('/nouveau', name: 'app_admin_produit_nouveau')]
    public function nouveau(
        Request                $request,
        EntityManagerInterface $em,
        CategorieRepository    $catRepo,
        SluggerInterface       $slugger
    ): Response {
        $categories = $catRepo->findAll();
        $errors     = [];

        if ($request->isMethod('POST')) {
            [$produit, $errors] = $this->traitementFormulaire($request, $em, $catRepo, $slugger, null);
            if (empty($errors)) {
                $em->persist($produit);
                $em->flush();
                $this->addFlash('success', 'Produit "' . $produit->getTitre() . '" créé avec succès.');
                return $this->redirectToRoute('app_admin_produits');
            }
        }

        return $this->render('admin/produits/form.html.twig', [
            'categories' => $categories,
            'errors'     => $errors,
            'produit'    => null,
        ]);
    }

    /**
     * Formulaire de modification d'un produit existant.
     */
    #[Route('/{id}/modifier', name: 'app_admin_produit_modifier', requirements: ['id' => '\d+'])]
    public function modifier(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        CategorieRepository    $catRepo,
        ProduitRepository      $produitRepo,
        SluggerInterface       $slugger
    ): Response {
        $produit = $produitRepo->find($id);
        if (!$produit) throw $this->createNotFoundException();

        $categories = $catRepo->findAll();
        $errors     = [];

        if ($request->isMethod('POST')) {
            [$produit, $errors] = $this->traitementFormulaire($request, $em, $catRepo, $slugger, $produit);
            if (empty($errors)) {
                $em->flush();
                $this->addFlash('success', 'Produit mis à jour.');
                return $this->redirectToRoute('app_admin_produits');
            }
        }

        return $this->render('admin/produits/form.html.twig', [
            'categories' => $categories,
            'errors'     => $errors,
            'produit'    => $produit,
        ]);
    }

    /**
     * Suppression d'un produit (et de ses images physiques).
     */
    #[Route('/{id}/supprimer', name: 'app_admin_produit_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supprimer(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em,
        ProduitRepository      $produitRepo
    ): Response {
        $produit = $produitRepo->find($id);
        if (!$produit) throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('delete_produit_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_produits');
        }

        // Supprimer les fichiers images physiques
        foreach ($produit->getImages() as $img) {
            $path = $this->getParameter('kernel.project_dir') . '/public/uploads/produits/' . $img->getCheminFichier();
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $em->remove($produit);
        $em->flush();

        $this->addFlash('success', 'Produit supprimé.');
        return $this->redirectToRoute('app_admin_produits');
    }

    /**
     * Suppression d'une image individuelle d'un produit.
     */
    #[Route('/image/{id}/supprimer', name: 'app_admin_image_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supprimerImage(
        int                    $id,
        Request                $request,
        EntityManagerInterface $em
    ): Response {
        $image = $em->getRepository(ProduitImage::class)->find($id);
        if (!$image) throw $this->createNotFoundException();

        $produitId = $image->getProduit()->getId();

        if (!$this->isCsrfTokenValid('delete_image_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_produit_modifier', ['id' => $produitId]);
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/produits/' . $image->getCheminFichier();
        if (file_exists($path)) {
            unlink($path);
        }

        $em->remove($image);
        $em->flush();

        $this->addFlash('success', 'Image supprimée.');
        return $this->redirectToRoute('app_admin_produit_modifier', ['id' => $produitId]);
    }

    /**
     * Traite le formulaire produit (création ou modification).
     * Retourne [$produit, $errors].
     */
    private function traitementFormulaire(
        Request                $request,
        EntityManagerInterface $em,
        CategorieRepository    $catRepo,
        SluggerInterface       $slugger,
        ?Produit               $produit
    ): array {
        $errors  = [];
        $isNew   = ($produit === null);
        $produit = $produit ?? new Produit();

        $titre       = trim($request->request->get('titre', ''));
        $description = trim($request->request->get('description', ''));
        $prix        = $request->request->get('prix', '');
        $ville       = trim($request->request->get('ville', ''));
        $categorieId = (int) $request->request->get('categorie_id', 0);
        $disponible  = $request->request->get('is_disponible') === '1';

        if (empty($titre))       $errors['titre']      = 'Le titre est requis.';
        if (empty($description)) $errors['description'] = 'La description est requise.';
        if ($categorieId === 0)  $errors['categorie']   = 'La catégorie est requise.';

        $categorie = $catRepo->find($categorieId);
        if (!$categorie)         $errors['categorie']   = 'Catégorie introuvable.';

        if (!empty($errors)) {
            return [$produit, $errors];
        }

        $produit->setTitre($titre)
                ->setDescription($description)
                ->setPrix($prix !== '' ? $prix : null)
                ->setVille($ville ?: null)
                ->setIsDisponible($disponible)
                ->setCategorie($categorie);

        // Upload des nouvelles images
        $fichiers = $request->files->get('images', []);
        $ordre    = $produit->getImages()->count();

        foreach ($fichiers as $fichier) {
            if ($fichier === null) continue;

            $originalFilename = pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename     = $slugger->slug($originalFilename);
            $newFilename      = $safeFilename . '-' . uniqid() . '.' . $fichier->guessExtension();

            try {
                $fichier->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/produits',
                    $newFilename
                );

                $img = new ProduitImage();
                $img->setCheminFichier($newFilename)
                    ->setOrdre($ordre)
                    ->setIsPrincipale($ordre === 0 && $produit->getImages()->isEmpty());

                $produit->addImage($img);
                $em->persist($img);
                $ordre++;

            } catch (FileException $e) {
                $errors['images'] = 'Erreur lors de l\'upload : ' . $e->getMessage();
            }
        }

        // Définir image principale si demandé
        $imagePrincipaleId = (int) $request->request->get('image_principale', 0);
        if ($imagePrincipaleId > 0) {
            foreach ($produit->getImages() as $img) {
                $img->setIsPrincipale($img->getId() === $imagePrincipaleId);
            }
        }

        return [$produit, $errors];
    }
}
