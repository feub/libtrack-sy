<?php

namespace App\Controller;

use App\Entity\Artist;
use App\Form\ScanType;
use App\Entity\Release;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/release', name: 'release.')]
final class ReleaseController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private SluggerInterface $slugger
    ) {}

    #[Route('/', name: 'index')]
    public function index(ReleaseRepository $releaseRepository): Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            $releases = $releaseRepository->findAll();

            return $this->render('release/index.html.twig', [
                'releases' => $releases,
            ]);
        } catch (AccessDeniedException $e) {
            return $this->render('errors/custom_access_denied.html.twig', [
                'error' => 'You need admin privileges to view this page.',
            ], new Response('', 403));
        }
    }

    #[Route('/scan', name: 'scan', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    public function scan(Request $request): Response
    {
        try {
            $barcodeValue = [];
            $releases = null;

            $form = $this->createForm(ScanType::class, null);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $barcodeValue = $form->get('barcode')->getData();

                $response = $this->client->request(
                    'GET',
                    'https://musicbrainz.org/ws/2/release/',
                    [
                        'query' => [
                            'query' => 'barcode:' . $barcodeValue,
                            'fmt' => 'json'
                        ],
                        'headers' => [
                            'User-Agent' => 'LibTrack/1.0 (f@feub.net)'
                        ]
                    ]
                );

                $statusCode = $response->getStatusCode();

                if ($statusCode !== 200) {
                    return $this->json([
                        'error' => 'Error fetching from MusicBrainz API',
                        'status' => $statusCode
                    ], $statusCode);
                }

                $content = $response->getContent();
                $content = $response->toArray();
                $releases = $content["releases"];

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
        } catch (AccessDeniedException $e) {
            return $this->render('errors/custom_access_denied.html.twig', [
                'error' => 'You need admin privileges to view this page.',
            ], new Response('', 403));
        }
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    public function scanAdd(Request $request, EntityManagerInterface $em, ReleaseRepository $releaseRepository, ArtistRepository $artistRepository): Response
    {
        try {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            // Get the release ID and barcode from the form submission
            $releaseId = $request->request->get('release_id');
            $barcode = $request->request->get('barcode');

            if (!$releaseId) {
                $this->addFlash('error', 'No release ID provided');
                return $this->redirectToRoute('scan');
            }

            // Fetch the complete release data
            $response = $this->client->request(
                'GET',
                'https://musicbrainz.org/ws/2/release/' . $releaseId,
                [
                    'query' => [
                        'fmt' => 'json',
                        'inc' => 'artists+labels+recordings'
                    ],
                    'headers' => [
                        'User-Agent' => 'LibTrack/1.0 (f@feub.net)'
                    ]
                ]
            );

            if ($response->getStatusCode() !== 200) {
                $this->addFlash('error', 'Error fetching release details');
                return $this->redirectToRoute('scan');
            }

            $releaseData = $response->toArray();

            // Check if the release does NOT already exist
            $checkRelease = $releaseRepository->findOneBy(['barcode' => $barcode]);

            if ($checkRelease) {
                $this->addFlash('warning', 'Barcode "' . $barcode . '" already in the database.');
                return $this->redirectToRoute('release.scan');
            }

            $release = new Release();
            $release->setTitle($releaseData['title']);
            $release->setBarcode($barcode);

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
        } catch (AccessDeniedException $e) {
            return $this->render('errors/custom_access_denied.html.twig', [
                'error' => 'You need admin privileges to view this page.',
            ], new Response('', 403));
        }
    }
}
