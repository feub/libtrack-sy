<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\Release;
use App\Repository\ArtistRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArtistFixtures extends Fixture
{
    public function __construct(private ArtistRepository $artistRepository) {}

    public function load(ObjectManager $manager): void
    {
        $artistEntities = [];
        $artists = ['Misanthrope', 'Black Sabbath', 'Nightfall', 'Septic Flesh', 'Vangelis'];

        foreach ($artists as $key => $a) {
            $artist = new Artist();
            $artist->setName($a);
            $manager->persist($artist);

            // Store references to artist entities
            $artistEntities[$a] = $artist;

            // Optionally define a reference that can be used in other fixtures
            $this->addReference('artist_' . $key, $artist);
        }

        // Flush to ensure artists are in the database
        $manager->flush();

        $releases = [
            [
                'title' => '1666... Theatre Bizare',
                'release_date' => 1995,
                'cover' => null,
                'barcode' => '1234567890',
                'artist' => 'Misanthrope'
            ],
            [
                'title' => 'Headless Cross',
                'release_date' => 1989,
                'cover' => null,
                'barcode' => '1111111111',
                'artist' => 'Black Sabbath'
            ],
            [
                'title' => 'Athenian Echoes',
                'release_date' => 1995,
                'cover' => null,
                'barcode' => '2222222222',
                'artist' => 'Nightfall'
            ],
            [
                'title' => 'Mystic Places of Dawn',
                'release_date' => 1994,
                'cover' => null,
                'barcode' => '1234567899',
                'artist' => 'Septic Flesh'
            ],
            [
                'title' => 'Esoptron',
                'release_date' => 1995,
                'cover' => null,
                'barcode' => '1233367899',
                'artist' => 'Septic Flesh'
            ],
            [
                'title' => 'Opéra Sauvage',
                'release_date' => 1979,
                'cover' => null,
                'barcode' => '1234567888',
                'artist' => 'Vangelis'
            ],
        ];

        // Create and persist releases with their artist relationships
        foreach ($releases as $r) {
            $release = new Release();
            $release->setTitle($r['title']);
            $release->setReleaseDate($r['release_date']);
            $release->setCover($r['cover']);
            $release->setBarcode($r['barcode']);

            // Get the artist entity from our stored references
            $artist = $artistEntities[$r['artist']];
            $release->addArtist($artist);

            $manager->persist($release);
        }

        $manager->flush();
    }
}
