<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Sortiment\EditSortimentItem;
use CeskaKruta\Web\Query\Admin\GetAdminSortiment;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditSortimentItemHandler
{
    public function __construct(
        private GetAdminSortiment $getAdminSortiment,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditSortimentItem $message): void
    {
        $this->getAdminSortiment->oneById($message->id);

        $this->repository->update('sortiment', $message->id, $message->columns(), $message->adminId);
    }
}
