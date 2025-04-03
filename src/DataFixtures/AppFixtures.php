<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\ArticleTranslation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
//        $article->setCurrentLocale('en');

        foreach (
            [
                     'hello' => [
                         'es' => 'Hola',
                         'fr' => 'Bonjour',
                     ],
                     'bye' => [
                         'es' => 'adios',
                         'fr' => 'au revoir'
                     ]
                 ] as $en => $others) {
            $article = new Article();
            $article->setAuthor('bob');
            $article->setDefaultLocale('en');

            $article->translate('en')->setTitle($en);
            foreach ($others as $locale => $translation) {
                assert(is_string($translation), json_encode($translation, JSON_PRETTY_PRINT));
                $article->translate($locale)->setTitle($translation);
            }
            $manager->persist($article);
            $article->mergeNewTranslations();
        }

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
