<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Translation\TranslatorBagInterface;

final class AppController extends AbstractController
{

    private TranslatorBagInterface $translatorBag;

    public function __construct(private TranslatorInterface $translator)
    {
        // We need TranslatorBagInterface to get the catalogue
//        $this->translator = $translator instanceof TranslatorBagInterface ? $translator : null;
    }

    #[Route('/', name: 'app_landing')]
    public function landing(Request $request): Response
    {
        return $this->redirectToRoute('admin', ['_locale' => $request->getLocale()]);
    }

    #[Route('/{_locale}', name: 'app_homepage')]
    public function index(ArticleRepository $articleRepository): Response
    {
        return $this->render('app/index.html.twig', [
            'controller_name' => 'AppController',
            'articles' => $articleRepository->findBy([], [], 3),
        ]);
    }



    #[Route('/list', name: 'app_list')]
    public function getAllTranslations(): array
    {
        if (!$this->translator) {
            throw new \LogicException('Translator does not implement TranslatorBagInterface.');
        }

        $locale = 'en';
        $catalogue = $this->translator->getCatalogue($locale);

        $allMessages = [];

        foreach ($catalogue->getDomains() as $domain) {
            $allMessages[$domain] = $catalogue->all($domain);
        }
        dd($allMessages);

        return $allMessages;
    }
}
