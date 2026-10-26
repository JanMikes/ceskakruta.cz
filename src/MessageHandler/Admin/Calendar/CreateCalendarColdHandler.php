<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Calendar;

use CeskaKruta\Web\Message\Admin\Calendar\CreateCalendarCold;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateCalendarColdHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new row
     */
    public function __invoke(CreateCalendarCold $message): int
    {
        return $this->repository->insert('calendar_cold', $message->columns(), $message->adminId);
    }
}
