<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Livres;
use Faker\Factory;
use App\Entity\Categorie;

class LivresFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $categories = [
            'Roman' => [
                [
                    'titre' => 'Le Petit Prince',
                    'isbn' => '9780156013987',
                    'editeur' => 'Harcourt',
                    'date' => '1943-04-06',
                    'prix' => 12.90,
                    'resume' => "Un aviateur rencontre un petit prince venu d'une autre planète et redécouvre l'essentiel à travers un récit poétique.",
                ],
                [
                    'titre' => "L'Étranger",
                    'isbn' => '9780679720201',
                    'editeur' => 'Vintage',
                    'date' => '1942-01-01',
                    'prix' => 11.50,
                    'resume' => "Un récit sobre et percutant sur l'absurde, la société et la vérité intime d'un homme face au monde.",
                ],
                [
                    'titre' => 'Les Misérables',
                    'isbn' => '9780451419439',
                    'editeur' => 'Signet Classics',
                    'date' => '1862-01-01',
                    'prix' => 14.95,
                    'resume' => "Le destin de Jean Valjean, entre justice, rédemption et lutte contre la misère dans la France du XIXe siècle.",
                ],
            ],
            'Science Fiction' => [
                [
                    'titre' => 'Dune',
                    'isbn' => '9780441172719',
                    'editeur' => 'Ace',
                    'date' => '1965-08-01',
                    'prix' => 15.99,
                    'resume' => "Sur Arrakis, une dynastie affronte intrigues politiques et enjeux écologiques autour de l'épice, ressource la plus précieuse de l'univers.",
                ],
                [
                    'titre' => '1984',
                    'isbn' => '9780451524935',
                    'editeur' => 'Signet Classics',
                    'date' => '1949-06-08',
                    'prix' => 9.99,
                    'resume' => "Dans un régime totalitaire, Winston tente de préserver sa liberté de penser sous l'œil de Big Brother.",
                ],
                [
                    'titre' => 'Foundation',
                    'isbn' => '9780553293357',
                    'editeur' => 'Bantam',
                    'date' => '1951-01-01',
                    'prix' => 10.99,
                    'resume' => "Une organisation scientifique tente de réduire l'âge sombre à venir en préservant la connaissance et en anticipant l'avenir.",
                ],
            ],
            'Histoire' => [
                [
                    'titre' => 'Sapiens: A Brief History of Humankind',
                    'isbn' => '9780062316110',
                    'editeur' => 'Harper',
                    'date' => '2015-02-10',
                    'prix' => 18.00,
                    'resume' => "Une synthèse accessible sur l'histoire de l'humanité, des chasseurs-cueilleurs aux sociétés modernes.",
                ],
                [
                    'titre' => 'The Guns of August',
                    'isbn' => '9780345476098',
                    'editeur' => 'Ballantine Books',
                    'date' => '1962-01-01',
                    'prix' => 16.50,
                    'resume' => "Une plongée dans les débuts de la Première Guerre mondiale, entre décisions politiques, erreurs et enchaînements fatals.",
                ],
            ],
            'Biographie' => [
                [
                    'titre' => 'Becoming',
                    'isbn' => '9781524763138',
                    'editeur' => 'Crown',
                    'date' => '2018-11-13',
                    'prix' => 19.99,
                    'resume' => "Le parcours personnel et public de Michelle Obama, entre ambition, famille et engagement.",
                ],
                [
                    'titre' => 'The Diary of a Young Girl',
                    'isbn' => '9780553296983',
                    'editeur' => 'Bantam',
                    'date' => '1993-06-01',
                    'prix' => 8.99,
                    'resume' => "Le journal poignant d'Anne Frank, écrit pendant la clandestinité, témoignage d'espoir et de lucidité.",
                ],
            ],
            'Fantasy' => [
                [
                    'titre' => 'The Hobbit',
                    'isbn' => '9780547928227',
                    'editeur' => 'Houghton Mifflin Harcourt',
                    'date' => '1937-09-21',
                    'prix' => 12.99,
                    'resume' => "Bilbo Baggins part à l'aventure avec des nains et découvre courage, dragons et trésors inattendus.",
                ],
                [
                    'titre' => 'A Game of Thrones',
                    'isbn' => '9780553573404',
                    'editeur' => 'Bantam',
                    'date' => '1996-08-06',
                    'prix' => 14.99,
                    'resume' => "Rivalités, alliances et trahisons opposent de grandes familles dans une lutte pour le pouvoir.",
                ],
            ],
        ];

        foreach ($categories as $categoryName => $books) {
            $categorie = new Categorie();
            $categorie
                ->setLibelle($categoryName)
                ->setDescription($faker->text(200));
            // Le slug est généré automatiquement via le lifecycle callback.
            $manager->persist($categorie);

            foreach ($books as $book) {
                $livre = new Livres();
                $isbn = $book['isbn'];
                $coverUrl = 'https://covers.openlibrary.org/b/isbn/' . $isbn . '-L.jpg';

                $livre
                    ->setTitre($book['titre'])
                    ->setPrix((float) $book['prix'])
                    ->setIsbn($isbn)
                    ->setEditeur($book['editeur'])
                    ->setDateEdition(new \DateTime($book['date']))
                    ->setImage($coverUrl)
                    ->setResume($book['resume'])
                    ->setCategorie($categorie);

                $manager->persist($livre);
            }
        }

        $manager->flush(); //envoie les insert au bd
    }
}
