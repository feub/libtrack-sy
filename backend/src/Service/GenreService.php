<?php

namespace App\Service;

use App\Entity\Genre;
use Psr\Log\LoggerInterface;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
        return $this->genreRepository->getArtist($id);
    }
}
