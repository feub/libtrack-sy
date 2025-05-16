<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Artist;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ArtistTest extends KernelTestCase
{
    public function getArtistEntity(): Artist
    {
        return (new Artist())
            ->setName('John Doe')
            ->setSlug('john-doe')
            ->setThumbnail('thumbnail.jpg')
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());
    }

    public function testValidArtistEntity(): void
    {
        $artist = $this->getArtistEntity();

        self::bootKernel();
        $error = self::getContainer()->get('validator')->validate($artist);
        $this->assertCount(0, $error, "Valid artist");
    }

    public function testInvalidSlugInArtistEntity(): void
    {
        $artist = $this->getArtistEntity();
        $artist->setSlug('john-doe$');

        self::bootKernel();
        $error = self::getContainer()->get('validator')->validate($artist);
        $this->assertCount(1, $error, "Invalid slug");
    }

    public function testNameTooLongInArtistEntity(): void
    {
        $artist = $this->getArtistEntity();
        $artist->setName(str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 4));

        self::bootKernel();
        $error = self::getContainer()->get('validator')->validate($artist);
        $this->assertCount(1, $error, "Invalid name (too long)");
    }

    public function testNameTooShortInArtistEntity(): void
    {
        $artist = $this->getArtistEntity();
        $artist->setName('');

        self::bootKernel();
        $error = self::getContainer()->get('validator')->validate($artist);
        $this->assertCount(1, $error, "Invalid name (too short)");
    }
}
