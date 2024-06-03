<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\CreateOrder;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\OrderRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateOrderHandler
{
    public function __construct(
        private OrderRepository $orderService,
        private CartStorage $cartStorage,
    ) {
    }

    public function __invoke(CreateOrder $message): void
    {
        $orderId = $this->orderService->createOrder($message->userId);

        $this->cartStorage->storeLastOrderId($orderId);
        $this->cartStorage->clear();
    }
}
