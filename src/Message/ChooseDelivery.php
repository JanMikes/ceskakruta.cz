<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

use CeskaKruta\Web\Value\Address;

readonly final class ChooseDelivery
{
    public function __construct(
        public Address $address,
    ) {
    }
}
