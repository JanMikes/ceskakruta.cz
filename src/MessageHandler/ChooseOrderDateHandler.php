<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\ChooseOrderDate;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChooseOrderDateHandler
{
    public function __construct(
        private CartService $cartService,
        private CartStorage $cartStorage,
    ) {
    }

    public function __invoke(ChooseOrderDate $message): void
    {
        $date = $message->date;

        if ($this->cartService->isDateAvailable($date, $message->placeId) === false) {
            // TODO: own exception
            throw new \Exception('unavailable day .. todo');
        }

        $this->cartStorage->storeDate($date);
    }
}
