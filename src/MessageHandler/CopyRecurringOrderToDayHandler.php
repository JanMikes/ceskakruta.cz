<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\CopyRecurringOrderToDay;
use CeskaKruta\Web\Message\SaveRecurringOrder;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly final class CopyRecurringOrderToDayHandler
{
    public function __construct(
        private RecurringOrderRepository $recurringOrderRepository,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(CopyRecurringOrderToDay $message): void
    {
        $orders = $this->recurringOrderRepository->getForUserByDay($message->userId);

        // Get the source order
        $sourceOrder = $orders[$message->sourceDay] ?? null;

        if ($sourceOrder === null) {
            return; // No order to copy
        }

        // Convert items to the format expected by SaveRecurringOrder
        /** @var array<int, array{amount: array<string, string>, note: null|string, is_sliced?: bool}> $items */
        $items = [];
        foreach ($sourceOrder->items as $item) {
            // Build package amounts with explicit string keys
            $packageAmounts = [];
            foreach ($item->packages as $package) {
                $packageAmounts[(string) ((int) $package->sizeG)] = (string) $package->amount;
            }

            /** @var array<string, string> $amount */
            $amount = ['other' => (string) $item->otherPackageSizeAmount] + $packageAmounts;

            $items[$item->productId] = [
                'amount' => $amount,
                'note' => $item->note,
                'is_sliced' => $item->isSliced,
            ];
        }

        // Dispatch SaveRecurringOrder for the target day
        $this->bus->dispatch(
            new SaveRecurringOrder(
                $message->userId,
                $message->targetDay,
                $items,
            ),
        );
    }
}