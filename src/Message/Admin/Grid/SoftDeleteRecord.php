<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Grid;

use CeskaKruta\Web\Value\Admin\Grid\AdminTable;

readonly final class SoftDeleteRecord
{
    public function __construct(
        public AdminTable $table,
        public int $id,
        public int $adminId,
    ) {
    }
}
