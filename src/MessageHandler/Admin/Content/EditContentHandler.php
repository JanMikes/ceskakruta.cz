<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Content;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Content\EditContent;
use CeskaKruta\Web\Query\Admin\GetAdminContents;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditContentHandler
{
    public function __construct(
        private GetAdminContents $getAdminContents,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditContent $message): void
    {
        $this->getAdminContents->oneById($message->id);

        // HTML stored as submitted (raw HTML mode of the editor, docs/admin/PLAN.md §3.12)
        $this->repository->update('content', $message->id, ['text' => $message->text], $message->adminId);
    }
}
