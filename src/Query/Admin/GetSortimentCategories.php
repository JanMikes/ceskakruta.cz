<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\SortimentCategory;
use Doctrine\DBAL\Connection;

final class GetSortimentCategories
{
    /** @var null|list<SortimentCategory> */
    private null|array $categories = null;

    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * Not deleted, inactive included — like the old admin navbar (`Entity::findAll()`).
     *
     * @return list<SortimentCategory>
     */
    public function all(): array
    {
        if ($this->categories === null) {
            $rows = $this->connection
                ->executeQuery('SELECT id, name, active_flag FROM sortiment_category WHERE del_flag = 0 ORDER BY `order` DESC, id')
                ->fetchAllAssociative();

            $categories = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     name: string,
                 *     active_flag: int,
                 * } $row
                 */

                $categories[] = new SortimentCategory(
                    id: $row['id'],
                    name: $row['name'],
                    active: (bool) $row['active_flag'],
                );
            }

            $this->categories = $categories;
        }

        return $this->categories;
    }
}
