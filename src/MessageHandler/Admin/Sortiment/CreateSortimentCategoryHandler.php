<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Sortiment;

use CeskaKruta\Web\Message\Admin\Sortiment\CreateSortimentCategory;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateSortimentCategoryHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new category
     */
    public function __invoke(CreateSortimentCategory $message): int
    {
        return $this->repository->insert('sortiment_category', $message->columns(), $message->adminId, orderable: true);
    }
}
