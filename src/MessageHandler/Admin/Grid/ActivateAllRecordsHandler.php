<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Grid;

use CeskaKruta\Web\Exceptions\GridActionNotAllowed;
use CeskaKruta\Web\Message\Admin\Grid\ActivateAllRecords;
use CeskaKruta\Web\Repository\Admin\GridRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class ActivateAllRecordsHandler
{
    public function __construct(
        private GridRecordRepository $repository,
    ) {
    }

    /**
     * @throws GridActionNotAllowed
     */
    public function __invoke(ActivateAllRecords $message): void
    {
        if (!$message->table->supportsActivateAll()) {
            throw new GridActionNotAllowed();
        }

        // Scoped lists activate only their own rows (the old app activated recipes / sortiment of all products / categories)
        if ($message->table->scopeColumn() !== null && $message->scope === null) {
            throw new GridActionNotAllowed();
        }

        $this->repository->activateAll($message->table, $message->scope, $message->adminId);
    }
}
