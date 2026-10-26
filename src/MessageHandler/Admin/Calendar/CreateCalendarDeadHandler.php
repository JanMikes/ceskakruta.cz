<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Calendar;

use CeskaKruta\Web\Message\Admin\Calendar\CreateCalendarDead;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateCalendarDeadHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new row
     */
    public function __invoke(CreateCalendarDead $message): int
    {
        return $this->repository->insert('calendar_dead', $message->columns(), $message->adminId);
    }
}
