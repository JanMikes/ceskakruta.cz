<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\FormData\OrderFormData;
use CeskaKruta\Web\Services\Cart\CartService;
use CeskaKruta\Web\Value\Address;
use CeskaKruta\Web\Value\Coupon;
use CeskaKruta\Web\Value\Product;
use CeskaKruta\Web\Value\ProductInCart;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

readonly final class OrderService
{
    public function __construct(
        private Connection $connection,
        private CartService $cartService,
    ) {
    }

    public function createOrderFromCart(null|int $userId): int
    {
        $this->connection->beginTransaction();
        try {
            $coupon = $this->cartService->getCoupon();

            $date = $this->cartService->getDate();
            assert($date !== null);

            $orderData = $this->cartService->getOrderData();
            assert($orderData !== null);

            $place = $this->cartService->getPlace();
            assert($place !== null);

            $deliveryAddress = $this->cartService->getDeliveryAddress();

            $payByCard = false;

            if ($deliveryAddress !== null) {
                $payByCard = $orderData->payByCard;
            }

            $now = new DateTimeImmutable();

            $this->connection->insert('`order`', [
                'user_id'               => $userId,
                'place_id'              => $place->id,
                'date'                  => $date->format('Y-m-d'),
                'email'                 => $orderData->email,
                'phone'                 => $orderData->phone,
                'name'                  => $orderData->name,
                'pay_by_card'           => $payByCard,
                'delivery_street'       => $deliveryAddress?->street,
                'delivery_city'         => $deliveryAddress?->city,
                'delivery_postal_code'  => $deliveryAddress?->postalCode,
                'note'                  => $orderData->note,
                'price_total'           => $this->cartService->totalPrice()->amount,
                'source'                => 'Web 2.0',
                'coupon_id'             => $coupon?->id,
                'coupon_code'           => $coupon?->code,
                'coupon_percent_value'  => $coupon?->percentValue,
                'price_total_before_discount'  => $coupon !== null ? $this->cartService->totalPriceWithoutDiscount()->amount : null,
                'ins_dt'                => $now->format('Y-m-d H:i:s'),
            ]);

            $orderId = (int) $this->connection->lastInsertId();

            foreach ($this->cartService->getItems() as $item) {
                $unitPrice = $item->product->price($coupon);

                $this->connection->insert('order_item', [
                    'order_id'         => $orderId,
                    'product_id'       => $item->product->id,
                    'product_tp_id'    => $item->product->turkeyType,
                    'is_sliced'        => $item->slice,
                    'is_packed'        => $item->pack,
                    'amount'           => $item->quantity,
                    'price_per_unit'   => $unitPrice,
                    'price_packing'    => $item->product->packPrice ?? 0,
                    'price_total'      => $unitPrice * $item->quantity,
                    'weight_from'      => $item->product->weightFrom,
                    'weight_to'        => $item->product->weightTo,
                    'note'             => $item->note,
                    'ins_dt'           => $now->format('Y-m-d H:i:s'),
                ]);
            }

            $this->connection->commit();

            return $orderId;
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    /**
     * @param array<ProductInCart> $items
     */
    public function createOrder(
        null|int $userId,
        DateTimeImmutable $date,
        OrderFormData $orderData,
        int $placeId,
        Address $deliveryAddress,
        array $items,
        int $totalPrice,
        string $source,
        null|Coupon $coupon = null,
    ): int
    {
        $this->connection->beginTransaction();
        try {
            $now = new DateTimeImmutable();

            $this->connection->insert('`order`', [
                'user_id'               => $userId,
                'place_id'              => $placeId,
                'date'                  => $date->format('Y-m-d'),
                'email'                 => $orderData->email,
                'phone'                 => $orderData->phone,
                'name'                  => $orderData->name,
                'pay_by_card'           => $orderData->payByCard,
                'delivery_street'       => $deliveryAddress->street,
                'delivery_city'         => $deliveryAddress->city,
                'delivery_postal_code'  => $deliveryAddress->postalCode,
                'note'                  => $orderData->note,
                'price_total'           => $totalPrice,
                'source'                => $source,
                'coupon_id'             => $coupon?->id,
                'coupon_code'           => $coupon?->code,
                'coupon_percent_value'  => $coupon?->percentValue,
                'price_total_before_discount' => $coupon !== null ? $this->cartService->totalPriceWithoutDiscount()->amount : null,
                'ins_dt' => $now->format('Y-m-d H:i:s'),
            ]);

            $orderId = (int) $this->connection->lastInsertId();

            foreach ($items as $item) {
                $product = $item->product;
                $unitPrice = $product->price();

                $this->connection->insert('order_item', [
                    'order_id'         => $orderId,
                    'product_id'       => $product->id,
                    'product_tp_id'    => $product->turkeyType,
                    'is_sliced'        => $item->slice,
                    'is_packed'        => $item->pack,
                    'amount'           => $item->quantity,
                    'price_per_unit'   => $unitPrice,
                    'price_packing'    => $product->packPrice ?? 0,
                    'price_total'      => $unitPrice * $item->quantity,
                    'weight_from'      => $product->weightFrom,
                    'weight_to'        => $product->weightTo,
                    'note'             => $item->note,
                    'ins_dt'           => $now->format('Y-m-d H:i:s'),
                ]);
            }

            $this->connection->commit();

            return $orderId;
        } catch (\Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }
}
