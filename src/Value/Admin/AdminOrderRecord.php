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
        /** `order.source` set = ordered on the new web ("Nový web") */
        public null|string $source,
        public null|string $couponCode,
        public null|\DateTimeImmutable $pohodaSyncedAt,
        public null|\DateTimeImmutable $boxxiSyncedAt,
        public null|float $coolBalikPrice,
    ) {
    }
}
