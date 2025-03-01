<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $thumbnail = null;

    /**
     * @var Collection<int, Release>
     */
    #[ORM\ManyToMany(targetEntity: Release::class, mappedBy: 'artist')]
    private Collection $releases;

    public function __construct()
    {
        $this->releases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getThumbnail(): ?string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?string $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * @return Collection<int, Release>
     */
    public function getReleases(): Collection
    {
        return $this->releases;
    }

    public function addRelease(Release $release): static
    {
        if (!$this->releases->contains($release)) {
            $this->releases->add($release);
            $release->addArtist($this);
        }

        return $this;
    }

    public function removeRelease(Release $release): static
    {
        if ($this->releases->removeElement($release)) {
            $release->removeArtist($this);
        }

        return $this;
    }
}
