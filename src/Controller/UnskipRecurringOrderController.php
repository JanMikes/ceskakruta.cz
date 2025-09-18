<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\UnskipRecurringOrder;
use CeskaKruta\Web\Value\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class UnskipRecurringOrderController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/uzivatel/zrusit-preskoceni-pravidelne-objednavky/{day}', name: 'user_unskip_recurring_order')]
    public function __invoke(#[CurrentUser] User $loggedUser, int $day): Response
    {
        $this->bus->dispatch(
            new UnskipRecurringOrder(userId: $loggedUser->id, dayOfWeek: $day),
        );

        $this->addFlash('success', 'Přeskočení pravidelné objednávky na tento den bylo zrušeno.');

        return $this->redirectToRoute('user_recurring_order', ['day' => $day]);
    }
}