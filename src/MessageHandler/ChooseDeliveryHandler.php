<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\Message\ChooseDelivery;
use CeskaKruta\Web\Services\Cart\CartStorage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ChooseDeliveryHandler
{
    public function __construct(
        private CartStorage $cartStorage,
    ) {
    }

    /**
     * @throws UnsupportedDeliveryToPostalCode
     */
    public function __invoke(ChooseDelivery $message): void
    {
        throw new UnsupportedDeliveryToPostalCode();
    }
}
