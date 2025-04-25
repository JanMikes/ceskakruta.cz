<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class DetectUserDeliveryPlace
{
    public function __construct(
        public int $userId,
    ) {
    }
}
