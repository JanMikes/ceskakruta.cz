<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query\Admin;

use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use Doctrine\DBAL\Connection;

/**
 * Number of rows "Aktivovat vše" would activate — the button is disabled at 0.
 */
readonly final class CountInactiveRecords
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function count(AdminTable $table, null|int $scope = null): int
    {
        $query = $this->connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('`' . $table->value . '`')
            ->where('active_flag = 0')
            ->andWhere('del_flag = 0');

        $scopeColumn = $table->scopeColumn();
        if ($scopeColumn !== null) {
            $query->andWhere('`' . $scopeColumn . '` = :scope')
                ->setParameter('scope', $scope);
        }

        $count = $query->fetchOne();

        return is_numeric($count) ? (int) $count : 0;
    }
}
