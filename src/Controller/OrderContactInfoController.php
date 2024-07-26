<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\FormType\OrderFormType;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class OrderContactInfoController extends AbstractController
{
    public function __construct(
        readonly private CartStorage $cartStorage,
        readonly private CartService $cartService,
    ) {
    }

    #[Route(path: '/objednavka/kontaktni-udaje', name: 'order_contact_info', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, null|int $cartItem, #[CurrentUser] null|User $user): Response
    {
        $orderData = $this->cartStorage->getOrderData();

        if ($orderData === null) {
            $orderData = new OrderFormData();

            if ($user !== null) {
                $orderData->email = $user->email;
                $orderData->name = $user->name ?? '';
                $orderData->phone = $user->phone ?? '';
            }
        }

        $orderForm = $this->createForm(OrderFormType::class, $orderData);
        $orderForm->handleRequest($request);

        if ($orderForm->isSubmitted() && $orderForm->isValid()) {
            $this->cartStorage->storeOrderData($orderData);

            if ($this->cartService->isOrderReadyToBePlaced()) {
                return $this->redirectToRoute('order_recapitulation');
            } else {
                return $this->redirectToRoute('order_contact_info');
            }
        }

        return $this->render('order_contact_info.html.twig', [
            'orderForm' => $orderForm,
        ]);
    }
}
