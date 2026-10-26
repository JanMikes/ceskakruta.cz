<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Grid;

use CeskaKruta\Web\Exceptions\GridActionNotAllowed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Grid\ActivateRecord;
use CeskaKruta\Web\Repository\Admin\GridRecordRepository;
use CeskaKruta\Web\Value\Admin\Grid\GridAction;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ActivateRecordHandler
{
    public function __construct(
        private GridRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws GridActionNotAllowed
     */
    public function __invoke(ActivateRecord $message): void
    {
        $row = $this->repository->get($message->table, $message->id);

        if (!$message->table->allows(GridAction::Activate, $row)) {
            throw new GridActionNotAllowed();
        }

        $this->repository->setActive($message->table, $message->id, true, $message->adminId);
    }
}
