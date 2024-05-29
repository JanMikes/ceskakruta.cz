<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\Message\ChooseDelivery;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\DeliveryService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChooseDeliveryHandler
{
    public function __construct(
        private CartStorage $cartStorage,
        private DeliveryService $deliveryService,
    ) {
    }

    /**
     * @throws UnsupportedDeliveryToPostalCode
     */
    public function __invoke(ChooseDelivery $message): void
    {
        $this->cartStorage->storePickupPlace(null);
        $this->cartStorage->storeDeliveryAddress($message->address);

        $postalCode = $message->address->postalCode;

        if ($this->deliveryService->canCeskaKrutaDeliverToPostalCode($postalCode)) {
            $this->cartStorage->storeDeliveryPlace($this->deliveryService::CESKA_KRUTA_DELIVERY_PLACE_ID);
            return;
        }

        if ($this->deliveryService->canCoolbalikDeliverToPostalCode($postalCode)) {
            $this->cartStorage->storeDeliveryPlace($this->deliveryService::COOLBALIK_DELIVERY_PLACE_ID);
            return;
        }

        throw new UnsupportedDeliveryToPostalCode();
    }
}
