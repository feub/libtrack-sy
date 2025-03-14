<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Form\ArtistType;
use App\Repository\ArtistRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/artist', name: 'artist.')]
#[IsGranted('ROLE_ADMIN', message: 'You need admin privileges to view this page.')]
final class ArtistController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ArtistRepository $artistRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $artists = $artistRepository->paginatedArtists($page, $limit);
        $maxpage = ceil($artists->count() / 2);

        return $this->render('artist/index.html.twig', [
            'artists' => $artists,
            'maxPage' => $maxpage,
            'page' => $page
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    public function edit(Artist $artist, Request $request, ArtistRepository $artistRepository)
    {
        $form = $this->createForm(ArtistType::class, $artist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //
        }

        return $this->render('artist/edit.html.twig', [
            'artist' => $artist,
            'form' => $form
        ]);
    }
}
