<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\CancelRecurringOrder;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CancelRecurringOrderHandler
{
    public function __construct(
        private RecurringOrderRepository $recurringOrderRepository,
    ) {
    }

    public function __invoke(CancelRecurringOrder $message): void
    {
        $ordersByDay = $this->recurringOrderRepository->getForUserByDay($message->userId);
        $order = $ordersByDay[$message->dayOfWeek] ?? null;

        if ($order !== null) {
            $this->recurringOrderRepository->remove($order);
        }
    }
}
