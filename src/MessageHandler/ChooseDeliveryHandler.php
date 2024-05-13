<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnsupportedDeliveryToPostalCode;
use CeskaKruta\Web\Message\ChooseDelivery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ChooseDeliveryHandler
{
    /**
     * @throws UnsupportedDeliveryToPostalCode
     */
    public function __invoke(ChooseDelivery $message): void
    {
        throw new UnsupportedDeliveryToPostalCode();
    }
}
