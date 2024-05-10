<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
    ) {
    }

    #[Route(path: '/nakupni-kosik', name: 'cart', methods: ['GET'])]
    #[Route(path: '/odebrat-z-kosiku/{cartItem}', name: 'remove_from_cart', methods: ['GET'])]
    public function __invoke(null|int $cartItem): Response
    {
        if ($cartItem !== null) {
            $this->cartStorage->removeItem($cartItem);

            $this->addFlash('success', 'Odstraněno z košíku');

            return $this->redirectToRoute('cart');
        }

        return $this->render('cart.html.twig');
    }
}
