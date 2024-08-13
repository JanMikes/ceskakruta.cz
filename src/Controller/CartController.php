<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\CouponExpired;
use CeskaKruta\Web\Exceptions\CouponNotFound;
use CeskaKruta\Web\Exceptions\CouponOrderDateExceeded;
use CeskaKruta\Web\Exceptions\CouponUsageLimitReached;
use CeskaKruta\Web\FormData\ChangeCartItemFormData;
use CeskaKruta\Web\FormData\CouponFormData;
use CeskaKruta\Web\FormType\ChangeCartItemFormType;
use CeskaKruta\Web\FormType\CouponFormType;
use CeskaKruta\Web\Message\UseCoupon;
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
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CartController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
        private readonly GetColdProductsCalendar $getColdProductsCalendar,
        private readonly GetPlaces $getPlaces,
        private readonly CartService $cartService,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/nakupni-kosik', name: 'cart')]
    #[Route(path: '/odebrat-z-kosiku/{cartItem}', name: 'remove_from_cart')]
    #[Route(path: '/prepocitat-kosik', name: 'change_cart_item_quantity')]
    public function __invoke(Request $request, null|int $cartItem, #[CurrentUser] null|User $user): Response
    {
        /** @var string $routeName */
        $routeName = $request->attributes->get('_route');

        if ($routeName === 'change_cart_item_quantity' && $request->isMethod(Request::METHOD_POST) === false) {
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
            $changeItemData = new ChangeCartItemFormData();
            $changeItemData->cartKey = $key;
            $changeItemData->quantity = $item->quantity;
            $changeItemData->note = $item->note;

            $changeQuantityForm = $this->createForm(ChangeCartItemFormType::class, $changeItemData, [
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
                $changeItemData = $requestForm->getData();
                assert($changeItemData instanceof ChangeCartItemFormData);

                $this->cartStorage->changeItem($changeItemData->cartKey, $changeItemData->quantity, $changeItemData->note);

                $this->addFlash('success', 'Košík přepočítán');

                return $this->redirectToRoute('cart');
            }
        }

        $coupon = $this->cartService->getCoupon();

        $couponFormData = new CouponFormData();
        $couponFormData->code = $coupon->code ?? '';

        $couponForm = $this->createForm(CouponFormType::class, $couponFormData);
        $couponForm->handleRequest($request);

        if ($couponForm->isSubmitted() && $couponForm->isValid()) {
            try {
                $this->bus->dispatch(
                    new UseCoupon($couponFormData->code),
                );
            } catch (HandlerFailedException $handlerFailedException) {
                $previousException = $handlerFailedException->getPrevious();

                if ($previousException instanceof CouponNotFound) {
                    $this->addFlash('danger', 'Je nám líto, ale takový slevový kód u nás nemůžeme najít. Raději prosím pečlivě zkontrolujte, jestli je kód správný.');
                } elseif ($previousException instanceof CouponExpired) {
                    $this->addFlash('danger', 'Je nám líto, ale tento slevový kód již neplatí.');
                } elseif ($previousException instanceof CouponOrderDateExceeded) {
                    $this->addFlash('warning', sprintf('Je nám líto, ale tento slevový kód lze použít pouze na objednávky do %s. Upravte si prosím datum, na který objednáváte, abyste mohli kód použít.', $previousException->coupon->deliveryUntilDate?->format('d.m.Y')));
                } elseif ($previousException instanceof CouponUsageLimitReached) {
                    $this->addFlash('danger', 'Je nám líto, ale tento slevový kód byl již použit/vyčerpán.');
                } else {
                    throw $handlerFailedException;
                }
            }

            return $this->redirectToRoute('cart');
        }

        $calendar = $this->getColdProductsCalendar->all();

        return $this->render('cart.html.twig', [
            'calendar' => $calendar,
            'places' => $this->getPlaces->all(),
            'change_quantity_forms' => $changeQuantityFormViews,
            'coupon_form' => $couponForm,
        ]);
    }
}
