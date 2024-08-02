<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler;

use CeskaKruta\Web\Exceptions\UnavailableDate;
use CeskaKruta\Web\Message\ChooseOrderDate;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Services\Cart\CartStorage;
use CeskaKruta\Web\Services\CouponChecker;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ChooseOrderDateHandler
{
    public function __construct(
        private CartService $cartService,
        private CartStorage $cartStorage,
        private CouponChecker $couponChecker,
    ) {
    }

    /**
     * @throws UnavailableDate
     */
    public function __invoke(ChooseOrderDate $message): void
    {
        $date = $message->date;

        if ($this->cartService->isDateAvailable($date, $message->placeId) === false) {
            throw new UnavailableDate();
        }

        $coupon = $this->cartService->getCoupon();

        if ($coupon) {
            $this->couponChecker->check($coupon, $date);
        }

        $this->cartStorage->storeDate($date);
    }
}
