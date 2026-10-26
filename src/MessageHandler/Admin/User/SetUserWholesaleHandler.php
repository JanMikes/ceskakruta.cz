<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\User;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\User\SetUserWholesale;
use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class SetUserWholesaleHandler
{
    public function __construct(
        private GetAdminUsers $getAdminUsers,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(SetUserWholesale $message): void
    {
        $this->getAdminUsers->oneById($message->userId);

        $this->repository->update('user', $message->userId, ['allow_wholesale' => $message->allow], $message->adminId);
    }
}
