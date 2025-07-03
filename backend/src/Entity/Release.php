<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ReleaseRepository;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReleaseRepository::class)]
#[ORM\Table(name: '`release`')]
#[UniqueEntity('slug')]
#[Vich\Uploadable]
class Release
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['api.release.list'])]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\Length(min: 1, groups: ['Extra'])]
    #[Assert\Length(max: 150, groups: ['Extra'])]
    #[Groups(['api.release.list'])]
    private ?string $title = null;

    #[ORM\Column(length: 150)]
    #[Assert\Length(min: 1)]
    #[Assert\Length(max: 150)]
    #[Assert\Regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: "This is not a valid slug.")]
    #[Groups(['api.release.list'])]
    private ?string $slug = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Positive()]
    #[Assert\GreaterThan(value: 1000)]
    #[Groups(['api.release.list'])]
    private ?int $release_date = null;

    #[Vich\UploadableField(mapping: 'cover_images', fileNameProperty: 'cover')]
    private ?File $coverFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['api.release.list'])]
    private ?string $cover = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['api.release.list'])]
    private ?string $barcode = null;

    /**
     * @var Collection<int, Artist>
     */
    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'releases')]
    #[Groups(['api.release.list'])]
    private Collection $artists;

    #[ORM\Column]
    #[Groups(['api.release.list'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['api.release.list'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'releases')]
    #[Groups(['api.release.list'])]
    private ?Shelf $shelf = null;

    #[ORM\ManyToOne(inversedBy: 'releases')]
    #[Groups(['api.release.list'])]
    private ?Format $format = null;

    /**
     * @var Collection<int, Genre>
     */
    #[ORM\ManyToMany(targetEntity: Genre::class, mappedBy: 'releases')]
    #[Groups(['api.release.list'])]
    private Collection $genres;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['api.release.list'])]
    private bool $featured = false;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'releases')]
    private Collection $users;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['api.release.list'])]
    private ?string $note = null;

    public function __construct()
    {
        $this->artists = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getReleaseDate(): ?int
    {
        return $this->release_date;
    }

    public function setReleaseDate(?int $release_date): static
    {
        $this->release_date = $release_date;

        return $this;
    }

    /**
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $coverFile
     */
    public function setCoverFile(?File $coverFile = null): void
    {
        $this->coverFile = $coverFile;

        if (null !== $coverFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getCoverFile(): ?File
    {
        return $this->coverFile;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(?string $cover): static
    {
        $this->cover = $cover;

        return $this;
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(?string $barcode): static
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getArtists(): Collection
    {
        return $this->artists;
    }

    public function addArtist(Artist $artists): static
    {
        if (!$this->artists->contains($artists)) {
            $this->artists->add($artists);
        }

        return $this;
    }

    public function removeArtist(Artist $artist): static
    {
        $this->artists->removeElement($artist);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    public function getShelf(): ?Shelf
    {
        return $this->shelf;
    }

    public function setShelf(?Shelf $shelf): static
    {
        $this->shelf = $shelf;

        return $this;
    }

    public function getFormat(): ?Format
    {
        return $this->format;
    }

    public function setFormat(?Format $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
            $genre->addRelease($this);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        if ($this->genres->removeElement($genre)) {
            $genre->removeRelease($this);
        }

        return $this;
    }

    public function isFeatured(): bool
    {
        return $this->featured;
    }

    public function setFeatured(bool $featured): static
    {
        $this->featured = $featured;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
