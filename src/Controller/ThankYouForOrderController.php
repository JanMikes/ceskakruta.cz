<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetOrders;
use CeskaKruta\Web\Query\GetPlaceClosedDays;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ThankYouForOrderController extends AbstractController
{
    public function __construct(
        private readonly CartStorage  $cartStorage,
        private readonly GetOrders $getOrders,
        private readonly GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/dekujeme-za-objednavku', name: 'thank_you_for_order', methods: ['GET'])]
    public function __invoke(): Response
    {
        $lastOrderId = $this->cartStorage->getLastOrderId();
        $order = null;

        if ($lastOrderId !== null) {
            $order = $this->getOrders->oneById($lastOrderId);
        }

        // $this->cartStorage->storeLastOrderId(null);

        return $this->render('thank_you_for_order.html.twig', [
            'last_order' => $order,
            'places' => $this->getPlaces->all(),
        ]);
    }
}
