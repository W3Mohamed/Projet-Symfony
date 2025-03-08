<?php

namespace App\DataFixtures;

use App\Entity\Movie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MovieFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $movie = new Movie();
        $movie->setTitle('The dark night');
        $movie->setReleaseYear(2008);
        $movie->setDescription('This is the description of the dark night');
        $movie->setImagePath('https://images.pexels.com/photos/66134/pexels-photo-66134.jpeg?auto=compress&cs=tinysrgb&w=600');
        $movie->AddActor($this->getReference('actor_1'));
        $movie->AddActor($this->getReference('actor_2'));
        $manager->persist($movie);

        $movie2 = new Movie();
        $movie2->setTitle('Avengers');
        $movie2->setReleaseYear(2019);
        $movie2->setDescription('This is the description of Avengers');
        $movie2->setImagePath('https://images.pexels.com/photos/12689078/pexels-photo-12689078.jpeg?auto=compress&cs=tinysrgb&w=600');
        $movie2->AddActor($this->getReference('actor_3'));
        $movie2->AddActor($this->getReference('actor_4'));
        $manager->persist($movie2);

        $manager->flush();
    }
}
