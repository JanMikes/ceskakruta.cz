<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Calendar;

use CeskaKruta\Web\Message\Admin\Calendar\CreateCalendarAlive;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateCalendarAliveHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new row
     */
    public function __invoke(CreateCalendarAlive $message): int
    {
        return $this->repository->insert('calendar_alive', $message->columns(), $message->adminId);
    }
}
