<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\ArticleTranslation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use League\Csv\Reader;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $csv = Reader::createFromPath('data/amazon.csv', 'r');
        $csv->setHeaderOffset(0);

        $header = $csv->getHeader(); //returns the CSV header record
        foreach ($records = $csv->getRecords() as $row) {
            $key = hash('xxh3', $row['en']);
            $article = new Article($key);

            $article->setDefaultLocale('en');
            $article->setAuthor('Amazon');
            foreach ($header as $locale) {
                $article->translate($locale)->setTitle($row[$locale]);
            }
            $manager->persist($article);
            $article->mergeNewTranslations();
        }
        $manager->flush();
    }

    private function oldWay() {

//returns all the records as
//        $article->setCurrentLocale('en');

        $defaultLocale = 'en';
        $articles = [
            [
                "title" => [
                    'en' => 'Hello',
                    'fr' => 'Bonjour',
                    'es' => 'Hola',
                ],
                "body" => [
                    'en' => 'Hello Body',
                    'fr' => 'Bonjour Corps',
                    'es' => 'Hola Cuerpo',
                ],
                "slug" => [
                    'en' => 'hello_slug',
                    'fr' => 'bonjour_slug',
                    'es' => 'hola_slug',
                ],
            ],
            [
                "title" => [
                    'en' => 'Bye',
                    'fr' => 'Au revoir',
                    'es' => 'Adios',
                ],
                "body" => [
                    'en' => 'Bye Body',
                    'fr' => 'Au revoir Corps',
                    'es' => 'Adios Cuerpo',
                ],
                "slug" => [
                    'en' => 'bye_slug',
                    'fr' => 'au_revoir_slug',
                    'es' => 'adios_slug',
                ],
            ],
            [
                "title" => [
                    'en' => 'Bye2',
                    'fr' => 'Au revoir2',
                    'es' => 'Adios2',
                ],
                "body" => [
                    'en' => 'Bye Body2',
                    'fr' => 'Au revoir Corps2',
                    'es' => 'Adios Cuerpo2',
                ],
                "slug" => [
                    'en' => 'bye_slug2',
                    'fr' => 'au_revoir_slug2',
                    'es' => 'adios_slug2',
                ],
            ],
        ];

        foreach($articles as $articleData) {
            $article = new Article();
            $article->setAuthor('bob');
            $article->setDefaultLocale($defaultLocale);

            foreach ($articleData as $field => $translations) {
                foreach ($translations as $locale => $translation) {
                    assert(is_string($translation), json_encode($translation, JSON_PRETTY_PRINT));
                    $article->translate($locale)->{"set$field"}($translation);
                }
            }
            $manager->persist($article);
            $article->mergeNewTranslations();
        }

        // foreach($articles as $field => $translations) {
        //     $article = new Article();
        //     $article->setAuthor('bob');
        //     $article->setDefaultLocale($defaultLocale);

        //     foreach ($translations as $locale => $translation) {
        //         assert(is_string($translation), json_encode($translation, JSON_PRETTY_PRINT));
        //         $article->translate($locale)->{"set$field"}($translation);
        //     }
        //     $manager->persist($article);
        //     $article->mergeNewTranslations();
        // }

        // foreach (
        //     [
        //              'hello' => [
        //                  'es' => 'Hola',
        //                  'fr' => 'Bonjour',
        //              ],
        //              'bye' => [
        //                  'es' => 'adios',
        //                  'fr' => 'au revoir'
        //              ]
        //          ] as $en => $others) {
        //     $article = new Article();
        //     $article->setAuthor('bob');
        //     $article->setDefaultLocale('en');

        //     $article->translate('en')->setTitle($en);
        //     foreach ($others as $locale => $translation) {
        //         assert(is_string($translation), json_encode($translation, JSON_PRETTY_PRINT));
        //         $article->translate($locale)->setTitle($translation);
        //     }
        //     $manager->persist($article);
        //     $article->mergeNewTranslations();
        // }

//        $article->translate('en')->setTitle('Hello!');
//        $article->translate('fr')->setTitle('Bonjour!');
//        $article->translate('es')->setTitle('Hola!');
//        $manager->persist($article);
        // $product = new Product();
        // $manager->persist($product);

// In order to persist new translations, call mergeNewTranslations method, before flush
        $manager->flush();
    }
}
