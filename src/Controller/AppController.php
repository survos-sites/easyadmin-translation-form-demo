<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AppController extends AbstractController
{

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
}
