<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Message\Admin\User;

readonly final class SetUserBannedProducts
{
    /**
     * @param list<int> $productIds
     */
    public function __construct(
        public int $userId,
        public array $productIds,
        public int $adminId,
    ) {
    }
}
