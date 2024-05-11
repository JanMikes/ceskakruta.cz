<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\FormType\OrderFormType;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
    ) {
    }

    #[Route(path: '/nakupni-kosik', name: 'cart', methods: ['GET', 'POST'])]
    #[Route(path: '/odebrat-z-kosiku/{cartItem}', name: 'remove_from_cart', methods: ['GET'])]
    public function __invoke(Request $request, null|int $cartItem): Response
    {
        if ($cartItem !== null) {
            $this->cartStorage->removeItem($cartItem);

            $this->addFlash('success', 'Odstraněno z košíku');

            return $this->redirectToRoute('cart');
        }

        $data = $this->cartStorage->getOrderData() ?? new OrderFormData();

        $orderForm = $this->createForm(OrderFormType::class, $data);
        $orderForm->handleRequest($request);

        if ($orderForm->isSubmitted() && $orderForm->isValid()) {
            $this->cartStorage->storeOrderData($data);

            if ($this->cartStorage->itemsCount() > 0) {
                return $this->redirectToRoute('order_recapitulation');
            }

            // TODO: what if place or date is empty

            return $this->redirectToRoute('cart');
        }

        return $this->render('cart.html.twig', [
            'orderForm' => $orderForm,
        ]);
    }
}
