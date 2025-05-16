<?php

namespace App\Tests\Integration\Repository;

use App\DataFixtures\ArtistFixtures;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;

class ArtistRepositoryTest extends WebTestCase
{
    /** @var AbstractDatabaseTool */
    protected $databaseTool;

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    public function testCountArtists()
    {
        $this->databaseTool->loadFixtures([ArtistFixtures::class]);

        self::bootKernel();
        $artists = self::getContainer()->get(ArtistRepository::class)->count([]);
        $this->assertEquals(5, $artists, "Count of artists should be 5");
    }
}
