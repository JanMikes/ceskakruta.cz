<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Exceptions\CouponExpired;
use CeskaKruta\Web\Exceptions\CouponOrderDateExceeded;
use CeskaKruta\Web\Exceptions\CouponUsageLimitReached;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Value\Coupon;
use DateTimeImmutable;

readonly final class CouponChecker
{
    public function __construct(
        private CartService $cartService,
    ) {
    }

    /**
     * @throws CouponExpired
     * @throws CouponOrderDateExceeded
     * @throws CouponUsageLimitReached
     */
    public function check(Coupon $coupon, null|DateTimeImmutable $orderDate = null): void
    {
        if ($orderDate === null) {
            $orderDate = $this->cartService->getDate()?->setTime(0, 0, 0);
        }

        if ($orderDate !== null && $coupon->deliveryUntilDate !== null) {
            $limitDate = $coupon->deliveryUntilDate->setTime(0, 0, 0);

            if ($orderDate > $limitDate) {
                throw new CouponOrderDateExceeded($coupon);
            }
        }

        if ($coupon->untilDate !== null) {
            $now = (new DateTimeImmutable())->setTime(0, 0, 0);

            $limitDate = $coupon->untilDate->setTime(0, 0, 0);

            if ($now > $limitDate) {
                throw new CouponExpired();
            }
        }
    }
}
