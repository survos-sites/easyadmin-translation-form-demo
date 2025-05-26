<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

class ProductController extends AbstractController
{
	public function __construct()
	{
	}


	#[Template('product/product_show.html.twig')]
	#[Route(path: '/product_show', name: 'product_show')]
    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
	public function product_show(Request $request): array|Response
	{
		return [];
	}
}
