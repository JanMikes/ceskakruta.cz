<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminRecipe;
use Doctrine\DBAL\Connection;

readonly final class GetAdminRecipes
{
    private const SELECT = 'SELECT r.id, r.product_id, r.name, r.text, r.photo_id, r.active_flag, ph.path AS photo_path, ph.filename AS photo_filename
        FROM recipe r
        LEFT JOIN photo ph ON ph.id = r.photo_id';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), sorted like the old list.
     *
     * @return list<AdminRecipe>
     */
    public function allOfProduct(int $productId): array
    {
        $rows = $this->connection
            ->executeQuery(self::SELECT . ' WHERE r.product_id = :productId AND r.del_flag = 0 ORDER BY r.name, r.id', ['productId' => $productId])
            ->fetchAllAssociative();

        return array_map(self::createRecipe(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminRecipe
    {
        $row = $this->connection
            ->executeQuery(self::SELECT . ' WHERE r.id = :id AND r.del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createRecipe($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createRecipe(array $row): AdminRecipe
    {
        /**
         * @var array{
         *     id: int,
         *     product_id: int,
         *     name: null|string,
         *     text: null|string,
         *     photo_id: null|int,
         *     active_flag: int,
         *     photo_path: null|string,
         *     photo_filename: null|string,
         * } $row
         */

        $photo = null;
        if ($row['photo_path'] !== null && $row['photo_filename'] !== null && $row['photo_filename'] !== '') {
            $photo = $row['photo_path'] . '/' . $row['photo_filename'];
        }

        return new AdminRecipe(
            id: $row['id'],
            productId: $row['product_id'],
            name: $row['name'],
            text: $row['text'],
            photoId: $row['photo_id'],
            photo: $photo,
            active: (bool) $row['active_flag'],
        );
    }
}
