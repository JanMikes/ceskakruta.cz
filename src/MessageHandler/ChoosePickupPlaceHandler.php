<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Query\GetAvailableDays;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChoosePickupPlaceHandler
{
    public function __construct(
        private CartStorage $cartStorage,
        private GetAvailableDays $getAvailableDays,
    ) {}

    public function __invoke(ChoosePickupPlace $message): void
    {
        $this->cartStorage->storePickupPlace($message->placeId);
        $this->cartStorage->storeDeliveryPlace(null);

        // Reset date when not available while changing places
        $existingDate = $this->cartStorage->getDate();
        if ($existingDate && $this->getAvailableDays->isDateAvailable($existingDate, $message->placeId) === false) {
            $this->cartStorage->storeDate(null);
        }
    }
}
