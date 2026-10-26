<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Value\Admin;

/**
 * Order header (`order` row + its place) as needed by the Pohoda XML and the Boxxi payload.
 * Pure data, built by the admin order query from DB values.
 */
readonly final class AdminOrder
{
    public function __construct(
        public int $id,
        /** `order.ins_dt` */
        public \DateTimeImmutable $insertedAt,
        /** `order.date` (delivery / pick-up date) */
        public \DateTimeImmutable $date,
        public string $name,
        public string $email,
        public string $phone,
        public null|string $deliveryStreet,
        public null|string $deliveryCity,
        public null|string $deliveryPostalCode,
        public string $note,
        public bool $payByCard,
        /** `order.coupon_percent_value`, NULL/0 = no discount */
        public null|int $couponPercentValue,
        public AdminOrderPlace $place,
    ) {
    }
}
