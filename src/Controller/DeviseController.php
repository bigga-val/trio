<?php

namespace App\Controller;

use App\Entity\Devise;
use App\Form\DeviseType;
use App\Repository\DeviseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/devise')]
final class DeviseController extends AbstractController
{
    #[Route(name: 'app_devise_index', methods: ['GET'])]
    public function index(DeviseRepository $deviseRepository): Response
    {
        return $this->render('devise/index.html.twig', [
            'devises' => $deviseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_devise_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, DeviseRepository $deviseRepository): Response
    {
        $devise = new Devise();
        $form = $this->createForm(DeviseType::class, $devise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($devise);
            $entityManager->flush();

            return $this->redirectToRoute('app_devise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('devise/new.html.twig', [
            'devise' => $devise,
            'form' => $form,
            'devises' => $deviseRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_devise_show', methods: ['GET'])]
    public function show(Devise $devise): Response
    {
        return $this->render('devise/show.html.twig', [
            'devise' => $devise,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_devise_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Devise $devise, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DeviseType::class, $devise);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_devise_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('devise/edit.html.twig', [
            'devise' => $devise,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_devise_delete', methods: ['POST'])]
    public function delete(Request $request, Devise $devise, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$devise->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($devise);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_devise_index', [], Response::HTTP_SEE_OTHER);
    }
}
