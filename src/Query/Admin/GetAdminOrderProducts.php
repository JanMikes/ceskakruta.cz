<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\Order\AdminOrderProduct;
use Doctrine\DBAL\Connection;

readonly final class GetAdminOrderProducts
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * All not deleted products (inactive ones too: an order may contain them), sorted like the shop (`order DESC`).
     *
     * @return array<int, AdminOrderProduct>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT id, name, unit, price, price_pack, type, half_of_product_id, can_be_sliced_flag, can_be_packed_flag, active_flag FROM product WHERE del_flag = 0 ORDER BY `order` DESC, id')
            ->fetchAllAssociative();

        $products = [];

        foreach ($rows as $row) {
            /**
             * @var array{
             *     id: int,
             *     name: null|string,
             *     unit: string,
             *     price: null|int,
             *     price_pack: null|int,
             *     type: null|int,
             *     half_of_product_id: null|int,
             *     can_be_sliced_flag: null|int,
             *     can_be_packed_flag: int,
             *     active_flag: int,
             * } $row
             */
            $products[$row['id']] = new AdminOrderProduct(
                id: $row['id'],
                name: (string) $row['name'],
                unit: $row['unit'],
                price: $row['price'],
                pricePack: $row['price_pack'],
                type: $row['type'],
                halfOfProductId: $row['half_of_product_id'],
                canBeSliced: $row['can_be_sliced_flag'] === 1,
                canBePacked: $row['can_be_packed_flag'] === 1,
                active: $row['active_flag'] === 1,
            );
        }

        return $products;
    }
}
