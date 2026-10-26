<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Actuality;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Actuality\EditActuality;
use CeskaKruta\Web\Query\Admin\GetAdminActualities;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditActualityHandler
{
    public function __construct(
        private GetAdminActualities $getAdminActualities,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditActuality $message): void
    {
        $this->getAdminActualities->oneById($message->id);

        $this->repository->update('actuality', $message->id, $message->columns(), $message->adminId);
    }
}
