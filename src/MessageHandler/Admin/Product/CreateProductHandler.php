<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Product;

use CeskaKruta\Web\Message\Admin\Product\CreateProduct;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateProductHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new product
     */
    public function __invoke(CreateProduct $message): int
    {
        return $this->repository->insert('product', $message->columns->toArray(), $message->adminId, orderable: true);
    }
}
