<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\User;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\User\SetUserBannedProducts;
use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class SetUserBannedProductsHandler
{
    public function __construct(
        private GetAdminUsers $getAdminUsers,
        private AdminRecordRepository $repository,
        private Connection $connection,
    ) {
    }

    /**
     * Same as the old admin: hard delete all bans of the user, then one row per banned product.
     *
     * @throws RecordNotFound
     */
    public function __invoke(SetUserBannedProducts $message): void
    {
        $this->getAdminUsers->oneById($message->userId);

        $this->connection->delete('user_banned_product', ['user_id' => $message->userId]);

        foreach (array_unique($message->productIds) as $productId) {
            $this->repository->insert('user_banned_product', [
                'user_id' => $message->userId,
                'product_id' => $productId,
            ], $message->adminId);
        }
    }
}
