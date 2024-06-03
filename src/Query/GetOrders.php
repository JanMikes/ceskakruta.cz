<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use CeskaKruta\Web\Exceptions\OrderNotFound;
use CeskaKruta\Web\Value\Order;
use CeskaKruta\Web\Value\OrderItem;
use Doctrine\DBAL\Connection;

final class GetOrders
{
    /** @var array<Order>|null  */
    private null|array $orders = null;

    /** @var array<int, array<OrderItem>>  */
    private array $orderItems = [];

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<Order>
     */
    public function ofUser(int $userId): array
    {
        if ($this->orders === null) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM `order` WHERE user_id = :userId AND active_flag = 1 AND del_flag = 0 ORDER BY date DESC', [
                    'userId' => $userId,
                ])
                ->fetchAllAssociative();

            $orders = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     place_id: int,
                 *     date: string,
                 *     ins_dt: string,
                 *     email: string,
                 *     phone: null|string,
                 *     name: null|string,
                 *     pay_by_card: null|int,
                 *     delivery_street: null|string,
                 *     delivery_city: null|string,
                 *     delivery_postal_code: null|string,
                 *     note: null|string,
                 *     price_total: int|float,
                 * } $row
                 */

                $id = $row['id'];

                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $row['date']);
                assert($date instanceof \DateTimeImmutable);

                $orderedAt = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['ins_dt']);
                assert($orderedAt instanceof \DateTimeImmutable);

                $orders[$id] = new Order(
                    id: $id,
                    placeId: $row['place_id'],
                    orderedAt: $orderedAt,
                    date: $date,
                    email: $row['email'],
                    phone: $row['phone'] ?? '',
                    name: $row['name'] ?? '',
                    payByCard: $row['pay_by_card'] === 1,
                    deliveryStreet: $row['delivery_street'],
                    deliveryCity: $row['delivery_city'],
                    deliveryPostalCode: $row['delivery_postal_code'],
                    note: $row['note'],
                    priceTotal: $row['price_total'],
                );
            }

            $this->orders = $orders;
        }

        return $this->orders;
    }

    public function oneById(int $userId, int $orderId): Order
    {
        return $this->ofUser($userId)[$orderId] ?? throw new OrderNotFound();
    }

    /**
     * @return array<OrderItem>
     */
    public function getOrderItems(int $orderId): array
    {
        if (!isset($this->orderItems[$orderId])) {
            $rows = $this->connection
                ->executeQuery('SELECT * FROM order_item WHERE order_id = :orderId AND active_flag = 1 AND del_flag = 0', [
                    'orderId' => $orderId,
                ])
                ->fetchAllAssociative();

            $this->orderItems[$orderId] = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     product_id: int,
                 *     is_sliced: null|int,
                 *     is_packed: null|int,
                 *     amount: int|float,
                 *     price_per_unit: int|float,
                 *     price_packing: int,
                 *     price_total: int|float,
                 *     weight_from: null|float,
                 *     weight_to: null|float,
                 * } $row
                 */

                $this->orderItems[$orderId][] = new OrderItem(
                    productId: $row['product_id'],
                    isSliced: $row['is_sliced'] === 1,
                    isPacked: $row['is_packed'] === 1,
                    pricePacking: $row['price_packing'],
                    amount: $row['amount'],
                    pricePerUnit: $row['price_per_unit'],
                    priceTotal: $row['price_total'],
                    weightFrom: $row['weight_from'],
                    weightTo: $row['weight_to'],
                );
            }
        }

        return $this->orderItems[$orderId];
    }
}
