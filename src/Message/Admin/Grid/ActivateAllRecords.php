<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Grid;

use CeskaKruta\Web\Value\Admin\Grid\AdminTable;

readonly final class ActivateAllRecords
{
    public function __construct(
        public AdminTable $table,
        /** Id of the product (recipes) / category (sortiment) the list belongs to, see AdminTable::scopeColumn() */
        public null|int $scope,
        public int $adminId,
    ) {
    }
}
