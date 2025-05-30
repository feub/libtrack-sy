<?php

namespace App\DataFixtures;

use App\Entity\Genre;
use App\Repository\GenreRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class GenreFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(private GenreRepository $genreRepository, private SluggerInterface $slugger) {}

    public static function getGroups(): array
    {
        return ['genre'];
    }

    public function load(ObjectManager $manager): void
    {
        $genreEntities = [];
        $genres = ['Metal', 'Black Metal', 'Heavy Metal', 'Progressive Rock', 'Rock', 'Space Rock', 'Electronic', 'Ambient'];

        foreach ($genres as $key => $g) {
            $genre = new Genre();
            $genre->setName($g);
            $genre->setSlug(strtolower($this->slugger->slug($g)));
            $manager->persist($genre);
        }

        $manager->flush();
    }
}
