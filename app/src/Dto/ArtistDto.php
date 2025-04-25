<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ArtistDto
{
    #[Assert\NotBlank(message: "Name is required")]
    #[Assert\Length(max: 100, maxMessage: "Name cannot be longer than {{ limit }} characters")]
    public ?string $name = null;

    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: "This is not a valid slug."
    )]
    public ?string $slug = null;

    public ?string $thumbnail = null;

    /**
     * Create a new ArtistDto from request data
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->name = $data['name'] ?? null;
        $dto->slug = $data['slug'] ?? null;
        $dto->thumbnail = $data['cover'] ?? null;

        return $dto;
    }
}
