<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminOrder;
use CeskaKruta\Web\Value\Admin\AdminOrderDetailItem;
use CeskaKruta\Web\Value\Admin\AdminOrderFilter;
use CeskaKruta\Web\Value\Admin\AdminOrderItem;
use CeskaKruta\Web\Value\Admin\AdminOrderPlace;
use CeskaKruta\Web\Value\Admin\AdminOrderRecord;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

readonly final class GetAdminOrders
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Sorted `id DESC`, `$limit = null` = all pages (CSV export).
     *
     * @return list<AdminOrderRecord>
     */
    public function filtered(AdminOrderFilter $filter, int $offset = 0, null|int $limit = null): array
    {
        $rows = $this->filteredQuery($filter)
            ->select('o.*', 'p.name AS place_name', 'p.is_cool_balik AS place_is_cool_balik', 'p.force_delivery_address AS place_force_delivery_address', 'p.allow_only_wholesale AS place_allow_only_wholesale')
            ->orderBy('o.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(self::createRecord(...), $rows);
    }

    public function count(AdminOrderFilter $filter): int
    {
        $count = $this->filteredQuery($filter)
            ->select('COUNT(*)')
            ->executeQuery()
            ->fetchOne();
        assert(is_numeric($count));

        return (int) $count;
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminOrderRecord
    {
        $row = $this->connection
            ->executeQuery('
                SELECT o.*, p.name AS place_name, p.is_cool_balik AS place_is_cool_balik, p.force_delivery_address AS place_force_delivery_address,
                    p.allow_only_wholesale AS place_allow_only_wholesale
                FROM `order` o
                LEFT JOIN place p ON p.id = o.place_id
                WHERE o.id = :id AND o.del_flag = 0
            ', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createRecord($row);
    }

    /**
     * Items in `order_item.id` order (Pohoda documents, Boxxi weight).
     *
     * @return list<AdminOrderItem>
     */
    public function items(int $orderId): array
    {
        return array_map(
            static fn (AdminOrderDetailItem $item): AdminOrderItem => $item->item,
            $this->itemRows($orderId, 'oi.id ASC'),
        );
    }

    /**
     * Items sorted like the old detail: `product_tp_id DESC, product_id ASC`.
     *
     * @return list<AdminOrderDetailItem>
     */
    public function detailItems(int $orderId): array
    {
        return $this->itemRows($orderId, 'oi.product_tp_id DESC, oi.product_id ASC, oi.id ASC');
    }

    /**
     * @param literal-string $orderBy
     * @return list<AdminOrderDetailItem>
     */
    private function itemRows(int $orderId, string $orderBy): array
    {
        $rows = $this->connection
            ->executeQuery('
                SELECT oi.*, pr.name AS product_name, pr.code AS product_code, pr.unit AS product_unit, pr.weight_per_unit AS product_weight_per_unit
                FROM order_item oi
                JOIN product pr ON pr.id = oi.product_id
                WHERE oi.order_id = :orderId
                ORDER BY ' . $orderBy, ['orderId' => $orderId])
            ->fetchAllAssociative();

        return array_map(static function (array $row): AdminOrderDetailItem {
            /**
             * @var array{
             *     id: int,
             *     product_id: int,
             *     product_tp_id: null|int,
             *     product_name: null|string,
             *     product_code: null|string,
             *     product_unit: string,
             *     product_weight_per_unit: float|string,
             *     amount: float|string,
             *     price_per_unit: int,
             *     price_total: int,
             *     weight_from: null|float|string,
             *     weight_to: null|float|string,
             * } $row
             */

            return new AdminOrderDetailItem(
                new AdminOrderItem(
                    id: $row['id'],
                    productId: $row['product_id'],
                    productTypeId: $row['product_tp_id'],
                    productName: (string) $row['product_name'],
                    productCode: $row['product_code'],
                    productUnit: $row['product_unit'],
                    productWeightPerUnit: (float) $row['product_weight_per_unit'],
                    amount: (float) $row['amount'],
                    pricePerUnit: $row['price_per_unit'],
                    weightFrom: $row['weight_from'] !== null ? (float) $row['weight_from'] : null,
                    weightTo: $row['weight_to'] !== null ? (float) $row['weight_to'] : null,
                ),
                $row['price_total'],
            );
        }, $rows);
    }

    private function filteredQuery(AdminOrderFilter $filter): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder()
            ->from('`order`', 'o')
            ->leftJoin('o', 'place', 'p', 'p.id = o.place_id')
            ->where('o.del_flag = 0');

        if ($filter->dateFrom !== null) {
            $query->andWhere('o.date >= :dateFrom')->setParameter('dateFrom', $filter->dateFrom->format('Y-m-d'));
        }

        if ($filter->dateTo !== null) {
            $query->andWhere('o.date <= :dateTo')->setParameter('dateTo', $filter->dateTo->format('Y-m-d'));
        }

        if ($filter->customerName !== null) {
            $query->andWhere('o.name LIKE :customerName')->setParameter('customerName', '%' . $filter->customerName . '%');
        }

        if ($filter->missing === 'pohoda') {
            $query->andWhere('o.pohoda_sync_dt IS NULL');
        }

        if ($filter->missing === 'boxxi') {
            $query->andWhere('p.is_cool_balik = 1 AND o.boxxi_sync_dt IS NULL');
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createRecord(array $row): AdminOrderRecord
    {
        /**
         * @var array{
         *     id: int,
         *     place_id: null|int,
         *     place_name: null|string,
         *     place_is_cool_balik: null|int,
         *     place_force_delivery_address: null|int,
         *     ins_dt: string,
         *     date: string,
         *     name: string,
         *     email: string,
         *     phone: string,
         *     delivery_street: null|string,
         *     delivery_city: null|string,
         *     delivery_postal_code: null|string,
         *     note: string,
         *     pay_by_card: null|int,
         *     coupon_percent_value: null|int,
         *     coupon_code: null|string,
         *     price_total: float|string,
         *     place_allow_only_wholesale: null|int,
         *     pohoda_sync_dt: null|string,
         *     boxxi_sync_dt: null|string,
         *     cool_balik_price: null|float|string,
         *     boxxi_shipment_number: null|string,
         * } $row
         */

        $order = new AdminOrder(
            id: $row['id'],
            insertedAt: new \DateTimeImmutable($row['ins_dt']),
            date: new \DateTimeImmutable($row['date']),
            name: $row['name'],
            email: $row['email'],
            phone: $row['phone'],
            deliveryStreet: $row['delivery_street'],
            deliveryCity: $row['delivery_city'],
            deliveryPostalCode: $row['delivery_postal_code'],
            note: $row['note'],
            payByCard: (bool) $row['pay_by_card'],
            couponPercentValue: $row['coupon_percent_value'],
            place: new AdminOrderPlace(
                id: (int) $row['place_id'],
                name: (string) $row['place_name'],
                isCoolBalik: (bool) $row['place_is_cool_balik'],
                forceDeliveryAddress: (bool) $row['place_force_delivery_address'],
            ),
        );

        return new AdminOrderRecord(
            order: $order,
            priceTotal: (float) $row['price_total'],
            isWholesale: (bool) $row['place_allow_only_wholesale'],
            couponCode: $row['coupon_code'],
            pohodaSyncedAt: $row['pohoda_sync_dt'] !== null ? new \DateTimeImmutable($row['pohoda_sync_dt']) : null,
            boxxiSyncedAt: $row['boxxi_sync_dt'] !== null ? new \DateTimeImmutable($row['boxxi_sync_dt']) : null,
            coolBalikPrice: $row['cool_balik_price'] !== null ? (float) $row['cool_balik_price'] : null,
            boxxiShipmentNumber: $row['boxxi_shipment_number'],
        );
    }
}
