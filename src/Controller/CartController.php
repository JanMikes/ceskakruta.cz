<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\ChangeCartItemQuantityFormData;
use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\FormType\ChangeCartItemQuantityFormType;
use CeskaKruta\Web\FormType\OrderFormType;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
        private readonly GetColdProductsCalendar $getColdProductsCalendar,
        private readonly GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/nakupni-kosik', name: 'cart', methods: ['GET', 'POST'])]
    #[Route(path: '/odebrat-z-kosiku/{cartItem}', name: 'remove_from_cart', methods: ['GET'])]
    #[Route(path: '/prepocitat-kosik', name: 'change_cart_item_quantity', methods: ['POST'])]
    public function __invoke(Request $request, null|int $cartItem): Response
    {
        /** @var string $routeName */
        $routeName = $request->attributes->get('_route');

        if ($routeName === 'remove_from_cart' && $cartItem !== null) {
            $this->cartStorage->removeItem($cartItem);

            $this->addFlash('success', 'Odstraněno z košíku');

            return $this->redirectToRoute('cart');
        }

        $orderData = $this->cartStorage->getOrderData() ?? new OrderFormData();

        $orderForm = $this->createForm(OrderFormType::class, $orderData);
        $orderForm->handleRequest($request);

        if ($orderForm->isSubmitted() && $orderForm->isValid()) {
            $this->cartStorage->storeOrderData($orderData);

            if ($this->cartStorage->itemsCount() > 0) {
                return $this->redirectToRoute('order_recapitulation');
            }

            // TODO: what if place or date is empty

            return $this->redirectToRoute('cart');
        }

        /** @var array<Form> $changeQuantityForms */
        $changeQuantityForms = [];
        /** @var array<FormView> $changeQuantityFormViews */
        $changeQuantityFormViews = [];

        foreach ($this->cartStorage->getItems() as $key => $item) {
            $changeQuantityData = new ChangeCartItemQuantityFormData();
            $changeQuantityData->cartKey = $key;
            $changeQuantityData->quantity = $item->quantity;

            $changeQuantityForm = $this->createForm(ChangeCartItemQuantityFormType::class, $changeQuantityData, [
                'action' => $this->generateUrl('change_cart_item_quantity', ['cartItem' => $key]),
            ]);

            $changeQuantityForms[$key] = $changeQuantityForm;
            $changeQuantityFormViews[$key] = $changeQuantityForm->createView();
        }

        /** @var null|string $submittedItemKey */
        $submittedItemKey = $request->query->get('cartItem');

        if ($submittedItemKey !== null) {
            $requestForm = $changeQuantityForms[(int)$submittedItemKey];
            $requestForm->handleRequest($request);

            if ($requestForm->isSubmitted() && $requestForm->isValid()) {
                $changeQuantityData = $requestForm->getData();
                assert($changeQuantityData instanceof ChangeCartItemQuantityFormData);

                $this->cartStorage->changeQuantity($changeQuantityData->cartKey, $changeQuantityData->quantity);

                $this->addFlash('success', 'Košík přepočítán');

                return $this->redirectToRoute('cart');
            }
        }

        $calendar = $this->getColdProductsCalendar->all();

        return $this->render('cart.html.twig', [
            'orderForm' => $orderForm,
            'calendar' => $calendar,
            'places' => $this->getPlaces->all(),
            'change_quantity_forms' => $changeQuantityFormViews
        ]);
    }
}
