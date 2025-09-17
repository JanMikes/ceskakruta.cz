<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\ChooseDelivery;
use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Message\RepeatOrder;
use CeskaKruta\Web\Query\GetOrders;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\CartItem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
readonly final class RepeatOrderHandler
{
    public function __construct(
        private GetOrders $getOrders,
        private CartStorage $cartStorage,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(RepeatOrder $message): void
    {
        // Clear existing cart items
        $this->cartStorage->clearItems();

        // Get order and its items
        $order = $this->getOrders->oneForUserById($message->userId, $message->orderId);
        $orderItems = $this->getOrders->getOrderItems($message->orderId);

        // Add all order items to cart
        foreach ($orderItems as $orderItem) {
            $cartItem = new CartItem(
                productId: $orderItem->productId,
                quantity: $orderItem->amount,
                slice: $orderItem->isSliced ? true : null,
                pack: $orderItem->isPacked ? true : null,
                note: $orderItem->note,
            );

            $this->cartStorage->addItem($cartItem);
        }

        // Handle delivery/pickup details
        if ($order->deliveryStreet !== null && $order->deliveryCity !== null && $order->deliveryPostalCode !== null) {
            // This was a delivery order
            $address = new Address(
                street: $order->deliveryStreet,
                city: $order->deliveryCity,
                postalCode: $order->deliveryPostalCode,
            );

            $this->bus->dispatch(new ChooseDelivery($address));
        } else {
            // This was a pickup order
            $this->bus->dispatch(new ChoosePickupPlace($order->placeId));
        }
    }
}