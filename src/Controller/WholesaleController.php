<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class WholesaleController extends AbstractController
{
    public function __construct(
        readonly private GetProducts $getProducts,
    ) {
    }

    #[Route(path: '/uzivatel/velkoobchod', name: 'user_wholesale')]
    public function __invoke(#[CurrentUser] User $loggedUser): Response
    {
        $products = $this->getProducts->all();

        return $this->render('user_wholesale.html.twig', [
            'products' => $products,
        ]);
    }
}
