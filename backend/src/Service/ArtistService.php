<?php

namespace App\Service;

use App\Entity\Artist;
use Psr\Log\LoggerInterface;
use App\Repository\ArtistRepository;
use App\Dto\ArtistDto;
use App\Mapper\ArtistDtoMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ArtistService
{
    private $coverDir;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private SluggerInterface $slugger,
        private EntityManagerInterface $em,
        private ArtistRepository $artistRepository,
        private Security $security,
        private NormalizerInterface $serializer,
        private ArtistDtoMapper $artistDtoMapper
    ) {}

    /**
     * Get paginated artists
     *
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @return array Array containing artists data, total artists, max page and current page
     */
    public function getPaginatedArtists(int $page = 1, int $limit = 20): array
    {
        $totalArtists = $this->artistRepository->getTotalArtists();
        $artists = $this->artistRepository->paginatedArtists($page, $limit);

        $maxpage = ceil($totalArtists / $limit);

        return [
            'artists' => $artists->getItems(),
            'totalArtists' => $totalArtists,
            'maxPage' => $maxpage,
            'page' => $page
        ];
    }

    /**
     * Get formated artist data
     *
     * @param int $id Artist ID
     * @return array Formated artist data
     * @throws NotFoundHttpException If the artist does not exist
     */
    public function getArtistFormatted(int $id): array
    {
        $artist = $this->artistRepository->getArtist($id);

        if (!$artist) {
            throw new NotFoundHttpException('Artist not found');
        }

        return $this->serializer->normalize(
            $artist,
            // null,
            // [
            //     'circular_reference_handler' => function ($object) {
            //         return $object->getId();
            //     },
            //     'groups' => ['api.release.list']
            // ]
        );
    }

    // Create artist from DTO
    public function createFromDto(ArtistDto $dto): Artist
    {
        // Check if the artist already exists
        if ($this->artistRepository->findOneBy(['name' => $dto->name])) {
            $this->logger->error('Artist name "' . $dto->name . '" already exists.');
            throw new BadRequestHttpException('Artist name "' . $dto->name . '" already exists.');
        }

        // Create a new artist entity from the DTO
        $artist = $this->artistDtoMapper->createEntityFromDto($dto);

        // Persist and flush
        $this->em->persist($artist);
        $this->em->flush();

        return $artist;
    }

    // Update from DTO
    public function updateFromDto(Artist $artist, ArtistDto $dto): Artist
    {
        // Update the artist from the DTO
        $this->artistDtoMapper->updateEntityFromDto($dto, $artist);

        $this->em->flush();

        return $artist;
    }

    public function deleteArtist(Artist $artist): void
    {
        if (!$artist) {
            throw new NotFoundHttpException('Artist not found');
        }

        try {
            $this->em->remove($artist);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while deleting the artist: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Get a single artist
     *
     * @param int $id ID of the artist
     * @return ?Artist Artist containing the artist data or null
     */
    public function getArtist(int $id): ?Artist
    {
        $artist = $this->artistRepository->getArtist($id);

        return $artist;
    }
}
