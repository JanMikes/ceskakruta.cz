<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\ChooseOrderDate;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChooseOrderDateHandler
{
    public function __construct(
        private CartStorage $cartStorage,
        private GetAvailableDays $getAvailableDays,
    ) {
    }

    public function __invoke(ChooseOrderDate $message): void
    {
        if ($this->getAvailableDays->isDateAvailable($message->date, $message->placeId) === false) {
            // TODO: own exception
            throw new \Exception('unavailable day .. todo');
        }

        $this->cartStorage->storeDate($message->date);
    }
}
