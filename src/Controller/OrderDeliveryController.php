<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\Place;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderDeliveryController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
        readonly private GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/objednavka/zpusob-doruceni', name: 'order_delivery')]
    #[Route(path: '/objednavka/vybrat-misto-odberu/{placeId}', name: 'choose_order_pickup_place')]
    public function __invoke(Request $request, null|int $placeId = null): Response
    {
        if ($placeId !== null) {
            $this->bus->dispatch(
                new ChoosePickupPlace($placeId),
            );

            return $this->redirectToRoute('order_available_dates');
        }

        $pickupPlaces = array_filter(
            $this->getPlaces->all(),
            static fn (Place $place): bool => $place->isDelivery === false,
        );

        return $this->render('order_delivery.html.twig', [
            'places' => $pickupPlaces,
        ]);
    }
}
