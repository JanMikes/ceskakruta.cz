<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\SkipRecurringOrder;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class SkipRecurringOrderController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/uzivatel/preskocit-pravidelnou-objednavku/{day}', name: 'user_skip_recurring_order')]
    public function __invoke(#[CurrentUser] User $loggedUser, int $day): Response
    {
        $this->bus->dispatch(
            new SkipRecurringOrder(userId: $loggedUser->id, dayOfWeek: $day),
        );

        $this->addFlash('success', 'Příští pravidelná objednávka na tento den byla přeskočena.');

        return $this->redirectToRoute('user_recurring_order', ['day' => $day]);
    }
}