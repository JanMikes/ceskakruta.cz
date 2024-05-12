<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class ChooseDelivery
{
    public function __construct(
        public string $street,
        public string $city,
        public string $postalCode
    ) {
    }
}
