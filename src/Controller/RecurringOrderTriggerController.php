<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Controller;

use CeskaKruta\Web\Message\CreateOrderFromRecurringOrder;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Repository\RecurringOrderSkipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RecurringOrderTriggerController extends AbstractController
{
    public function __construct(
        readonly private MessageBusInterface $bus,
        readonly private RecurringOrderRepository $recurringOrderRepository,
        readonly private RecurringOrderSkipRepository $recurringOrderSkipRepository,
    ) {
    }

    #[Route(path: '/cron/recurring-order-trigger', name: 'cron_recurring_order_trigger', methods: ['GET'])]
    public function __invoke(): Response
    {
        $orders = $this->recurringOrderRepository->getScheduledForOrdering();
        $activeSkips = $this->recurringOrderSkipRepository->getActiveSkipsByUserAndDay();
        $orderIds = [];

        foreach ($orders as $order) {
            $skipKey = $order->userId . '_' . $order->dayOfWeek;

            if (isset($activeSkips[$skipKey])) {
                continue;
            }

            $this->bus->dispatch(
                new CreateOrderFromRecurringOrder($order->id),
            );

            $orderIds[] = $order->id->toString();
        }

        return $this->json(['orders' => $orderIds]);
    }
}
