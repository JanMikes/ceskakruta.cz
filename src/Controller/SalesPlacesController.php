<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\Place;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SalesPlacesController extends AbstractController
{
    public function __construct(
        readonly private GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/prodejni-mista', name: 'sales_places', methods: ['GET'])]
    public function __invoke(): Response
    {
        $places = array_filter(
            $this->getPlaces->all(),
            static fn (Place $place): bool => $place->isDelivery === false,
        );

        return $this->render('sales_places.html.twig', [
            'places' => $places,
        ]);
    }
}
