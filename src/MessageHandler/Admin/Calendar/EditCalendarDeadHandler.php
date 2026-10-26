<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Calendar;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Calendar\EditCalendarDead;
use CeskaKruta\Web\Query\Admin\GetAdminCalendarDead;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditCalendarDeadHandler
{
    public function __construct(
        private GetAdminCalendarDead $getAdminCalendarDead,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditCalendarDead $message): void
    {
        $this->getAdminCalendarDead->oneById($message->id);

        $this->repository->update('calendar_dead', $message->id, $message->columns(), $message->adminId);
    }
}
