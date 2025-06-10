<?php

namespace App\Service;

use App\Dto\GenreDto;
use App\Entity\Genre;
use Psr\Log\LoggerInterface;
use App\Mapper\GenreDtoMapper;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GenreService
{
    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private SluggerInterface $slugger,
        private EntityManagerInterface $em,
        private GenreRepository $genreRepository,
        private Security $security,
        private NormalizerInterface $serializer,
        private GenreDtoMapper $genreDtoMapper
    ) {}

    /**
     * Get paginated genres
     *
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @return array Array containing genres data, total max page and current page
     */
    public function getPaginatedGenres(int $page = 1, int $limit = 20): array
    {
        $total = $this->genreRepository->getTotalGenres();
        $genres = $this->genreRepository->paginatedGenres($page, $limit);

        $maxpage = ceil($total / $limit);

        return [
            'genres' => $genres->getItems(),
            'total' => $total,
            'maxPage' => $maxpage,
            'page' => $page
        ];
    }

    public function deleteGenre(Genre $genre): void
    {
        if (!$genre) {
            throw new NotFoundHttpException('Genre not found');
        }

        try {
            $this->em->remove($genre);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while deleting the genre: " . $e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Get a single genre
     *
     * @param int $id ID of the genre
     * @return ?Genre Genre containing the genre data or null
     */
    public function getGenre(int $id): ?Genre
    {
        return $this->genreRepository->getGenre($id);
    }

    // Create genre from DTO
    public function createFromDto(GenreDto $dto): Genre
    {
        // Check if the genre already exists
        if ($this->genreRepository->findOneBy(['name' => $dto->name])) {
            $this->logger->error('Genre name "' . $dto->name . '" already exists.');
            throw new BadRequestHttpException('Genre name "' . $dto->name . '" already exists.');
        }

        // Create a new genre entity from the DTO
        $genre = $this->genreDtoMapper->createEntityFromDto($dto);

        // Persist and flush
        $this->em->persist($genre);
        $this->em->flush();

        return $genre;
    }

    // Update from DTO
    public function updateFromDto(Genre $genre, GenreDto $dto): Genre
    {
        // Update the genre from the DTO
        $this->genreDtoMapper->updateEntityFromDto($dto, $genre);

        $this->em->flush();

        return $genre;
    }
}
