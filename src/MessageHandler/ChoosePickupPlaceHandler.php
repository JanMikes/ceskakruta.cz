<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Message\ChoosePickupPlace;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChoosePickupPlaceHandler
{
    public function __construct(
       private CartStorage $cartStorage,
    ) {}

    public function __invoke(ChoosePickupPlace $message): void
    {
        $this->cartStorage->storePickupPlace($message->placeId);
    }
}
