<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Entity\ProduitImage;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produit')]
final class ProduitController extends AbstractController
{
    #[Route(name: 'app_produit_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads';

            // Handle main image (ImageProduit) - get data from the unmapped form field
            $imageFile = $form->get('ImageProduit')->getData();
            if ($imageFile) {
                $fileName = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($uploadDir, $fileName);
                $produit->setImageProduit('/uploads/'.$fileName);
            }

            // Handle additional images (non-mapped 'images' field)
            $imageFiles = $form->get('images')->getData();
            if ($imageFiles) {
                foreach ($imageFiles as $file) {
                    if (!$file) continue;
                    $fname = uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                    $file->move($uploadDir, $fname);

                    $img = new ProduitImage();
                    $img->setFilename('/uploads/'.$fname);
                    $produit->addImage($img);
                    $entityManager->persist($img);
                }
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Produit créé avec succès.');

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads';

            // Handle main image update (if a new file was uploaded) - read from form unmapped field
            $imageFile = $form->get('ImageProduit')->getData();
            if ($imageFile && method_exists($imageFile, 'guessExtension')) {
                $fileName = uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($uploadDir, $fileName);
                $produit->setImageProduit('/uploads/'.$fileName);
            }

            // Handle additional uploaded images
            if ($form->has('images')) {
                $imageFiles = $form->get('images')->getData();
                if ($imageFiles) {
                    foreach ($imageFiles as $file) {
                        if (!$file) continue;
                        $fname = uniqid().'_'.preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                        $file->move($uploadDir, $fname);

                        $img = new ProduitImage();
                        $img->setFilename('/uploads/'.$fname);
                        $produit->addImage($img);
                        $entityManager->persist($img);
                    }
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Produit mis à jour.');

            return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{produitId}/image/{id}/delete', name: 'app_produit_image_delete', methods: ['POST'])]
    public function deleteImage(Request $request, int $produitId, ProduitImage $image, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-image'.$image->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_produit_edit', ['id' => $produitId]);
        }

        // ensure image belongs to produit
        if (!$image->getProduit() || $image->getProduit()->getId() !== $produitId) {
            $this->addFlash('error', 'Image non trouvée pour ce produit.');
            return $this->redirectToRoute('app_produit_edit', ['id' => $produitId]);
        }

        // delete file from disk
        $file = $this->getParameter('kernel.project_dir').'/public'. $image->getFilename();
        if (file_exists($file)) {
            @unlink($file);
        }

        $entityManager->remove($image);
        $entityManager->flush();

        $this->addFlash('success', 'Image supprimée.');

        return $this->redirectToRoute('app_produit_edit', ['id' => $produitId]);
    }

    #[Route('/{produitId}/images/delete-selected', name: 'app_produit_images_delete_selected', methods: ['POST'])]
    public function deleteSelectedImages(Request $request, int $produitId, EntityManagerInterface $entityManager): Response
    {
        $ids = $request->request->get('selected_images', []);
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-selected-images'.$produitId, $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_produit_edit', ['id' => $produitId]);
        }

        if (!is_array($ids) || empty($ids)) {
            $this->addFlash('warning', 'Aucune image sélectionnée.');
            return $this->redirectToRoute('app_produit_edit', ['id' => $produitId]);
        }

        $repo = $entityManager->getRepository(ProduitImage::class);
        foreach ($ids as $id) {
            $image = $repo->find((int)$id);
            if (!$image) continue;
            if (!$image->getProduit() || $image->getProduit()->getId() !== $produitId) continue;

            // delete file
            $file = $this->getParameter('kernel.project_dir').'/public'.$image->getFilename();
            if (file_exists($file)) @unlink($file);

            $entityManager->remove($image);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Images supprimées.');

        return $this->redirectToRoute('app_produit_edit', ['id' => $produitId]);
    }

    #[Route('/{produitId}/images/reorder', name: 'app_produit_images_reorder', methods: ['POST'])]
    public function reorderImages(Request $request, int $produitId, EntityManagerInterface $entityManager): Response
    {
        $order = $request->request->get('order', []);
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reorder-images'.$produitId, $token)) {
            return $this->json(['success' => false, 'message' => 'CSRF invalid'], 400);
        }

        if (!is_array($order)) {
            return $this->json(['success' => false, 'message' => 'Invalid payload'], 400);
        }

        $repo = $entityManager->getRepository(ProduitImage::class);
        $pos = 0;
        foreach ($order as $id) {
            $image = $repo->find((int)$id);
            if (!$image) continue;
            if (!$image->getProduit() || $image->getProduit()->getId() !== $produitId) continue;
            $image->setPosition($pos);
            $pos++;
        }

        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
