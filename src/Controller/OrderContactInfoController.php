<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\ChangeCartItemQuantityFormData;
use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\FormType\ChangeCartItemQuantityFormType;
use CeskaKruta\Web\FormType\OrderFormType;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class OrderContactInfoController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
        private readonly GetColdProductsCalendar $getColdProductsCalendar,
        private readonly GetPlaces $getPlaces,
        private readonly CartService $cartService,
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

            return $this->redirectToRoute('order_recapitulation');
        }

        return $this->render('order_contact_info.html.twig', [
            'orderForm' => $orderForm,
        ]);
    }
}
