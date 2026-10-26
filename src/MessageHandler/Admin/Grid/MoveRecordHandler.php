<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Grid;

use CeskaKruta\Web\Exceptions\GridActionNotAllowed;
use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Grid\MoveRecord;
use CeskaKruta\Web\Repository\Admin\GridRecordRepository;
use CeskaKruta\Web\Value\Admin\Grid\GridAction;
use CeskaKruta\Web\Value\Admin\Grid\MoveDirection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class MoveRecordHandler
{
    public function __construct(
        private GridRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     * @throws GridActionNotAllowed
     */
    public function __invoke(MoveRecord $message): void
    {
        $row = $this->repository->get($message->table, $message->id);
        $action = $message->direction === MoveDirection::Up ? GridAction::MoveUp : GridAction::MoveDown;

        if (!$message->table->allows($action, $row)) {
            throw new GridActionNotAllowed();
        }

        $this->repository->move($message->table, $message->id, $message->direction, $message->adminId);
    }
}
