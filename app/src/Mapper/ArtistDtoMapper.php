<?php

namespace App\Mapper;

use App\Dto\ArtistDto;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArtistDtoMapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * Map DTO to a new Artist entity
     */
    public function createEntityFromDto(ArtistDto $dto): Artist
    {
        $artist = new Artist();
        $this->mapDtoToEntity($dto, $artist);

        // Set timestamps for new entities
        $now = new \DateTimeImmutable();
        $artist->setCreatedAt($now);
        $artist->setUpdatedAt($now);

        // Generate slug if not provided
        if (!$dto->slug) {
            $slug = $this->slugger->slug(strtolower($dto->name));
            $artist->setSlug($slug);
        }

        return $artist;
    }

    /**
     * Update existing Artist entity from DTO
     */
    public function updateEntityFromDto(ArtistDto $dto, Artist $artist): Artist
    {
        $this->mapDtoToEntity($dto, $artist);
        $artist->setUpdatedAt(new \DateTimeImmutable());

        return $artist;
    }

    /**
     * Map common fields from DTO to entity
     */
    private function mapDtoToEntity(ArtistDto $dto, Artist $artist): void
    {
        if ($dto->name !== null) {
            $artist->setName($dto->name);
        }

        if ($dto->slug !== null) {
            $artist->setSlug($dto->slug);
        }

        if ($dto->thumbnail !== null) {
            $artist->setThumbnail($dto->thumbnail);
        }
    }
}
