<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Doc;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard('/ez')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator                             $adminUrlGenerator,
        #[Autowire('%kernel.enabled_locales%')] private array $enabledLocales,
    )
    {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->redirect($this->adminUrlGenerator->setController(ArticleCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->renderSidebarMinimized()
            ->setTitle('TranslationFormDemo')->setLocales($this->enabledLocales)
            ;
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToCrud('articles', 'fas fa-pen', Article::class);
        yield MenuItem::linkToCrud('docs', 'fas fa-pencil', Doc::class);
    }

}
