<?php
declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\CreateOrder;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class FinishOrderController extends AbstractController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/objednat', name: 'finish_order', methods: ['GET'])]
    public function __invoke(#[CurrentUser] null|User $user): Response
    {
        if ($this->cartService->isOrderReadyToBePlaced() === false) {
            return $this->redirectToRoute('cart');
        }

        $this->bus->dispatch(
            new CreateOrder($user?->id), // TODO: pass arguments here
        );

        return $this->redirectToRoute('thank_you_for_order');
    }
}
