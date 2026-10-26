<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use Doctrine\DBAL\Connection;

readonly final class GetUserBannedProducts
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * Products that can be banned: all active products sorted by name.
     *
     * @return array<int, string> id => name
     */
    public function productChoices(): array
    {
        /** @var array<int|string, string> $pairs */
        $pairs = $this->connection
            ->executeQuery('SELECT id, name FROM product WHERE active_flag = 1 AND del_flag = 0 ORDER BY name ASC, id ASC')
            ->fetchAllKeyValue();

        $choices = [];
        foreach ($pairs as $id => $name) {
            $choices[(int) $id] = $name;
        }

        return $choices;
    }

    /**
     * @return list<int>
     */
    public function bannedProductIds(int $userId): array
    {
        $ids = $this->connection
            ->executeQuery(
                'SELECT product_id FROM user_banned_product WHERE user_id = :userId AND active_flag = 1 AND del_flag = 0 ORDER BY product_id',
                ['userId' => $userId],
            )
            ->fetchFirstColumn();

        return array_map(static fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, $ids);
    }
}
