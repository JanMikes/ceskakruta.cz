<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\User;

readonly final class SetUserWholesale
{
    public function __construct(
        public int $userId,
        public bool $allow,
        public int $adminId,
    ) {
    }
}
