<?php

declare(strict_types=1);

namespace CeskaKruta\Web\Query;

use Doctrine\DBAL\Connection;

final class GetBannedProductIds
{
    public function __construct(
        readonly private Connection $connection,
    ) {
    }

    /**
     * @return array<int>
     */
    public function forUser(int $userId): array
    {
        /** @var array<int> $productIds */
        $productIds = $this->connection
            ->executeQuery(
                'SELECT product_id FROM user_banned_product WHERE user_id = :userId AND active_flag = 1 AND del_flag = 0',
                ['userId' => $userId],
            )
            ->fetchFirstColumn();

        return $productIds;
    }
}
