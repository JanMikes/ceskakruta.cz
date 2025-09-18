<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Entity\RecurringOrder;
use CeskaKruta\Web\Entity\RecurringOrderItem;
use CeskaKruta\Web\Message\SaveRecurringOrder;
use CeskaKruta\Web\Repository\RecurringOrderRepository;
use CeskaKruta\Web\Value\PackageAmount;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class SaveRecurringOrderHandler
{
    public function __construct(
        private RecurringOrderRepository $recurringOrderRepository,
    ) {
    }

    public function __invoke(SaveRecurringOrder $message): void
    {
        // Load existing order for this user/day or create new one
        $orders = $this->recurringOrderRepository->getForUserByDay($message->userId);
        $order = $orders[$message->dayOfWeek] ?? new RecurringOrder(
            Uuid::uuid7(),
            $message->userId,
            $message->dayOfWeek,
        );

        // Build a map of existing items by productId
        $existingItems = [];
        foreach ($order->items as $item) {
            $existingItems[$item->productId] = $item;
        }

        // Process incoming items
        foreach ($message->items as $productId => $itemData) {
            $other = 0.0;
            $note = null;
            $isSliced = false;
            $isPacked = false;

            if (isset($itemData['amount']['other'])) {
                $other = (float) $itemData['amount']['other'];
                unset($itemData['amount']['other']);
            }

            if (isset($itemData['note'])) {
                $note = $itemData['note'];
            }

            if (isset($itemData['is_sliced'])) {
                $isSliced = (bool) $itemData['is_sliced'];
            }

            if (isset($itemData['is_packed'])) {
                $isPacked = (bool) $itemData['is_packed'];
            }

            $packages = [];

            foreach ($itemData['amount'] as $sizeKey => $amount) {
                $amount = (int) $amount;

                if ($amount > 0) {
                    $packages[] = new PackageAmount(sizeG: (int) $sizeKey, amount: $amount);
                }
            }

            if (count($packages) === 0 && $other === 0.0) {
                continue;
            }

            if (isset($existingItems[$productId])) {
                // Update existing item
                $item = $existingItems[$productId];
                $item->change(
                    packages: $packages,
                    otherPackageSizeAmount: $other,
                    note: $note,
                    isSliced: $isSliced,
                    isPacked: $isPacked,
                );
                unset($existingItems[$productId]);
                continue;
            }

            // Create and add new item
            new RecurringOrderItem(
                Uuid::uuid7(),
                $order,
                (int) $productId,
                $packages,
                otherPackageSizeAmount: $other,
                isSliced: $isSliced,
                isPacked: $isPacked,
                note: $note,
            );
        }

        // Remove any items the user has cleared out
        foreach ($existingItems as $itemToRemove) {
            $order->removeItem($itemToRemove);
        }

        if (count($order->items) === 0) {
            $this->recurringOrderRepository->remove($order);
            return;
        }

        $this->recurringOrderRepository->save($order);
    }
}
