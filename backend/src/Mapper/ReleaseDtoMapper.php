<?php

namespace App\Mapper;

use App\Dto\ReleaseDto;
use App\Entity\Artist;
use App\Entity\Format;
use App\Entity\Release;
use App\Entity\Shelf;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ReleaseDtoMapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger
    ) {}

    /**
     * Map DTO to a new Release entity
     */
    public function createEntityFromDto(ReleaseDto $dto): Release
    {
        $release = new Release();
        $this->mapDtoToEntity($dto, $release);

        // Set timestamps for new entities
        $now = new \DateTimeImmutable();
        $release->setCreatedAt($now);
        $release->setUpdatedAt($now);

        // Generate slug if not provided
        if (!$dto->slug) {
            $slug = $this->slugger->slug(strtolower($dto->title . '-' . $dto->barcode . '-' . rand(10000, 79999)));
            $release->setSlug($slug);
        }

        return $release;
    }

    /**
     * Update existing Release entity from DTO
     */
    public function updateEntityFromDto(ReleaseDto $dto, Release $release): Release
    {
        $this->mapDtoToEntity($dto, $release);
        $release->setUpdatedAt(new \DateTimeImmutable());

        return $release;
    }

    /**
     * Map common fields from DTO to entity
     */
    private function mapDtoToEntity(ReleaseDto $dto, Release $release): void
    {
        if ($dto->title !== null) {
            $release->setTitle($dto->title);
        }

        if ($dto->slug !== null) {
            $release->setSlug($dto->slug);
        }

        if ($dto->release_date !== null) {
            $release->setReleaseDate($dto->release_date);
        }

        if ($dto->cover !== null) {
            $release->setCover($dto->cover);
        }

        if ($dto->barcode !== null) {
            $release->setBarcode($dto->barcode);
        }

        // Handle artists
        if ($dto->artists !== null) {
            $this->mapArtists($dto->artists, $release);
        }

        // Handle format
        if ($dto->format !== null) {
            $this->mapFormat($dto->format, $release);
        }

        // Handle shelf
        if ($dto->shelf !== null) {
            $this->mapShelf($dto->shelf, $release);
        }
    }

    /**
     * Map artists from DTO to entity
     */
    private function mapArtists(array $artistsData, Release $release): void
    {
        // Clear existing artist relations for proper update
        foreach ($release->getArtists() as $existingArtist) {
            $release->removeArtist($existingArtist);
        }

        // Add new artists
        foreach ($artistsData as $artistData) {
            if (isset($artistData['id'])) {
                $artist = $this->entityManager->getRepository(Artist::class)->find($artistData['id']);
                if ($artist) {
                    $release->addArtist($artist);
                }
            }
        }
    }

    /**
     * Map format from DTO to entity
     */
    private function mapFormat(array $formatData, Release $release): void
    {
        if (isset($formatData['id'])) {
            $format = $this->entityManager->getRepository(Format::class)->find($formatData['id']);
            if ($format) {
                $release->setFormat($format);
            }
        }
    }

    /**
     * Map shelf from DTO to entity
     */
    private function mapShelf(array $shelfData, Release $release): void
    {
        if (isset($shelfData['id'])) {
            $shelf = $this->entityManager->getRepository(Shelf::class)->find($shelfData['id']);
            if ($shelf) {
                $release->setShelf($shelf);
            }
        }
    }
}
