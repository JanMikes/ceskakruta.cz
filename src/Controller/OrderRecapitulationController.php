<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetColdProductsCalendar;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
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
    ) {
    }

    #[Route(path: '/rekapitulace-objednavky', name: 'order_recapitulation', methods: ['GET'])]
    public function __invoke(): Response
    {
        if ($this->cartService->isOrderReadyToBePlaced() === false) {
            return $this->redirectToRoute('cart');
        }

        $calendar = $this->getColdProductsCalendar->all();

        return $this->render('order_recapitulation.html.twig', [
            'places' => $this->getPlaces->all(),
            'calendar' => $calendar,
        ]);
    }
}
