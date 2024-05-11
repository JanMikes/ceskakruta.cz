<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\CreateOrder;
use CeskaKruta\Web\Query\GetPlaces;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class FinishOrderController extends AbstractController
{
    public function __construct(
        private readonly CartStorage $cartStorage,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/objednat', name: 'finish_order', methods: ['GET'])]
    public function __invoke(): Response
    {
        if ($this->cartStorage->getPickupPlace() === null) {
            return $this->redirectToRoute('cart');
        }

        if ($this->cartStorage->getDate() === null) {
            return $this->redirectToRoute('cart');
        }

        if ($this->cartStorage->getOrderData() === null) {
            return $this->redirectToRoute('cart');
        }

        if ($this->cartStorage->itemsCount() === 0) {
            return $this->redirectToRoute('cart');
        }

        $this->bus->dispatch(
            new CreateOrder(), // TODO: pass arguments here
        );

        return $this->redirectToRoute('thank_you_for_order');
    }
}
