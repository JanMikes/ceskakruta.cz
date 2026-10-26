<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Place;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Place\EditPlace;
use CeskaKruta\Web\Query\Admin\GetAdminPlaces;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditPlaceHandler
{
    public function __construct(
        private GetAdminPlaces $getAdminPlaces,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditPlace $message): void
    {
        $this->getAdminPlaces->oneById($message->id);

        $this->repository->update('place', $message->id, $message->columns->toArray(), $message->adminId);
    }
}
