<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Value\Admin\AdminSortimentItem;
use Doctrine\DBAL\Connection;

readonly final class GetAdminSortiment
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Inactive included (they can be activated again), sorted like the old list.
     *
     * @return list<AdminSortimentItem>
     */
    public function allInCategory(int $categoryId): array
    {
        $rows = $this->connection
            ->executeQuery('SELECT * FROM sortiment WHERE category_id = :categoryId AND del_flag = 0 ORDER BY `order` DESC', ['categoryId' => $categoryId])
            ->fetchAllAssociative();

        return array_map(self::createItem(...), $rows);
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): AdminSortimentItem
    {
        $row = $this->connection
            ->executeQuery('SELECT * FROM sortiment WHERE id = :id AND del_flag = 0', ['id' => $id])
            ->fetchAssociative();

        if ($row === false) {
            throw new RecordNotFound();
        }

        return self::createItem($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function createItem(array $row): AdminSortimentItem
    {
        /**
         * @var array{
         *     id: int,
         *     category_id: null|int,
         *     name: null|string,
         *     text: null|string,
         *     active_flag: int,
         * } $row
         */

        return new AdminSortimentItem(
            id: $row['id'],
            categoryId: $row['category_id'],
            name: $row['name'],
            text: $row['text'],
            active: (bool) $row['active_flag'],
        );
    }
}
