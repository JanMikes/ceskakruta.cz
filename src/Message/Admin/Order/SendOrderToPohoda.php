<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Order;

readonly final class SendOrderToPohoda
{
    public function __construct(
        public int $orderId,
        public int $adminId,
    ) {
    }
}
