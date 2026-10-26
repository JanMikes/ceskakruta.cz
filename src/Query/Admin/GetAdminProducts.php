<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminProduct;
use CeskaKruta\Web\Value\Admin\PackagesSize;
use Doctrine\DBAL\Connection;

readonly final class GetAdminProducts
{
    private const SELECT = 'SELECT p.*, (SELECT COUNT(*) FROM recipe r WHERE r.product_id = p.id AND r.del_flag = 0) AS recipes_count FROM product p';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), sorted like the old list.
     *
     * @return list<AdminProduct>
     */
    public function all(): array
    {
        $rows = $this->connection
            ->executeQuery(self::SELECT . ' WHERE p.del_flag = 0 ORDER BY p.`order` DESC')
            ->fetchAllAssociative();

        return array_map(self::createProduct(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminProduct
    {
        $row = $this->connection
            ->executeQuery(self::SELECT . ' WHERE p.id = :id AND p.del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createProduct($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createProduct(array $row): AdminProduct
    {
        /**
         * @var array{
         *     id: int,
         *     name: null|string,
         *     type: null|int,
         *     unit: string,
         *     weight_per_unit: float|string,
         *     price: null|int,
         *     code: null|string,
         *     can_be_sliced_flag: null|int,
         *     can_be_packed_flag: int,
         *     force_packing: int,
         *     price_pack: null|int,
         *     text_additional: null|string,
         *     text: null|string,
         *     packages_size: string,
         *     active_flag: int,
         *     recipes_count: int|string,
         * } $row
         */

        return new AdminProduct(
            id: $row['id'],
            name: $row['name'],
            type: $row['type'],
            unit: $row['unit'],
            weightPerUnit: (float) $row['weight_per_unit'],
            price: $row['price'],
            code: $row['code'],
            canBeSliced: (bool) $row['can_be_sliced_flag'],
            canBePacked: (bool) $row['can_be_packed_flag'],
            forcePacking: (bool) $row['force_packing'],
            pricePack: $row['price_pack'],
            textAdditional: $row['text_additional'],
            text: $row['text'],
            packagesSize: PackagesSize::fromJson($row['packages_size']),
            active: (bool) $row['active_flag'],
            recipesCount: (int) $row['recipes_count'],
        );
    }
}
