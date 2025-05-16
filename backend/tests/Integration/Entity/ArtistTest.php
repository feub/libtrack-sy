<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Artist;
use App\DataFixtures\ArtistFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;

class ArtistTest extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function getArtistEntity(): Artist
    {
        return (new Artist())
            ->setName('John Doe')
            ->setSlug('john-doe')
            ->setThumbnail('thumbnail.jpg')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
    }

    public function assertHasErrors(Artist $artist, int $numberOfErrors = 0): void
    {
        self::bootKernel();
        $error = self::getContainer()->get('validator')->validate($artist);
        $this->assertCount($numberOfErrors, $error);
    }

    public function testValidArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity(), 0, "Valid artist");
    }

    // name
    public function testInvalidNameTooLongInArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity()->setName(str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 4)), 1, "Invalid name (too long)");
    }

    public function testInvalidNameTooShortInArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity()->setName(''), 1, "Invalid name (too short)");
    }

    public function testNonUniqueNameInArtistEntity(): void
    {
        $this->databaseTool->loadFixtures([ArtistFixtures::class]);

        $this->assertHasErrors($this->getArtistEntity()->setName('Misanthrope'), 1, "Invalid name (already exists");
    }

    // slug
    public function testInvalidSlugTooLongInArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity()->setSlug(str_repeat('lorem-ipsum-dolor-sit-amet-consectetur-adipiscing-elit', 5)), 1, "Invalid slug (too long)");
    }

    public function testInvalidSlugTooShortInArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity()->setSlug(''), 1, "Invalid slug (too short)");
    }

    public function testInvalidCharacterSlugInArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity()->setSlug('john doe$'), 1, "Invalid slug (invalid character)");
    }

    public function testInvalidEmptySlugInArtistEntity(): void
    {
        $this->assertHasErrors($this->getArtistEntity()->setSlug(''), 1, "Invalid slug (empty)");
    }

    public function testNonUniqueSlugInArtistEntity(): void
    {
        $this->databaseTool->loadFixtures([ArtistFixtures::class]);

        $this->assertHasErrors($this->getArtistEntity()->setSlug('misanthrope'), 1, "Invalid slug (already exists");
    }
}
