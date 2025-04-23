<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\CreateOrderFromRecurringOrder;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RecurringOrderTriggerController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
    ) {
    }

    #[Route(path: '/cron/recurring-order-trigger', name: 'cron_recurring_order_trigger', methods: ['GET'])]
    public function __invoke(): Response
    {
        $this->bus->dispatch(
            new CreateOrderFromRecurringOrder(
                Uuid::fromString('01965f36-5358-7333-8cac-49586fee0260'),
            ),
        );

        return $this->json(['orders' => []]);
    }
}
