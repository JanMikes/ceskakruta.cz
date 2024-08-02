<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message;

readonly final class UseCoupon
{
    public function __construct(
        public null|string $code,
    ) {
    }
}
