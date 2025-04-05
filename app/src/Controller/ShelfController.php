<?php

namespace App\Controller;

use App\Entity\Shelf;
use App\Form\ShelfType;
use App\Repository\ShelfRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/shelf', name: 'shelf.')]
final class ShelfController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ShelfRepository $shelfRepository): Response
    {
        $shelves = $shelfRepository->getShelves();

        return $this->render('shelf/index.html.twig', [
            'shelves' => $shelves,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Shelf $shelf, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ShelfType::class, $shelf);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The shelf has been successfully saved.');
            return $this->redirectToRoute('shelf.index');
        }
        return $this->render('shelf/edit.html.twig', [
            'shelf' => $shelf,
            'form' => $form
        ]);
    }
}
