<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\CouponExpired;
use CeskaKruta\Web\Exceptions\CouponNotFound;
use CeskaKruta\Web\Exceptions\CouponOrderDateExceeded;
use CeskaKruta\Web\Exceptions\CouponUsageLimitReached;
use CeskaKruta\Web\Message\UseCoupon;
use CeskaKruta\Web\Query\GetCoupon;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\CouponChecker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class UseCouponHandler
{
    public function __construct(
        private GetCoupon $getCoupon,
        private CartStorage $cartStorage,
        private CouponChecker $couponChecker,
    ) {
    }

    /**
     * @throws CouponExpired
     * @throws CouponNotFound
     * @throws CouponOrderDateExceeded
     * @throws CouponUsageLimitReached
     */
    public function __invoke(UseCoupon $message): void
    {
        if ($message->code !== null) {
            $coupon = $this->getCoupon->oneByCode($message->code);

            try {
                if ($coupon === null) {
                    throw new CouponNotFound();
                }

                $this->couponChecker->check($coupon);
            } catch (\Throwable $exception) {
                $this->cartStorage->storeCoupon(null);

                throw $exception;
            }
        }

        $this->cartStorage->storeCoupon($message->code);
    }
}
