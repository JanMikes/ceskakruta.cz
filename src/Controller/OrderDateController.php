<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Exceptions\CouponExpired;
use CeskaKruta\Web\Exceptions\CouponNotFound;
use CeskaKruta\Web\Exceptions\CouponOrderDateExceeded;
use CeskaKruta\Web\Exceptions\CouponUsageLimitReached;
use CeskaKruta\Web\Exceptions\UnavailableDate;
use CeskaKruta\Web\Message\ChooseOrderDate;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Services\Cart\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderDateController extends AbstractController
{
    public function __construct(
        readonly private CartService $cartService,
        readonly private GetAvailableDays $getAvailableDays,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/objednavka/datum-objednavky', name: 'order_available_dates')]
    #[Route(path: '/objednavka/vybrat-datum/{date}', name: 'choose_date')]
    public function __invoke(null|string $date, Request $request): Response
    {
        $place = $this->cartService->getPlace();

        if ($place === null) {
            $this->addFlash('warning', 'Pro možnost výběru termínu, prosím nejdříve zvolte způsob doručení.');

            return $this->redirectToRoute('order_delivery');
        }

        if ($date !== null) {
            $chosenDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;

            if ($chosenDate !== null) {
                try {
                    $this->bus->dispatch(
                        new ChooseOrderDate($place->id, $chosenDate),
                    );
                } catch (HandlerFailedException $handlerFailedException) {
                    $previousException = $handlerFailedException->getPrevious();

                    if ($previousException instanceof CouponOrderDateExceeded) {
                        $this->addFlash('warning', sprintf('Je nám líto, ale váš slevový kód lze použít pouze na objednávky do %s. Vyberte si prosím jiný datum, abyste mohli kód použít.', $previousException->coupon->deliveryUntilDate?->format('d.m.Y')));

                        return $this->redirectToRoute('order_available_dates');
                    } elseif ($previousException instanceof UnavailableDate) {
                        $this->addFlash('warning', 'Něco se pokazilo, vámi vybraný den již není k dispozici. Vyberte prosím jiný.');

                        return $this->redirectToRoute('order_available_dates');
                    } else {
                        throw $handlerFailedException;
                    }
                }

                return $this->redirectToRoute('order_contact_info');
            }
        }

        $availableDays = $this->getAvailableDays->forPlace($place->id);

        return $this->render('order_available_dates.html.twig', [
            'available_days' => $availableDays,
            'first_available_day' => $availableDays[array_key_first($availableDays)] ?? null,
        ]);
    }
}
