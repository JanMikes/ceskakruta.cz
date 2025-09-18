<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\Message\DetectUserDeliveryPlace;
use CeskaKruta\Web\Message\SaveRecurringOrder;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Repository\RecurringOrderSkipRepository;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CeskaKrutaShopDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use CeskaKruta\Web\Services\OrderingDeadline;
use CeskaKruta\Web\Value\Product;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class RecurringOrderController extends AbstractController
{
    public function __construct(
        readonly private GetProducts $getProducts,
        readonly private RecurringOrderRepository $recurringOrderRepository,
        readonly private RecurringOrderSkipRepository $recurringOrderSkipRepository,
        readonly private MessageBusInterface $bus,
        readonly private CeskaKrutaDelivery $ceskaKrutaDelivery,
        readonly private CeskaKrutaShopDelivery $ceskaKrutaShopDelivery,
        readonly private CoolBalikDelivery $coolBalikDelivery,
        readonly private OrderingDeadline $orderingDeadline,
    ) {
    }

    #[Route(path: '/uzivatel/pravidelne-objednavky', name: 'user_recurring_order')]
    public function __invoke(#[CurrentUser] User $loggedUser, Request $request): Response
    {
        $deliveryPlacesIds = [
            CeskaKrutaDelivery::DELIVERY_PLACE_ID,
            CeskaKrutaShopDelivery::DELIVERY_PLACE_ID,
            CoolBalikDelivery::DELIVERY_PLACE_ID,
        ];

        if ($loggedUser->hasFilledDeliveryAddress() === false) {
            $this->addFlash('warning', 'Prosím, vyplňte si doručovací adresu!');

            return $this->redirectToRoute('edit_user_info');
        }

        if (in_array($loggedUser->preferredPlaceId, $deliveryPlacesIds, true) === false) {
            try {
                $this->bus->dispatch(
                    new DetectUserDeliveryPlace($loggedUser->id),
                );

                return $this->redirectToRoute('user_recurring_order');
            } catch (HandlerFailedException $handlerFailedException) {
                $previousException = $handlerFailedException->getPrevious();

                if ($previousException instanceof UnsupportedDeliveryToPostalCode) {
                    $this->addFlash('warning', 'Na vámi vyplněnou doručovací adresu bohužel nerozvážíme, prosím, vyberte jinou adresu!');

                    return $this->redirectToRoute('edit_user_info');
                } else {
                    throw $handlerFailedException;
                }
            }
        }

        $products = $this->getProducts->all();
        $products = array_filter($products, function(Product $product): bool {
            return $product->isTurkey === false;
        });

        $ordersByDay = $this->recurringOrderRepository->getForUserByDay($loggedUser->id);
        $activeSkips = $this->recurringOrderSkipRepository->getActiveSkipsForUser($loggedUser->id);

        /** @var null|string $day */
        $day = $request->query->get('day');

        if ($day !== null && $request->isMethod('POST')) {
            /** @var array<int, array{amount: array<string, string>, note: null|string, is_sliced?: bool, is_packed?: bool}> $items */
            $items = $request->request->all('item');

            $this->bus->dispatch(
                new SaveRecurringOrder(
                    $loggedUser->id,
                    (int) $day,
                    $items,
                ),
            );

            $this->addFlash('success', 'Uložili jsme vaši pravidelnou objednávku.');

            return $this->redirectToRoute('user_recurring_order', ['day' => $day]);
        }

        $allowedDays = [];
        $placeId = $loggedUser->preferredPlaceId;

        $postalCode = $loggedUser->deliveryZip ?? '';
        if ($placeId === CeskaKrutaDelivery::DELIVERY_PLACE_ID) {
            $allowedDays = $this->ceskaKrutaDelivery->getAllowedDaysForPostalCode($postalCode);
        }

        if ($placeId === CeskaKrutaShopDelivery::DELIVERY_PLACE_ID) {
            $allowedDays = $this->ceskaKrutaShopDelivery->getAllowedDaysForPostalCode($postalCode);
        }

        if ($placeId === CoolBalikDelivery::DELIVERY_PLACE_ID) {
            $allowedDays = $this->coolBalikDelivery->getAllowedDaysForPostalCode($postalCode);
        }

        $nextDeadline = null;
        $nextOrderingDay = null;
        if ($day !== null) {
            $nextDeadline = $this->orderingDeadline->nextDeadline((int) $day, $placeId);
            $nextOrderingDay = $this->orderingDeadline->nextOrderDay((int) $day, $placeId);
        }

        return $this->render('user_recurring_order.html.twig', [
            'products' => $products,
            'day' => $day,
            'orders_by_day' => $ordersByDay,
            'active_skips' => $activeSkips,
            'allowed_days' => $allowedDays,
            'next_deadline' => $nextDeadline,
            'next_ordering_day' => $nextOrderingDay,
            'days_short' => [
                1 => 'Po',
                2 => 'Út',
                3 => 'St',
                4 => 'Čt',
                5 => 'Pá',
                6 => 'So',
                7 => 'Ne',
            ],
            'days' => [
                1 => 'Pondělí',
                2 => 'Úterý',
                3 => 'Středa',
                4 => 'Čtvrtek',
                5 => 'Pátek',
                6 => 'Sobota',
                7 => 'Neděle',
            ],
        ]);
    }
}
