<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Values of the Boxxi form (`boxxi[date|cool_balik_price|cool_balik_box_count|weight]`) used for the shipment payload.
 */
readonly final class BoxxiShipmentData
{
    public function __construct(
        /** "Datum doručení" → `order.date` */
        public \DateTimeImmutable $deliveryDate,
        /** "Finální cena" → `order.cool_balik_price`, sent as COD */
        public float $price,
        /** "Počet boxů" → `order.cool_balik_box_count` */
        public int $boxCount,
        /** "Finální váha (kg)" → `order.cool_balik_weight` + `order.boxxi_weight` */
        public float $weight,
    ) {
    }
}
