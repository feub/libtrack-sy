<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Form\ScanType;
use App\Entity\Release;
use App\Service\MusicBrainzService;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use App\Service\CoverArtArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/release', name: 'release.')]
#[IsGranted('ROLE_ADMIN', message: 'You need admin privileges to view this page.')]
final class ReleaseController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private SluggerInterface $slugger
    ) {}

    #[Route('/', name: 'index')]
    public function index(ReleaseRepository $releaseRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
        $releases = $releaseRepository->paginatedReleases($page, $limit);
        $maxpage = ceil($releases->count() / 2);

        return $this->render('release/index.html.twig', [
            'releases' => $releases,
            'maxPage' => $maxpage,
            'page' => $page
        ]);
    }

    #[Route('/scan', name: 'scan', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    public function scan(
        Request $request,
        MusicBrainzService $musicBrainzService,
        CoverArtArchiveService $coverService
    ): Response {
        $barcodeValue = [];
        $releases = null;

        $form = $this->createForm(ScanType::class, null);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $barcodeValue = $form->get('barcode')->getData();

            try {
                $releaseData = $musicBrainzService->getReleaseByBarcode($barcodeValue);
                $releases = $releaseData["releases"];
            } catch (\Exception $e) {
                return $this->json([
                    'error' => $e->getMessage()
                ], 500);
            }

            // Attach cover art
            foreach ($releases as $key => $release) {
                $release['cover'] = $coverService->getCoverArtByMbid($release['id']);
                $releases[$key] = $release;
            }

            // Store the data in the session
            $session = $request->getSession();
            $session->set('barcode', $barcodeValue);
            $session->set('releases', $releases);

            // Redirect to the same route
            return $this->redirectToRoute('release.scan');
        }

        // Get any data from the session
        $session = $request->getSession();
        $barcodeValue = $session->get('barcode', '');
        $releases = $session->get('releases', null);

        // Clear the session data after retrieving it
        if ($request->getMethod() === 'GET' && !$form->isSubmitted()) {
            $session->remove('barcode');
            $session->remove('releases');
        }

        return $this->render('release/scan.html.twig', [
            'form' => $form,
            'barcode' => $barcodeValue,
            'releases' => $releases
        ]);
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    public function scanAdd(
        Request $request,
        EntityManagerInterface $em,
        ReleaseRepository $releaseRepository,
        ArtistRepository $artistRepository,
        MusicBrainzService $musicBrainzService
    ): Response {
        // Get the release ID and barcode from the form submission
        $releaseId = $request->request->get('release_id');
        $barcode = $request->request->get('barcode');

        if (!$releaseId) {
            $this->addFlash('error', 'No release ID provided');
            return $this->redirectToRoute('scan');
        }

        // Fetch the complete release data
        try {
            $releaseData = $musicBrainzService->getReleaseWithCoverArt($releaseId);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }

        // Check if the release does NOT already exist
        $checkRelease = $releaseRepository->findOneBy(['barcode' => $barcode]);

        if ($checkRelease) {
            $this->addFlash('warning', 'Barcode "' . $barcode . '" already in the database.');
            return $this->redirectToRoute('release.scan');
        }

        $release = new Release();
        $release->setTitle($releaseData['title']);
        $release->setBarcode($barcode);

        // dd($releaseData);

        if ($releaseData['cover']) {
            $release->setCover($releaseData['cover']);
        }

        // Release date (extract year if available)
        if (isset($releaseData['date']) && strlen($releaseData['date']) >= 4) {
            $yearString = substr($releaseData['date'], 0, 4);
            $year = (int)$yearString;
            $release->setReleaseDate($year);
        }

        // Slug
        $slug = $this->slugger->slug(strtolower($releaseData['title'] . '-' . $barcode . '-' . $releaseId));
        $release->setSlug($slug);

        // Timestamps
        $now = new \DateTimeImmutable();
        $release->setCreatedAt($now);
        $release->setUpdatedAt($now);

        // Artist
        if (isset($releaseData['artist-credit'])) {
            foreach ($releaseData['artist-credit'] as $artistCredit) {
                if (isset($artistCredit['artist'])) {
                    $artistData = $artistCredit['artist'];

                    // Check if artist already exists
                    $artist = $artistRepository->findOneBy(['name' => $artistData['name']]);

                    if (!$artist) {
                        $artist = new Artist();
                        $artist->setName($artistData['name']);
                        $artistSlug = $this->slugger->slug(strtolower($artistData['name']));
                        $artist->setSlug($artistSlug);
                        $artist->setCreatedAt($now);
                        $artist->setUpdatedAt($now);

                        $em->persist($artist);
                    }

                    $release->addArtist($artist);
                }
            }
        }

        $em->persist($release);
        $em->flush();

        $this->addFlash('success', 'Release "' . $release->getTitle() . '" added successfully');
        return $this->redirectToRoute('release.scan');
    }

    #[Route('/{barcode}/fetch-cover', name: 'fetch-cover', methods: ['GET', 'POST'], requirements: ['barcode' => Requirement::DIGITS])]
    public function fetchCover(
        int $barcode,
        Request $request,
        MusicBrainzService $musicBrainzService,
        CoverArtArchiveService $coverService
    ) {
        try {
            $releaseData = $musicBrainzService->getReleaseByBarcode($barcode);
            $releases = $releaseData["releases"];
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }

        // Attach cover art
        foreach ($releases as $key => $release) {
            $release['cover'] = $coverService->getCoverArtByMbid($release['id']);
            $releases[$key] = $release;
        }

        return $this->render('release/fetch-cover.html.twig', [
            'releases' => $releases,
            'barcode' => $barcode,
            'page' => $request->query->get('page', 1)
        ]);
    }

    #[Route('/update-cover', name: 'update.cover', methods: ['POST'])]
    public function updateCover(
        Request $request,
        EntityManagerInterface $em,
        ReleaseRepository $releaseRepository,
        MusicBrainzService $musicBrainzService
    ): Response {
        // Get the release ID and barcode from the form submission
        $page = $request->request->get('page');
        $releaseId = $request->request->get('release_id');
        $barcode = $request->request->get('barcode');

        if (!$releaseId) {
            $this->addFlash('error', 'No release ID provided');
            return $this->redirectToRoute('scan');
        }

        // Fetch the complete release data
        try {
            $releaseData = $musicBrainzService->getReleaseWithCoverArt($releaseId);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }

        // Get the release
        $release = $releaseRepository->findOneBy(['barcode' => $barcode]);

        if (!$release) {
            $this->addFlash('warning', 'Release with barcode "' . $barcode . '" does not exist in the database.');
            return $this->redirectToRoute('release.index', ['page' => $page]);
        }

        if ($releaseData['cover']) {
            $release->setCover($releaseData['cover']);
        }

        //$em->persist($release);
        $em->flush();

        $this->addFlash('success', 'Cover art successfully added for release "' . $release->getTitle() . '".');
        return $this->redirectToRoute('release.index', ['page' => $page]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    public function edit()
    {
        //
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Release $release, EntityManagerInterface $em)
    {
        $em->remove($release);
        $em->flush();
        $this->addFlash('success', 'The release has been successfully deleted.');
        return $this->redirectToRoute('release.index');
    }
}
