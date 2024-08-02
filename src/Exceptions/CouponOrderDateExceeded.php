<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Exceptions;

use CeskaKruta\Web\Value\Coupon;

final class CouponOrderDateExceeded extends \Exception
{
    public function __construct(
        readonly public Coupon $coupon,
    ) {
        parent::__construct();
    }
}
