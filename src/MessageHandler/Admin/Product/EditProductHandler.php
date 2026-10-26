<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Product;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Product\EditProduct;
use CeskaKruta\Web\Query\Admin\GetAdminProducts;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditProductHandler
{
    public function __construct(
        private GetAdminProducts $getAdminProducts,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditProduct $message): void
    {
        $product = $this->getAdminProducts->oneById($message->id);
        $columns = $message->columns->toArray();

        if ($product->isTurkey()) {
            // Whole turkey products keep their protected columns (PLAN §6, Phase 1 review item 10)
            $columns = array_intersect_key($columns, ['price' => true, 'code' => true, 'text' => true]);
        }

        $this->repository->update('product', $message->id, $columns, $message->adminId);
    }
}
