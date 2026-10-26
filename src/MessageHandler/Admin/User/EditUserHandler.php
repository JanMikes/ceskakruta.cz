<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\User;

use CeskaKruta\Web\Exceptions\CannotRevokeOwnAdmin;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\User\EditUser;
use CeskaKruta\Web\Query\Admin\GetAdminUsers;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditUserHandler
{
    public function __construct(
        private GetAdminUsers $getAdminUsers,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws CannotRevokeOwnAdmin
     */
    public function __invoke(EditUser $message): void
    {
        $user = $this->getAdminUsers->oneById($message->id);

        if ($message->id === $message->adminId && $user->isAdmin && !$message->isAdmin) {
            throw new CannotRevokeOwnAdmin();
        }

        $this->repository->update('user', $message->id, $message->columns(), $message->adminId);
    }
}
