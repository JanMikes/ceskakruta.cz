<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Place;

use CeskaKruta\Web\Message\Admin\Place\AddPlaceClosedDay;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class AddPlaceClosedDayHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new closed day
     */
    public function __invoke(AddPlaceClosedDay $message): int
    {
        return $this->repository->insert('place_closed_day', [
            'date' => $message->date->format('Y-m-d'),
            'place_id' => $message->placeId,
        ], $message->adminId);
    }
}
