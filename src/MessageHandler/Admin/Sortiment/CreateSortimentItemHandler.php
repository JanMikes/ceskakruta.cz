<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Sortiment;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Sortiment\CreateSortimentItem;
use CeskaKruta\Web\Query\Admin\GetSortimentCategories;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateSortimentItemHandler
{
    public function __construct(
        private GetSortimentCategories $getSortimentCategories,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new sortiment item
     * @throws RecordNotFound
     */
    public function __invoke(CreateSortimentItem $message): int
    {
        $this->getSortimentCategories->oneById($message->categoryId);

        return $this->repository->insert('sortiment', $message->columns(), $message->adminId, orderable: true);
    }
}
