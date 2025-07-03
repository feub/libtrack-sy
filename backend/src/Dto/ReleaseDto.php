<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ReleaseDto
{
    #[Assert\NotBlank(message: "Title is required")]
    #[Assert\Length(max: 150, maxMessage: "Title cannot be longer than {{ limit }} characters")]
    public ?string $title = null;

    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: "This is not a valid slug."
    )]
    public ?string $slug = null;

    #[Assert\GreaterThan(value: 1000, message: "Year must be valid (1000+)")]
    public ?int $release_date = null;

    #[Assert\Length(max: 255, maxMessage: "Cover URL cannot be longer than {{ limit }} characters")]
    public ?string $cover = null;

    #[Assert\Regex(
        pattern: '/^$|^\d+$/', // allow either empty strings (^$) or numeric strings (^\d+$)
        message: "Barcode must contain only numbers"
    )]
    public ?string $barcode = null;

    /**
     * @var array<array{id: int}>|null
     */
    #[Assert\NotBlank(message: "At least one artist must be chosen")]
    public ?array $artists = null;

    /**
     * @var array{id: int}|null
     */
    public ?array $format = null;

    /**
     * @var array{id: int}|null
     */
    public ?array $shelf = null;

    /**
     * @var array<array{id: int}>|null
     */
    public ?array $genres = null;

    #[Assert\Type('boolean')]
    public ?bool $featured = null;

    public ?string $note = null;

    /**
     * Create a new ReleaseDto from request data
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->title = $data['title'] ?? null;
        $dto->slug = $data['slug'] ?? null;
        $dto->release_date = isset($data['release_date']) ? (int) $data['release_date'] : null;
        $dto->cover = $data['cover'] ?? null;
        $dto->barcode = $data['barcode'] ?? null;
        $dto->artists = $data['artists'] ?? null;
        $dto->format = $data['format'] ?? null;
        $dto->shelf = $data['shelf'] ?? null;
        $dto->genres = $data['genres'] ?? null;
        $dto->featured = isset($data['featured']) ? filter_var($data['featured'], FILTER_VALIDATE_BOOLEAN) : null;
        $dto->note = $data['note'] ?? null;

        return $dto;
    }
}
