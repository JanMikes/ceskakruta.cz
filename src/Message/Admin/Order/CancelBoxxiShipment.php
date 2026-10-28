<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Order;

/**
 * Cancels the order's Boxxi shipment and marks the order as not sent (`boxxi_sync_dt` + `boxxi_shipment_number` NULL),
 * so it can be sent again from the Boxxi page.
 */
readonly final class CancelBoxxiShipment
{
    public function __construct(
        public int $orderId,
        public int $adminId,
    ) {
    }
}
