<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Genre;
use App\Entity\Artist;
use App\Dto\ReleaseDto;
use App\Entity\Release;
use Psr\Log\LoggerInterface;
use App\Mapper\ReleaseDtoMapper;
use App\Repository\GenreRepository;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReleaseService
{
    private $coverDir;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private SluggerInterface $slugger,
        private EntityManagerInterface $em,
        private DiscogsService $discogsService,
        private ReleaseRepository $releaseRepository,
        private ArtistRepository $artistRepository,
        private GenreRepository $genreRepository,
        private Security $security,
        private NormalizerInterface $serializer,
        private ReleaseDtoMapper $releaseDtoMapper,
        private ValidatorInterface $validator
    ) {
        $this->coverDir = $params->get('images_dir') . '/covers/';
    }

    /**
     * Get paginated releases
     *
     * @param User $user User requesting the releases
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @param string $sortBy Field to sort by
     * @param string $sortDir Sort direction (ASC or DESC)
     * @param string $searchTerm Optional search term
     * @return array Array containing releases data, total, max page, current page and pagination properties
     */
    public function getPaginatedReleases(User $user, int $page = 1, int $limit = 20, string $sortBy = 'createdAt', string $sortDir = 'ASC', ?string $searchTerm = '', ?string $searchShelf = '', ?bool $featured = null): array
    {
        $paginationResult = $this->releaseRepository->paginatedReleases($user, $page, $limit, $sortBy, $sortDir, $searchTerm, $searchShelf, $featured);

        return [
            'releases' => $paginationResult->getItems(),
            'totalReleases' => $paginationResult->getTotalItems(),
            'maxPage' => $paginationResult->getTotalPages(),
            'page' => $paginationResult->getCurrentPage(),
            'hasNextPage' => $paginationResult->hasNextPage(),
            'hasPreviousPage' => $paginationResult->hasPreviousPage(),
            'nextPage' => $paginationResult->getNextPage(),
            'previousPage' => $paginationResult->getPreviousPage()
        ];
    }

    /**
     * Get formated release data
     *
     * @param int $id Release ID
     * @param User $user User requesting the release
     * @return array Formated release data
     * @throws NotFoundHttpException If the release does not exist
     */
    public function getReleaseFormatted(int $id, User $user): array
    {
        $release = $this->releaseRepository->getRelease($id, $user);

        if (!$release) {
            throw new NotFoundHttpException('Release not found');
        }

        return $this->serializer->normalize(
            $release,
            null,
            [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                },
                'groups' => ['api.release.list']
            ]
        );
    }

    /**
     * Create release from DTO
     *
     * @param ReleaseDto $dto
     * @param User $user
     * @return Release
     */
    public function createFromDto(ReleaseDto $dto, User $user): Release
    {
        // Check if the barcode already exists for that user
        if (!empty($dto->barcode) && $dto->barcode !== null) {
            $existingRelease = $this->releaseRepository->findByBarcodeAndUser(
                $dto->barcode,
                $user
            );

            if ($existingRelease) {
                $this->logger->error('Barcode "' . $dto->barcode . '" already exists for user ' . $user->getId());
                throw new ConflictHttpException('Barcode ' . $dto->barcode . ' already exists in your collection.');
            }
        }

        // Create a new release entity from the DTO
        $release = $this->releaseDtoMapper->createEntityFromDto($dto);

        // Associate the release with the user
        $release->addUser($user);

        // Persist and flush
        $this->em->persist($release);
        $this->em->flush();

        return $release;
    }

    /**
     * Update the release from the DTO
     *
     * @param Release $release
     * @param ReleaseDto $dto
     * @param User $user
     * @return Release
     */
    public function updateFromDto(Release $release, ReleaseDto $dto, User $user): Release
    {
        $this->releaseDtoMapper->updateEntityFromDto($dto, $release);

        $this->em->flush();

        return $release;
    }

    /**
     * Download cover art image from Discogs service and save it to the cover directory
     *
     * @param string $coverUrl
     * @param string $id
     * @return string
     */
    public function downloadCovertArt(string $coverUrl, string $id): string
    {
        if (!is_dir($this->coverDir)) {
            mkdir($this->coverDir, 0775, true);
        }

        $coverPath = $this->coverDir . $id . '.jpg';

        // Use cURL instead of file_get_contents
        $ch = curl_init($coverUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $coverContent = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logger->error('Cover download failed: ' . curl_error($ch));
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        file_put_contents($coverPath, $coverContent);

        return $id . '.jpg';
    }

    /**
     * Save cover file name to the release entity
     *
     * @param integer $id
     * @param string|null $cover
     * @return void
     */
    public function setCover(int $id, ?string $cover): void
    {
        $release = $this->releaseRepository->find($id);

        if (!$release) {
            throw new NotFoundHttpException('Release not found');
        }

        if ($cover) {
            $release->setCover($cover);
        } else {
            $release->setCover(null);
        }

        $this->em->persist($release);
        $this->em->flush();
    }


    public function setFormat(string $format): ?array
    {
        if ($format === "Vinyl") {
            return ['id' => 2];
        } elseif ($format === "CD") {
            return ['id' => 1];
        } else {
            return null;
        }
    }

    /**
     * Add a scanned release to the user's collection
     *
     * @param User $user
     * @param string $releaseId
     * @param string|null $barcode
     * @param integer|null $shelf
     * @return void
     */
    public function addScannedRelease(
        User $user,
        string $releaseId,
        ?string $barcode,
        ?int $shelf
    ) {
        // Fetch the complete release data
        try {
            $releaseData = $this->discogsService->getReleaseById($releaseId);
            $formattedData = [
                'title' => $releaseData['title'] ?? null,
                'release_date' => $releaseData['year'] <= 1000 ? null : $releaseData['year'],
                'barcode' => (!$barcode || $barcode === '') ? null : $barcode, // Use provided barcode or null as empty strings are treated as actual values
                'cover' => $releaseData['images'][0]['uri'] ? $this->downloadCovertArt($releaseData['images'][0]['uri'], $releaseData['id']) : null,
                'artists' => array_map(function ($artist) {
                    return ['id' => $this->getArtistIdByName($artist['name'])];
                }, $releaseData['artists'] ?? []),
                'format' => $releaseData['formats'][0]["name"] ? $this->setFormat($releaseData['formats'][0]["name"]) : null,
                'shelf' => ["id" => $shelf] ?? null,
                'genres' => array_map(function ($style) {
                    return ['id' => $this->getGenreIdByName($style)];
                }, $releaseData['styles'] ?? []),
            ];
        } catch (\Exception $e) {
            $this->logger->error("Release data not found: $e");
            throw new NotFoundHttpException('Release data not found.', $e);
        }

        // Create and validate DTO
        $releaseDto = ReleaseDto::fromArray($formattedData);
        $violations = $this->validator->validate($releaseDto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            throw new BadRequestHttpException('Validation failed: ' . json_encode($errors));
        }

        // Create the release
        $release = $this->createFromDto($releaseDto, $user);

        return $release;
    }

    /**
     * Clean artist name by removing the trailing number in parentheses Discogs is adding to some artist names
     *
     * @param string $artistName
     * @return string
     */
    private function stripArtistName(string $artistName): string
    {
        return preg_replace('/\s*\(\d+\)$/', '', $artistName);
    }

    /**
     * Find or create an artist by name and return its ID
     *
     * @param string $artistName The name of the artist to find or create
     * @return int The ID of the found or created artist
     */
    private function getArtistIdByName(string $artistName): int
    {
        $cleanName = $this->stripArtistName($artistName);

        $artist = $this->artistRepository->findOneBy(['name' => $cleanName]);

        if (!$artist) {
            $artist = new Artist;
            $artist->setName($cleanName);

            $slug = $this->slugger->slug(strtolower($cleanName))->toString();
            $artist->setSlug($slug);

            $now = new \DateTimeImmutable();
            $artist->setCreatedAt($now);
            $artist->setUpdatedAt($now);

            $this->em->persist($artist);
            $this->em->flush();

            $this->logger->info("Created new artist: $cleanName");
        }

        return $artist->getId();
    }

    /**
     * Find or create a genre by name and return its ID
     *
     * @param string $genreName The name of the genre to find or create
     * @return int The ID of the found or created genre
     */
    private function getGenreIdByName(string $genreName): int
    {
        $genre = $this->genreRepository->findOneBy(['name' => $genreName]);

        if (!$genre) {
            $genre = new Genre;
            $genre->setName($genreName);

            $slug = $this->slugger->slug(strtolower($genreName))->toString();
            $genre->setSlug($slug);

            $this->em->persist($genre);
            $this->em->flush();

            $this->logger->info("Created new genre: $genreName");
        }

        return $genre->getId();
    }

    /**
     * Delete a user's release
     *
     * @param Release $release
     * @return void
     */
    public function deleteRelease(Release $release): void
    {
        if (!$release) {
            throw new NotFoundHttpException('Release not found');
        }

        try {
            $this->em->remove($release);
            $this->em->flush();

            $coverPath = $this->coverDir . $release->getCover();

            if ($release->getCover() && file_exists($coverPath)) {
                if (!unlink($coverPath)) {
                    $this->logger->error("Failed to delete the cover file at path: $coverPath");
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while deleting the release: " . $e->getMessage());
            throw new \RuntimeException("Failed to delete the release: " . $e->getMessage());
        }
    }

    /**
     * Get a single release
     *
     * @param User $user User requesting the release
     * @param int $id ID of the release
     * @return ?Release Release containing the release data or null
     */
    public function getRelease(User $user, int $id): ?Release
    {
        $release = $this->releaseRepository->getRelease($id, $user);

        return $release;
    }
}
