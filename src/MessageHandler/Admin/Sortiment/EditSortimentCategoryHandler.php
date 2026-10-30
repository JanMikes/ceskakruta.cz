<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Sortiment\EditSortimentCategory;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditSortimentCategoryHandler
{
    public function __construct(
        private GetSortimentCategories $getSortimentCategories,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditSortimentCategory $message): void
    {
        $this->getSortimentCategories->oneById($message->id);

        $this->repository->update('sortiment_category', $message->id, $message->columns(), $message->adminId);
    }
}
