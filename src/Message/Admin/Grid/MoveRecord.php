<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\Grid;

use CeskaKruta\Web\Value\Admin\Grid\AdminTable;
use CeskaKruta\Web\Value\Admin\Grid\MoveDirection;

readonly final class MoveRecord
{
    public function __construct(
        public AdminTable $table,
        public int $id,
        public MoveDirection $direction,
        public int $adminId,
    ) {
    }
}
