<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Query\GetOrders;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MyOrdersController extends AbstractController
{
    public function __construct(
        readonly private GetOrders $getOrders,
        readonly private GetPlaces $getPlaces,
    ) {
    }

    #[Route(path: '/uzivatel/objednavky', name: 'user_my_orders', methods: ['GET'])]
    public function __invoke(#[CurrentUser] User $loggedUser): Response
    {
        return $this->render('user_my_orders.html.twig', [
            'orders' => $this->getOrders->ofUser($loggedUser->id),
            'places' => $this->getPlaces->all(),
        ]);
    }
}
