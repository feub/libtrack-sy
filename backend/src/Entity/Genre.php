<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\GenreRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GenreRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('name')]
#[UniqueEntity('slug')]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\Length(min: 1)]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Assert\Length(min: 1)]
    #[Assert\Length(max: 100)]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: "This is not a valid slug.")]
    private ?string $slug = null;

    /**
     * @var Collection<int, Release>
     */
    #[ORM\ManyToMany(targetEntity: Release::class, inversedBy: 'genres')]
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

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
        }

        return $this;
    }

    public function removeRelease(Release $release): static
    {
        $this->releases->removeElement($release);

        return $this;
    }
}
