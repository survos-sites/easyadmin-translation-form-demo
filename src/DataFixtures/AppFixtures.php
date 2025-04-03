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
        $article = new Article();
        $article->setAuthor('bob');
        $article->setCurrentLocale('en');
        $article->setDefaultLocale('en');
        $article->translate('en')->setTitle('Hello!');
        $article->translate('fr')->setTitle('Bonjour!');
        $article->translate('es')->setTitle('Hola!');
        $manager->persist($article);
        // $product = new Product();
        // $manager->persist($product);

// In order to persist new translations, call mergeNewTranslations method, before flush
        $article->mergeNewTranslations();
        $manager->flush();
    }
}
