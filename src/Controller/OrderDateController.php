<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\ChooseOrderDate;
use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderDateController extends AbstractController
{
    public function __construct(
        readonly private CartStorage $cartStorage,
        readonly private GetAvailableDays $getAvailableDays,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/datum-objednavky', name: 'order_available_dates', methods: ['GET'])]
    #[Route(path: '/vybrat-datum-objednavky/{date}', name: 'choose_date', methods: ['GET'])]
    public function __invoke(null|string $date): Response
    {
        $placeId = $this->cartStorage->getPickupPlace();

        if ($placeId === null) {
            $this->addFlash('warning', 'Pro možnost výběru termínu, prosím zvolte první způsob doručení - osobní odběr nebo rozvoz.');

            return $this->redirectToRoute('order_available_dates');
        }

        if ($date !== null) {
            $chosenDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date) ?: null;

            if ($chosenDate !== null) {
                $this->bus->dispatch(
                    new ChooseOrderDate($placeId, $chosenDate),
                );

                $this->addFlash('success', 'Zvolen datum');

                return $this->redirectToRoute('order_available_dates');
            }
        }

        // TODO: for delivery address -> maybe placeId unified in the future

        return $this->render('order_available_dates.html.twig', [
            'available_days' => $this->getAvailableDays->forPlace($placeId),
        ]);
    }
}