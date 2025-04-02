<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Form\ArtistType;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
    public function edit(Artist $artist, Request $request, EntityManagerInterface $em)
    {
        $form = $this->createForm(ArtistType::class, $artist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The artist has been successfully saved.');
            return $this->redirectToRoute('artist.index');
        }

        return $this->render('artist/edit.html.twig', [
            'artist' => $artist,
            'form' => $form
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em)
    {
        $artist = new Artist();
        $form = $this->createForm(ArtistType::class, $artist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($artist);
            $em->flush();
            $this->addFlash('success', 'The artist has been successfully added.');
            return $this->redirectToRoute('artist.index');
        }

        return $this->render('artist/create.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/confirm-delete', name: 'delete', methods: ['GET', 'POST'])]
    public function confirmDelete(Artist $artist, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder()
            ->add('delete', SubmitType::class, [
                'label' => 'Delete',
                'attr' => [
                    'class' => 'text-white bg-red-500 hover:bg-red-500 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-3 py-1.5 text-center dark:bg-red-500 dark:hover:bg-red-700 dark:focus:ring-blue-800'
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->remove($artist);
                $em->flush();
                $this->addFlash('success', 'The artist has been successfully deleted.');
                return $this->redirectToRoute('artist.index');
            } catch (\RuntimeException $e) {
                $this->addFlash('warning', $e->getMessage());
                return $this->redirectToRoute('artist.index');
            }
        }

        return $this->render('artist/confirm_delete.html.twig', [
            'artist' => $artist,
            'form' => $form->createView(),
        ]);
    }
}
