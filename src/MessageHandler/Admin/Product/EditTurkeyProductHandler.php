<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Product;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Product\EditTurkeyProduct;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditTurkeyProductHandler
{
    public function __construct(
        private GetAdminProducts $getAdminProducts,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditTurkeyProduct $message): void
    {
        $this->getAdminProducts->oneById($message->id);

        $this->repository->update('product', $message->id, $message->columns(), $message->adminId);
    }
}
