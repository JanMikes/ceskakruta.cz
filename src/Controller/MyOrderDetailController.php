<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetOrders;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MyOrderDetailController extends AbstractController
{
    public function __construct(
        readonly private GetOrders $getOrders,
        readonly private GetProducts $getProducts,
        readonly private GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/uzivatel/objednavky/{orderId}', name: 'user_my_order_detail')]
    public function __invoke(#[CurrentUser] User $loggedUser, int $orderId): Response
    {
        $order = $this->getOrders->oneForUserById($loggedUser->id, $orderId);
        $items = $this->getOrders->getOrderItems($orderId);

        return $this->render('user_my_order_detail.html.twig', [
            'order' => $order,
            'order_items' => $items,
            'products' => $this->getProducts->all(),
            'places' => $this->getPlaces->all(),
        ]);
    }
}
