<?php

declare(strict_types=1);

namespace CeskaKruta\Web\MessageHandler\Admin\Actuality;

use CeskaKruta\Web\Message\Admin\Actuality\CreateActuality;
use CeskaKruta\Web\Repository\ActualityNewsletterRepository;
use CeskaKruta\Web\Repository\Admin\AdminRecordRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly final class CreateActualityHandler
{
    public function __construct(
        private AdminRecordRepository $repository,
        private ActualityNewsletterRepository $actualityNewsletterRepository,
    ) {
    }

    /**
     * @return int id of the new actuality
     */
    public function __invoke(CreateActuality $message): int
    {
        $id = $this->repository->insert('actuality', $message->columns(), $message->adminId);

        // Sent by the newsletter cron (SendNewsletterBatch)
        $this->actualityNewsletterRepository->enqueueActiveSubscribers($id, $message->adminId);

        return $id;
    }
}
