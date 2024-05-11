<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Services;

use CeskaKruta\Web\Services\Cart\CartStorage;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

readonly final class OrderRepository
{
    public function __construct(
        private Connection $connection,
        private CartStorage $cartStorage,
    ) {
    }

    public function createOrder(): int
    {
        // TODO: data as parameter

        $this->connection->beginTransaction();
        try {
            $date = $this->cartStorage->getDate();
            assert($date !== null);

            $orderData = $this->cartStorage->getOrderData();
            assert($orderData !== null);

            $now = new DateTimeImmutable();

            $this->connection->insert('`order`', [
                'user_id'               => null, // TODO
                'place_id'              => $this->cartStorage->getPickupPlace(), // TODO - muze byt delivery
                'date'                  => $date->format('Y-m-d'),
                'email'                 => $orderData->email,
                'phone'                 => $orderData->phone,
                'name'                  => $orderData->name,
                'pay_by_card'           => false, // TODO
                'delivery_street'       => null, // TODO
                'delivery_city'         => null, // TODO
                'delivery_postal_code'  => null, // TODO
                'note'                  => $orderData->note,
                'price_total'           => $this->cartStorage->totalPrice()->amount,
                'ins_dt'                => $now->format('Y-m-d H:i:s'),
            ]);

            $orderId = (int) $this->connection->lastInsertId();

            foreach ($this->cartStorage->getItems() as $item) {
                $this->connection->insert('order_item', [
                    'order_id'         => $orderId,
                    'product_id'       => $item->product->id,
                    'product_tp_id'    => null, // TODO
                    'is_sliced'        => null, // TODO
                    'is_packed'        => null, // TODO
                    'amount'           => $item->quantity,
                    'price_per_unit'   => $item->product->price(),
                    'price_packing'    => 0, // TODO
                    'price_total'      => $item->product->price() * $item->quantity,
                    'weight_from'      => null, // TODO
                    'weight_to'        => null, // TODO
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
