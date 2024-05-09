<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\Place;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class OrderPickupPlacesController extends AbstractController
{
    public function __construct(
        readonly private GetPlaces $getPlaces,
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/vybrat-misto-odberu/{placeId}', name: 'choose_order_pickup_place', methods: ['GET'])]
    #[Route(path: '/osobni-odber', name: 'order_pickup_places', methods: ['GET'])]
    public function __invoke(null|int $placeId = null): Response
    {
        if ($placeId !== null) {
            $this->bus->dispatch(
                new ChoosePickupPlace($placeId),
            );

            return $this->redirectToRoute('order_pickup_places');
        }

        $pickupPlaces = array_filter(
            $this->getPlaces->all(),
            static fn (Place $place): bool => $place->isDelivery === false,
        );

        return $this->render('order_pickup_places.html.twig', [
            'places' => $pickupPlaces,
        ]);
    }
}
