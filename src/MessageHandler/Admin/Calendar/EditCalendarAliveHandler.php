<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Calendar;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Calendar\EditCalendarAlive;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarAlive;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditCalendarAliveHandler
{
    public function __construct(
        private GetAdminCalendarAlive $getAdminCalendarAlive,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditCalendarAlive $message): void
    {
        $this->getAdminCalendarAlive->oneById($message->id);

        $this->repository->update('calendar_alive', $message->id, $message->columns(), $message->adminId);
    }
}
