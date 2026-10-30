<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Order as shown in the admin list, detail, CSV export and Boxxi page: the integration data (`AdminOrder`) + list columns.
 */
readonly final class AdminOrderRecord
{
    public function __construct(
        public AdminOrder $order,
        public float $priceTotal,
        /** Ordered to a wholesale-only place (`place.allow_only_wholesale`) = wholesale order ("Velkoodběratel") */
        public bool $isWholesale,
        public null|string $couponCode,
        public null|\DateTimeImmutable $pohodaSyncedAt,
        public null|\DateTimeImmutable $boxxiSyncedAt,
        public null|float $coolBalikPrice,
        /** `order.boxxi_shipment_number` from the create response, NULL for orders sent before it was stored */
        public null|string $boxxiShipmentNumber,
    ) {
    }
}
