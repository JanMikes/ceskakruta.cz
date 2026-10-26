<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Calendar;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Calendar\EditCalendarCold;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarCold;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditCalendarColdHandler
{
    public function __construct(
        private GetAdminCalendarCold $getAdminCalendarCold,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditCalendarCold $message): void
    {
        $this->getAdminCalendarCold->oneById($message->id);

        $this->repository->update('calendar_cold', $message->id, $message->columns(), $message->adminId);
    }
}
