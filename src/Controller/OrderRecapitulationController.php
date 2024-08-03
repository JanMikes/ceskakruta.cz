<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\CouponExpired;
use CeskaKruta\Web\Exceptions\CouponNotFound;
use CeskaKruta\Web\Exceptions\CouponOrderDateExceeded;
use CeskaKruta\Web\Exceptions\CouponUsageLimitReached;
use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\CouponChecker;
use CeskaKruta\Web\Services\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderRecapitulationController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly GetPlaces $getPlaces,
        private readonly GetColdProductsCalendar $getColdProductsCalendar,
        private readonly CouponChecker $couponChecker,
    ) {
    }

    #[Route(path: '/objednavka/rekapitulace', name: 'order_recapitulation')]
    public function __invoke(): Response
    {
        if ($this->cartService->isOrderReadyToBePlaced() === false) {
            return $this->redirectToRoute('cart');
        }

        $coupon = $this->cartService->getCoupon();
        if ($coupon !== null) {
            try {
                $this->couponChecker->check($coupon);
            } catch (CouponExpired) {
                $this->addFlash('danger', 'Je nám líto, ale tento slevový kód již neplatí.');
                return $this->redirectToRoute('cart');
            } catch (CouponOrderDateExceeded $exception) {
                $this->addFlash('warning', sprintf('Je nám líto, ale tento slevový kód lze použít pouze na objednávky do %s. Upravte si prosím datum, na který objednáváte, abyste mohli kód použít.', $exception->coupon->deliveryUntilDate?->format('d.m.Y')));
                return $this->redirectToRoute('cart');
            } catch (CouponUsageLimitReached) {
                $this->addFlash('danger', 'Je nám líto, ale tento slevový kód byl již použit/vyčerpán.');
                return $this->redirectToRoute('cart');
            }
        }

        $calendar = $this->getColdProductsCalendar->all();

        return $this->render('order_recapitulation.html.twig', [
            'places' => $this->getPlaces->all(),
            'calendar' => $calendar,
        ]);
    }
}
