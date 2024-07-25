<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\ChooseOrderDate;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Services\Cart\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route(path: '/datum-objednavky', name: 'order_available_dates', methods: ['GET'])]
    #[Route(path: '/vybrat-datum-objednavky/{date}', name: 'choose_date', methods: ['GET'])]
    public function __invoke(null|string $date, Request $request): Response
    {
        $place = $this->cartService->getPlace();

        if ($place === null) {
            $this->addFlash('warning', 'Pro možnost výběru termínu, prosím zvolte první způsob doručení - osobní odběr nebo rozvoz.');

            $referer = $request->headers->get('referer');

            if (!is_string($referer)) {
                return $this->redirectToRoute('order_pickup_places');
            }

            return $this->redirect($referer);
        }

        if ($date !== null) {
            $chosenDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;

            if ($chosenDate !== null) {
                $this->bus->dispatch(
                    new ChooseOrderDate($place->id, $chosenDate),
                );

                $this->addFlash('success', 'Zvolen datum');

                return $this->redirectToRoute('cart');
            }
        }

        $availableDays = $this->getAvailableDays->forPlace($place->id);

        return $this->render('order_available_dates.html.twig', [
            'available_days' => $availableDays,
            'first_available_day' => $availableDays[array_key_first($availableDays)] ?? null,
        ]);
    }
}
