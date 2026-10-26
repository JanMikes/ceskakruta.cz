<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Vendor;

use CeskaKruta\Web\Exceptions\RecordNotFound;
use CeskaKruta\Web\Message\Admin\Vendor\EditVendor;
use CeskaKruta\Web\Query\Admin\GetAdminVendors;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class EditVendorHandler
{
    public function __construct(
        private GetAdminVendors $getAdminVendors,
        private AdminRecordRepository $repository,
    ) {
    }

    /**
     * @throws RecordNotFound
     */
    public function __invoke(EditVendor $message): void
    {
        $this->getAdminVendors->oneById($message->id);

        $this->repository->update('vendor', $message->id, $message->columns(), $message->adminId);
    }
}
