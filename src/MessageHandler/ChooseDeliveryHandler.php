<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\Message\ChooseDelivery;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\CeskaKrutaDelivery;
use CeskaKruta\Web\Services\CoolBalikDelivery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChooseDeliveryHandler
{
    public function __construct(
        private CartStorage $cartStorage,
        private CeskaKrutaDelivery $ceskaKrutaDelivery,
        private CoolBalikDelivery $coolBalikDelivery,
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

        if ($this->ceskaKrutaDelivery->canDeliverToPostalCode($postalCode)) {
            $this->cartStorage->storeDeliveryPlace($this->ceskaKrutaDelivery::DELIVERY_PLACE_ID);
            return;
        }

        if ($this->coolBalikDelivery->canDeliverToPostalCode($postalCode)) {
            $this->cartStorage->storeDeliveryPlace($this->coolBalikDelivery::DELIVERY_PLACE_ID);
            return;
        }

        throw new UnsupportedDeliveryToPostalCode();
    }
}
