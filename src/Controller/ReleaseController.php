<?php

namespace App\Controller;

use App\Repository\ReleaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReleaseController extends AbstractController
{
    #[Route('/release', name: 'release')]
    public function index(ReleaseRepository $releaseRepository): Response
    {
        $releases = $releaseRepository->findAll();

        return $this->render('release/index.html.twig', [
            'releases' => $releases,
        ]);
    }
}
