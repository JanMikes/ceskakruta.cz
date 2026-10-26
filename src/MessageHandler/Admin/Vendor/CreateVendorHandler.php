<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Vendor;

use CeskaKruta\Web\Message\Admin\Vendor\CreateVendor;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateVendorHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @return int id of the new vendor
     */
    public function __invoke(CreateVendor $message): int
    {
        return $this->repository->insert('vendor', $message->columns(), $message->adminId, orderable: true);
    }
}
