<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmptyCartController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
    ) {
    }

    #[Route(path: '/vyprazdnit-kosik', name: 'empty_cart')]
    public function __invoke(): Response
    {
        $this->cartStorage->clearItems();

        return $this->redirectToRoute('cart');
    }
}
