<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Services\Cart\CartService;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

readonly final class OrderRepository
{
    public function __construct(
        private Connection $connection,
        private CartService $cartService,
    ) {
    }

    public function createOrder(): int
    {
        // TODO: data as parameter

        $this->connection->beginTransaction();
        try {
            $date = $this->cartService->getDate();
            assert($date !== null);

            $orderData = $this->cartService->getOrderData();
            assert($orderData !== null);

            $now = new DateTimeImmutable();

            $this->connection->insert('`order`', [
                'user_id'               => null, // TODO
                'place_id'              => $this->cartService->getPickupPlace(), // TODO - muze byt delivery
                'date'                  => $date->format('Y-m-d'),
                'email'                 => $orderData->email,
                'phone'                 => $orderData->phone,
                'name'                  => $orderData->name,
                'pay_by_card'           => false, // TODO
                'delivery_street'       => null, // TODO
                'delivery_city'         => null, // TODO
                'delivery_postal_code'  => null, // TODO
                'note'                  => $orderData->note,
                'price_total'           => $this->cartService->totalPrice()->amount,
                'ins_dt'                => $now->format('Y-m-d H:i:s'),
            ]);

            $orderId = (int) $this->connection->lastInsertId();

            foreach ($this->cartService->getItems() as $item) {
                $this->connection->insert('order_item', [
                    'order_id'         => $orderId,
                    'product_id'       => $item->product->id,
                    'product_tp_id'    => $item->product->turkeyType,
                    'is_sliced'        => $item->slice,
                    'is_packed'        => $item->pack,
                    'amount'           => $item->quantity,
                    'price_per_unit'   => $item->product->price(),
                    'price_packing'    => $item->product->packPrice ?? 0,
                    'price_total'      => $item->product->price() * $item->quantity,
                    'weight_from'      => $item->product->weightFrom,
                    'weight_to'        => $item->product->weightTo,
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
