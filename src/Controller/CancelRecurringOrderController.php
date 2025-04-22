<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\CancelRecurringOrder;
use CeskaKruta\Web\Message\SaveRecurringOrder;
use CeskaKruta\Web\Query\GetProducts;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Value\Product;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class CancelRecurringOrderController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    )
    {
    }

    #[Route(path: '/uzivatel/zrusit-pravidelnou-objednavku/{day}', name: 'user_cancel_recurring_order')]
    public function __invoke(#[CurrentUser] User $loggedUser, int $day): Response
    {
        $this->bus->dispatch(
            new CancelRecurringOrder(userId: $loggedUser->id, dayOfWeek: $day),
        );

        $this->addFlash('success', 'Pravidelné objednávání na tento del bylo zrušeno.');

        return $this->redirectToRoute('user_recurring_order', ['day' => $day]);
    }
}
