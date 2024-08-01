<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\FormData\ChangeCartItemQuantityFormData;
use CeskaKruta\Web\FormType\ChangeCartItemQuantityFormType;
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

final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
        private readonly GetColdProductsCalendar $getColdProductsCalendar,
        private readonly GetPlaces $getPlaces,
        private readonly CartService $cartService,
    ) {
    }

    #[Route(path: '/nakupni-kosik', name: 'cart')]
    #[Route(path: '/odebrat-z-kosiku/{cartItem}', name: 'remove_from_cart')]
    #[Route(path: '/prepocitat-kosik', name: 'change_cart_item_quantity')]
    public function __invoke(Request $request, null|int $cartItem, #[CurrentUser] null|User $user): Response
    {
        /** @var string $routeName */
        $routeName = $request->attributes->get('_route');

        if ($routeName === 'remove_from_cart' && $request->isMethod(Request::METHOD_POST) === false) {
            return $this->redirectToRoute('cart');
        }

        if ($routeName === 'remove_from_cart' && $cartItem !== null) {
            $this->cartService->removeItem($cartItem);

            $this->addFlash('success', 'Odstraněno z košíku');

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
            $requestForm = $changeQuantityForms[(int) $submittedItemKey];
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
            'calendar' => $calendar,
            'places' => $this->getPlaces->all(),
            'change_quantity_forms' => $changeQuantityFormViews
        ]);
    }
}
