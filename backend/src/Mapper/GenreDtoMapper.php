<?php

namespace App\Mapper;

use App\Dto\GenreDto;
use App\Entity\Genre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class GenreDtoMapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * Map DTO to a new Genre entity
     */
    public function createEntityFromDto(GenreDto $dto): Genre
    {
        $genre = new Genre();
        $this->mapDtoToEntity($dto, $genre);

        // Generate slug if not provided
        if (!$dto->slug) {
            $slug = $this->slugger->slug(strtolower($dto->name));
            $genre->setSlug($slug);
        }

        return $genre;
    }

    /**
     * Update existing Genre entity from DTO
     */
    public function updateEntityFromDto(GenreDto $dto, Genre $genre): Genre
    {
        $this->mapDtoToEntity($dto, $genre);

        return $genre;
    }

    /**
     * Map common fields from DTO to entity
     */
    private function mapDtoToEntity(GenreDto $dto, Genre $genre): void
    {
        if ($dto->name !== null) {
            $genre->setName($dto->name);
        }

        if ($dto->slug !== null) {
            $genre->setSlug($dto->slug);
        }
    }
}
