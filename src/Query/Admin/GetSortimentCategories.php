<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Exceptions\RecordNotFound;
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
                ->executeQuery(<<<'SQL'
SELECT c.id, c.name, c.active_flag,
    (SELECT COUNT(*) FROM sortiment s WHERE s.category_id = c.id AND s.del_flag = 0) AS items_count
FROM sortiment_category c
WHERE c.del_flag = 0
ORDER BY c.`order` DESC, c.id
SQL)
                ->fetchAllAssociative();

            $categories = [];

            foreach ($rows as $row) {
                /**
                 * @var array{
                 *     id: int,
                 *     name: string,
                 *     active_flag: int,
                 *     items_count: int|string,
                 * } $row
                 */

                $categories[] = new SortimentCategory(
                    id: $row['id'],
                    name: $row['name'],
                    active: (bool) $row['active_flag'],
                    itemsCount: (int) $row['items_count'],
                );
            }

            $this->categories = $categories;
        }

        return $this->categories;
    }

    /**
     * @throws RecordNotFound
     */
    public function oneById(int $id): SortimentCategory
    {
        foreach ($this->all() as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        throw new RecordNotFound();
    }
}
