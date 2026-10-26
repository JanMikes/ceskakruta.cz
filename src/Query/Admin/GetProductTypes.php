<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use Doctrine\DBAL\Connection;

/**
 * `lst_product_tp` options of the calendar_cold / calendar_alive forms (old `fetchPairs("id", "name", "id ASC")`).
 */
readonly final class GetProductTypes
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @return array<string, int> name => id, for a ChoiceType
     */
    public function choices(): array
    {
        /** @var list<array{id: int, name: null|string}> $rows */
        $rows = $this->connection
            ->executeQuery('SELECT id, name FROM lst_product_tp WHERE del_flag = 0 ORDER BY id ASC')
            ->fetchAllAssociative();

        $choices = [];
        foreach ($rows as $row) {
            $choices[(string) $row['name']] = (int) $row['id'];
        }

        return $choices;
    }
}
