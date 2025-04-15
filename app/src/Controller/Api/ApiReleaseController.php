<?php

namespace App\Controller\Api;

use App\Entity\Release;
use App\Service\DiscogsService;
use App\Service\ReleaseService;
use App\Repository\ReleaseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ApiResponseService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/release', name: 'api.release.')]
final class ApiReleaseController extends AbstractApiController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ApiResponseService $apiResponseService,
        private HttpClientInterface $client,
        private SluggerInterface $slugger
    ) {
        parent::__construct($entityManager, $apiResponseService);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->apiResponseService->success('LibTrack API is running.');
    }

    #[Route('/scan', name: 'scan', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scan(
        Request $request,
        DiscogsService $discogsService
    ): Response {
        $barcode = $request->toArray();
        $barcode = $barcode['barcode'];
        $releases = null;

        if (!$barcode) {
            return $this->apiResponseService->error(
                'Barcode is required',
                Response::HTTP_BAD_REQUEST
            );
        }

        // ApiExceptionSubscriber handles exceptions
        $releases = $discogsService->getReleaseByBarcode($barcode);

        return $this->apiResponseService->success(
            'Available releases for the barcode: ' . $barcode,
            [
                'barcode' => $barcode,
                'releases' => $releases
            ]
        );
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scanAdd(
        Request $request,
        ReleaseService $releaseService
    ): Response {
        // Get the JSON payload
        $data = json_decode($request->getContent(), true);
        $releaseId = $data['release_id'] ?? null;
        $barcode = $data['barcode'] ?? null;

        if (!$releaseId) {
            return $this->apiResponseService->error(
                'No release ID provided',
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$barcode) {
            return $this->apiResponseService->error(
                'No barcode provided',
                Response::HTTP_BAD_REQUEST
            );
        }

        // ApiExceptionSubscriber handles exceptions
        $release = $releaseService->addRelease($releaseId, $barcode);
        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" added successfully'
        );
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(ReleaseRepository $releaseRepository, Request $request): Response
    {
        $totalReleases = $releaseRepository->getTotalReleases();

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $searchTerm = $request->query->getString('search', '');

        $releases = $releaseRepository->paginatedReleases($page, $limit, $searchTerm);

        if (!empty($searchTerm)) {
            $totalReleases = $releases->count();
        }

        $maxpage = ceil($totalReleases / $limit);

        $releasesData = [];

        foreach ($releases as $release) {
            $artists = $release->getArtists();
            $artistsData = [];

            $format = $release->getFormat();
            $shelf = $release->getShelf();

            foreach ($artists as $artist) {
                $artistsData[] = [
                    'id' => $artist->getId(),
                    'name' => $artist->getName(),
                ];
            }
            $releasesData[] = [
                'id' => $release->getId(),
                'title' => $release->getTitle(),
                'cover' => $release->getCover(),
                'release_date' => $release->getReleaseDate(),
                'artists' => $artistsData,
                'barcode' => $release->getBarcode(),
                'format' => $format?->getName() ?? '',
                'shelf' => $shelf?->getLocation() ?? ''
            ];
        }

        return $this->apiResponseService->success(
            'Releases retrieved successfully',
            [
                'releases' => $releasesData,
                'totalReleases' => $totalReleases,
                'maxPage' => $maxpage,
                'page' => $page
            ]
        );
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id, ReleaseService $releaseService): Response
    {
        // Use findOr404 method to find the entity or return a 404
        $releaseOrResponse = $this->findOr404(Release::class, $id);

        // If a response is returned (404), return it
        if ($releaseOrResponse instanceof Response) {
            return $releaseOrResponse;
        }

        // ApiExceptionSubscriber will handle exceptions
        $releaseService->deleteRelease($releaseOrResponse);
        return $this->apiResponseService->success('Release successfully deleted');
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function view(int $id): Response
    {
        $releaseOrResponse = $this->findOr404(Release::class, $id);

        if ($releaseOrResponse instanceof Response) {
            return $releaseOrResponse;
        }

        $release = $releaseOrResponse;
        $artists = $release->getArtists();
        $artistsData = [];

        $format = $release->getFormat();
        $shelf = $release->getShelf();

        foreach ($artists as $artist) {
            $artistsData[] = [
                'id' => $artist->getId(),
                'name' => $artist->getName(),
            ];
        }

        $releaseData = [
            'id' => $release->getId(),
            'title' => $release->getTitle(),
            'cover' => $release->getCover(),
            'release_date' => $release->getReleaseDate(),
            'artists' => $artistsData,
            'barcode' => $release->getBarcode(),
            'format' => $format ? [
                'id' => $format->getId(),
                'name' => $format->getName()
            ] : null,
            'shelf' => $shelf ? [
                'id' => $shelf->getId(),
                'location' => $shelf->getLocation()
            ] : null
        ];

        return $this->apiResponseService->success(
            'Release details retrieved successfully',
            ['release' => $releaseData]
        );
    }

    #[Route('/edit/{id}', name: 'edit', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(int $id, Request $request): Response
    {
        // Example of a POST request to edit a release
        // {
        //     "title": "New Title",
        //     "release_date": 1995,
        //     "barcode": "1234567890",
        //     "artists": [
        //       {"id": 1},
        //       {"id": 2}
        //     ],
        //     "format": {
        //       "id": 1
        //     },
        //     "shelf": {
        //       "id": 2
        //     }
        //   }

        $releaseOrResponse = $this->findOr404(Release::class, $id);

        if ($releaseOrResponse instanceof Response) {
            return $releaseOrResponse;
        }

        $release = $releaseOrResponse;
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $release->setTitle($data['title']);
        }

        if (isset($data['release_date'])) {
            $release->setReleaseDate($data['release_date']);
        }

        if (isset($data['cover'])) {
            $release->setCover($data['cover']);
        }

        if (isset($data['barcode'])) {
            $release->setBarcode($data['barcode']);
        }

        // Artists
        if (isset($data['artists']) && is_array($data['artists'])) {
            $artistRepository = $this->entityManager->getRepository(\App\Entity\Artist::class);

            // Create a collection with IDs of current artists
            $currentArtistIds = [];
            foreach ($release->getArtists() as $artist) {
                $currentArtistIds[] = $artist->getId();
            }

            // Create a collection with the new artists IDs
            $newArtistIds = [];
            foreach ($data['artists'] as $artistData) {
                if (isset($artistData['id'])) {
                    $newArtistIds[] = $artistData['id'];
                }
            }

            // Remove artists not associated
            foreach ($release->getArtists() as $artist) {
                if (!in_array($artist->getId(), $newArtistIds)) {
                    $release->removeArtist($artist);
                }
            }

            // Add new artists
            foreach ($newArtistIds as $artistId) {
                if (!in_array($artistId, $currentArtistIds)) {
                    $artist = $artistRepository->find($artistId);
                    if ($artist) {
                        $release->addArtist($artist);
                    }
                }
            }
        }

        // Format
        if (isset($data['format'])) {
            if (isset($data['format']['id'])) {
                $formatRepository = $this->entityManager->getRepository(\App\Entity\Format::class);
                $format = $formatRepository->find($data['format']['id']);

                if ($format) {
                    $release->setFormat($format);
                }
            } elseif ($data['format'] === null) {
                $release->setFormat(null);
            }
        }

        // Shelf
        if (isset($data['shelf'])) {
            if (isset($data['shelf']['id'])) {
                $shelfRepository = $this->entityManager->getRepository(\App\Entity\Shelf::class);
                $shelf = $shelfRepository->find($data['shelf']['id']);

                if ($shelf) {
                    $release->setShelf($shelf);
                }
            } elseif ($data['shelf'] === null) {
                $release->setShelf(null);
            }
        }

        $release->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" updated successfully'
        );
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, ReleaseService $releaseService): Response
    {
        // Example of a POST request to create a release
        // {
        //     "title": "Nightfall Farmer",
        //     "release_date": 1995,
        //     "barcode": "1234567890",
        //     "artists": "Death",
        //     "cover": "https://yukonwildlife.ca/2021-04-uneasy-neighbours-red-foxes-and-arctic-foxes-in-the-north/",
        //     "format": {
        //       "id": 2
        //     },
        //     "shelf": {
        //       "id": 2
        //     }
        //   }

        $data = json_decode($request->getContent(), true);
        $release = $releaseService->manualAddRelease($data);

        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" created successfully'
        );
    }
}
