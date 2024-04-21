<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductsController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    #[Route(path: '/nase-nabidka', name: 'products', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('products.html.twig', [
            // 'products' => $this->productRepository->findAll(),
        ]);
    }
}
